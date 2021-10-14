<?php

namespace Worldline\Worldline\Block\Adminhtml;

use \Magento\Framework\View\Element\Template;
use \Magento\Framework\View\Element\Template\Context;

class Verification extends Template
{
    public function __construct(
        Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->_storeManager = $storeManager;
        parent::__construct($context);
    }

    public function getResultUrl()
    {
        return $this->getUrl('Worldline/verificationresult');
    }

    public function getMerchantCode()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $config_data = $objectManager->create('Worldline\Worldline\Helper\Data');

        return $config_data->getConfig('payment/Worldlinepayment/Worldline_mercode');
    }
}
