<?php

namespace Ingenico\Ingenico\Block\Adminhtml;

use \Magento\Framework\View\Element\Template;
use \Magento\Framework\View\Element\Template\Context;

class Reconciliation extends Template
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
        return $this->getUrl('ingenico/reconciliationresult');
    }

    public function getMaxFromDate()
    {
        return date('Y-m-d', strtotime("-1 days"));
    }

    public function getMaxToDate()
    {
        return date('Y-m-d');
    }
}
