<?php

namespace Ingenico\Ingenico\Controller\Token;

use Magento\Framework\Controller\ResultFactory;
use Ingenico\Ingenico\Model\AdditionalConfigVars;
use Ingenico\Ingenico\Logger\Logger;

class Index extends \Magento\Framework\App\Action\Action
{
    protected $additionalConfigVars;
    protected $_logger;

    public function __construct(
        Logger $logger,
        \Magento\Framework\App\Action\Context $context,
        AdditionalConfigVars $additionalConfigVars
    ) {
        $this->additionalConfigVars = $additionalConfigVars;
        $this->_logger = $logger;
        return parent::__construct($context);
    }

    public function execute()
    {
        $this->additionalConfigVars->setcustomerMobileNo($this->getRequest()->getParam('mobileNo'));
        $this->additionalConfigVars->setCustomerEmail($this->getRequest()->getParam('email'));

        $response = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $response->setData($this->additionalConfigVars->getConfig()['payment']['ingenico']);
        $response->setHttpResponseCode(200);
        $datastringlog = $this->additionalConfigVars->logToken();
        $MrctTxtID = $this->additionalConfigVars->getmerchantTxnRefNumber();
        $this->_logger->info("Transaction request: " . $datastringlog);
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $checkout_session = $objectManager->get('Magento\Checkout\Model\Session');
        $sales_order = $objectManager->get('Magento\Sales\Model\Order');
        $orderId = $checkout_session->getLastRealOrderId();
        $order = $sales_order->loadByIncrementId($orderId);
        $payment = $order->getPayment();
        $payment->setTransactionId($MrctTxtID);
        $payment->setAdditionalInformation(
            [\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => array("Transaction is yet to complete")]
        );
        $trn = $payment->addTransaction(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE, null, true);
        $trn->setIsClosed(1)->save();

        $payment->setParentTransactionId(null);
        $payment->save();
        return $response;
    }
}
