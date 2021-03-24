<?php

namespace Ingenico\Ingenico\Controller\Adminhtml\VerificationResult;

use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\HTTP\Client\Curl;

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
        $merchant_identifier = $this->getRequest()->getParam('merchant_identifier');
        $date_time = date('d-m-Y', strtotime($this->getRequest()->getParam('date_time')));

        $merchant_transaction_id = $this->getRequest()->getParam('merchant_transaction_id');

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $store_manager = $objectManager->get('Magento\Store\Model\StoreManagerInterface');
        $currencycode = $store_manager->getStore()->getCurrentCurrency()->getCode();


        $request_array = [
            "merchant" => [
                "identifier" => $merchant_identifier
            ],
            "transaction" => [
                "deviceIdentifier" => "S",
                "currency" => $currencycode,
                "dateTime" => $date_time,
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
        $this->paymentResult = json_decode($result);

        $resultJson = $this->resultJsonFactory->create();

        if ($this->isPaymentSuccessful()) {
            $response = ['success' => true, 'data' => $this->paymentResult];
        } else {
            $response = ['success' => false, 'data' => $this->paymentResult];
        }

        $resultJson->setData($response);
        return $resultJson;
    }

    protected function isPaymentSuccessful()
    {
        if ($this->paymentResult->paymentMethod->paymentTransaction->statusCode == 300) {
            return true;
        }

        return false;
    }
}
