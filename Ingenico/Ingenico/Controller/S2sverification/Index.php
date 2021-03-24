<?php

namespace Ingenico\Ingenico\Controller\S2sverification;

use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Response\Http;
use Magento\Sales\Model\Order\Payment\Transaction\Builder as TransactionBuilder;
use Ingenico\Ingenico\Logger\Logger;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;

class Index extends  \Magento\Framework\App\Action\Action
{
    protected $_objectmanager;
    protected $_checkoutSession;
    protected $_orderFactory;
    protected $urlBuilder;
    protected $response;
    protected $config;
    protected $messageManager;
    protected $transactionRepository;
    protected $cart;
    protected $inbox;
    protected $_logger;
    protected $_messageManager;
    protected $_orderRepository;
    protected $_invoiceService;
    protected $_transaction;
    protected $invoiceSender;
    public function __construct(
        Logger $logger,
        Context $context,
        Session $checkoutSession,
        OrderFactory $orderFactory,
        ScopeConfigInterface $scopeConfig,
        InvoiceSender $invoiceSender,
        Http $response,
        TransactionBuilder $tb,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Framework\DB\Transaction $transaction,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\AdminNotification\Model\Inbox $inbox,
        \Magento\Sales\Api\TransactionRepositoryInterface $transactionRepository,
        \Magento\Framework\Data\Form\FormKey $formKey,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        $this->request = $request;
        $this->formKey = $formKey;
        $this->invoiceSender = $invoiceSender;
        $this->_orderRepository = $orderRepository;
        $this->_invoiceService = $invoiceService;
        $this->_transaction = $transaction;
        $this->request->setParam('form_key', $this->formKey->getFormKey());
        $this->checkoutSession = $checkoutSession;
        $this->orderFactory = $orderFactory;
        $this->response = $response;
        $this->config = $scopeConfig;
        $this->transactionBuilder = $tb;
        $this->cart = $cart;
        $this->inbox = $inbox;
        $this->transactionRepository = $transactionRepository;
        $this->urlBuilder = \Magento\Framework\App\ObjectManager::getInstance()
            ->get('Magento\Framework\UrlInterface');
        $this->_logger = $logger;
        $this->_messageManager = $messageManager;
        parent::__construct($context);
    }
    public function execute()
    {
        $str = $this->getRequest()->getParam('msg');
        if($str){
        $key = $this->config->getValue('payment/ingenicopayment/ingenico_key');
        $MrctCode = $this->config->getValue('payment/ingenicopayment/ingenico_mercode');
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $store_manager = $objectManager->get('Magento\Store\Model\StoreManagerInterface');
        $currencycode = $store_manager->getStore()->getCurrentCurrency()->getCode();
        $responseData = explode('|', $str); 
        $responseData_1 = explode('|', $str);
        $oid = explode('orderid:', $responseData[7]);
        $txn_msg = $responseData[1];
        $txn_err_msg = $responseData[2];
        $oid_1 = $oid[1];
        $oid2 = explode('}', $oid_1);
        $oidreceived = $oid2[0];
        $orderId = $oidreceived;
        if($orderId == ''){
            $orderId = $this->checkoutSession->getLastOrderId();
        }
        $verificationHash = array_pop($responseData_1);
        $hashableString = join('|', $responseData_1) . "|" . $key;
        $hashedString = hash('sha512',  $hashableString);
        
        $order = $this->orderFactory->create()->load($orderId);
        $payment = $order->getPayment();
        $payment->setTransactionId($responseData[5]);
        $payment->setAdditionalInformation(
            [\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => array("Transaction is yet to complete")]
        );
        $trn = $payment->addTransaction(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_ORDER, null, true);
        $trn->setIsClosed(1)->save();
        $payment->setParentTransactionId(null);
        $payment->save();
        $this->_logger->info("Transaction S2S response: txn_status: " . $responseData[0] . "|txn_msg: " . $responseData[1] . "|txn_err_msg: " . $responseData[2] . "|clnt_txn_ref: " . $responseData[3] . "|tpsl_bank_cd: " . $responseData[4] . "|tpsl_txn_id: " . $responseData[5] . "|txn_amt: " . $responseData[6] . "|clnt_rqst_meta: " . $responseData[7] . "|tpsl_txn_time: " . $responseData[8] . "|bal_amt: " . $responseData[9] . "|card_id: " . $responseData[10] . "|alias_name: " . $responseData[11] . "|BankTransactionID: " . $responseData[12] . "|mandate_reg_no: " . $responseData[13] . "|token: " . $responseData[14] . "|hash: " . $responseData[15]);
        if ($responseData[0] == '0300' && $hashedString == $verificationHash) {
            $orderSuccessStatus =  $this->config->getValue('payment/ingenicopayment/order_success_status');
            if ($orderSuccessStatus == "") {
                $orderSuccessStatus = $order->getConfig()->getStateDefaultStatus(Order::STATE_PROCESSING);
            }
            $order->setState(Order::STATE_PROCESSING)->setStatus($orderSuccessStatus);
            # send new email
            $order->setCanSendNewEmailFlag(true);
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $objectManager->create('Magento\Sales\Model\OrderNotifier')->notify($order);
            if ($order->canInvoice()) {
                $invoice = $this->_invoiceService->prepareInvoice($order);
                $invoice->setTransactionId($responseData[5]);
                $invoice->register()->pay();
                $invoice->save();
                $transactionSave = $this->_transaction->addObject(
                    $invoice
                )->addObject(
                    $invoice->getOrder()
                );
                $transactionSave->save();
                $this->invoiceSender->send($invoice);
                //send notification code
                $order->addStatusHistoryComment(
                    __('Notified customer about invoice #%1.', $invoice->getId())
                )
                    ->setIsCustomerNotified(true);
            }
            $order->save();
            echo json_encode($responseData[3]."|".$responseData[5]."|1");
            die;
        } else {
            $this->cancelAction($orderId);
            echo json_encode($responseData[3]."|".$responseData[5]."|0");
            die;
        }
    }
    else{
            echo json_encode("INVALID DATA");
            die;
    }
    
    }
    public function cancelAction($orderId)
    {
        if ($orderId) {
            $order = $this->orderFactory->create()->load($orderId);
            $order->cancel()->setState(Order::STATE_CANCELED, true, 'Gateway has declined the payment.')->save();
        }   
    }
}
