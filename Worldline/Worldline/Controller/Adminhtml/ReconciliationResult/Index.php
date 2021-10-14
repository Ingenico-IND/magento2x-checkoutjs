<?php

namespace Worldline\Worldline\Controller\Adminhtml\ReconciliationResult;

use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Sales\Model\Order;

class Index extends \Magento\Framework\App\Action\Action
{
    protected $resultPageFactory;

    protected $resultJsonFactory;

    protected $curl;

    protected $paymentResult;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        JsonFactory $resultJsonFactory,
        Curl $curl
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->curl = $curl;
    }


    public function execute()
    {
        $from_date = date('Y-m-d' . '00:00:00', strtotime($this->getRequest()->getParam('from_date')));
        $to_date = date('Y-m-d' . "23:59:59", strtotime($this->getRequest()->getParam('to_date')));

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $store_manager = $objectManager->get('Magento\Store\Model\StoreManagerInterface');
        $currencycode = $store_manager->getStore()->getCurrentCurrency()->getCode();

        $orderIds = $this->getOrderCollectionByDate($from_date, $to_date);

        $merchant_identifier = $this->getMerchantCode();

        $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
        $orderFactory = $objectManager->get('Magento\Sales\Model\OrderFactory');
        $config = $objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface');
        $connection = $resource->getConnection();

        $tableName   = $connection->getTableName('sales_payment_transaction');

        $successFullOrdersIds = [];

        foreach ($orderIds as $orderId) {

            $sql = "select txn_id,created_at from " . $tableName . " where order_id=" . $orderId . " and txn_type='capture' order by created_at asc limit 1 ";

            $result = $connection->query($sql);

            $data = $result->fetch();

            if (!$data) {
                continue;
            }

            $merchant_transaction_id = $data['txn_id'];
            $transaction_date = date('d-m-Y', strtotime($data['created_at']));;

            $request_array = [
                "merchant" => [
                    "identifier" => $merchant_identifier
                ],
                "transaction" => [
                    "deviceIdentifier" => "S",
                    "currency" => $currencycode,
                    "dateTime" => $transaction_date,
                    "identifier" => $merchant_transaction_id,
                    "requestType" => "O"
                ]
            ];

            $url = "https://www.paynimo.com/api/paynimoV2.req";

            $options = array(
                'http' => array(
                    'method'  => 'POST',
                    'content' => json_encode($request_array),
                    'header' =>  "Content-Type: application/json\r\n" .
                        "Accept: application/json\r\n"
                )
            );
            $context     = stream_context_create($options);
            $result      = file_get_contents($url, false, $context);

            $paymentResult = json_decode($result);

            $transaction = $objectManager->create("Magento\Framework\DB\Transaction");
            $invoiceService = $objectManager->create("Magento\Sales\Model\Service\InvoiceService");
            $invoiceSender = $objectManager->create("Magento\Sales\Model\Order\Email\Sender\InvoiceSender");

            if ($this->isPaymentSuccessful($paymentResult)) {
                $order = $orderFactory->create()->load($orderId);
                $orderSuccessStatus =  $config->getValue('payment/Worldlinepayment/order_success_status');
                if ($orderSuccessStatus == "") {
                    $orderSuccessStatus = $order->getConfig()->getStateDefaultStatus(Order::STATE_PROCESSING);
                }
                $order->setState(Order::STATE_PROCESSING)->setStatus($orderSuccessStatus);

                if ($order->canInvoice()) {
                    $invoice = $invoiceService->prepareInvoice($order);
                    $invoice->setTransactionId($this->getTPSLTransactionId($paymentResult));
                    $invoice->register()->pay();
                    $invoice->save();
                    $transactionSave = $transaction->addObject(
                        $invoice
                    )->addObject(
                        $invoice->getOrder()
                    );
                    $transactionSave->save();
                    $invoiceSender->send($invoice);
                    //send notification code
                    $order->addStatusHistoryComment(
                        __('Notified customer about invoice #%1.', $invoice->getId())
                    )
                        ->setIsCustomerNotified(true);
                }

                $payment = $order->getPayment();
                $payment->setTransactionId($this->getTPSLTransactionId($paymentResult));
                $payment->setAdditionalInformation(
                    [\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => array("Transaction is yet to complete")]
                );
                $trn = $payment->addTransaction(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_ORDER, null, true);
                $trn->setIsClosed(1)->save();
                $payment->setParentTransactionId(null);
                $payment->save();
                $order->save();

                array_push($successFullOrdersIds, $orderId);
            } else if ($this->isPaymentFailed($paymentResult)) {
                if ($orderId) {
                    $order = $orderFactory->create()->load($orderId);
                    $order->cancel()->setState(Order::STATE_CANCELED, true, 'Gateway has declined the payment.')->save();
                }
                array_push($successFullOrdersIds, $orderId);
            }
        }
        if ($successFullOrdersIds) {
            $message = "Updated Order Status for Order ID " . implode(",", $successFullOrdersIds);
        } else {
            $message = "Found  no orders to update";
        }
        $response = ['success' => true, 'data' => $message];

        $resultJson = $this->resultJsonFactory->create();

        $resultJson->setData($response);
        return $resultJson;
    }

    protected function isPaymentSuccessful($paymentResult)
    {
        if ($paymentResult->paymentMethod->paymentTransaction->statusCode == 300) {
            return true;
        }

        return false;
    }

    protected function getTPSLTransactionId($paymentResult)
    {
        if ($paymentResult->paymentMethod->paymentTransaction->statusCode == 300) {
            return $paymentResult->paymentMethod->paymentTransaction->identifier;
        }

        return null;
    }

    public function getMerchantCode()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $config_data = $objectManager->create('Worldline\Worldline\Helper\Data');

        return $config_data->getConfig('payment/Worldlinepayment/Worldline_mercode');
    }

    public function getOrderCollectionByDate($from, $to)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $orderCollectionFactory = $objectManager->create('Magento\Sales\Model\ResourceModel\Order\CollectionFactory');

        $collection = $orderCollectionFactory->create()
            ->addFieldToSelect('*')
            ->addFieldToFilter(
                'status',
                ['in' => 'pending']
            )
            ->addFieldToFilter(
                'created_at',
                ['gteq' => $from]
            )
            ->addFieldToFilter(
                'created_at',
                ['lteq' => $to]
            );

        $collection->getSelect()
            ->join(
                ["sop" => "sales_order_payment"],
                'main_table.entity_id = sop.parent_id',
                array('method')
            )
            ->where('sop.method = ?', "Worldlinepayment");

        $collection->setOrder(
            'created_at',
            'desc'
        );

        if (!$collection->getTotalCount()) {
            return [];
        };

        return $collection->getAllIds();
    }

    protected function isPaymentFailed($paymentResult)
    {
        if (
            $paymentResult->paymentMethod->paymentTransaction->statusCode == 392 ||
            $paymentResult->paymentMethod->paymentTransaction->statusCode == 396 ||
            $paymentResult->paymentMethod->paymentTransaction->statusCode == 397 ||
            $paymentResult->paymentMethod->paymentTransaction->statusCode == 399
        ) {
            return true;
        }

        return false;
    }
}
