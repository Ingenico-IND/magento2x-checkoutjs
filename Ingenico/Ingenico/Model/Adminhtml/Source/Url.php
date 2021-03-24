<?php

/**
 * Custom Options for ingenico backend configuration for WSD Locator Url
 **/

namespace Ingenico\Ingenico\Model\Adminhtml\Source;

use Magento\Payment\Model\Method\AbstractMethod;

class Url implements \Magento\Framework\Option\ArrayInterface

{
    protected $_options;

    public function toOptionArray()
    {
        $trans_req = array(
            array('value' => 'test', 'label' => 'TEST'),
            array('value' => 'live', 'label' => 'LIVE'),
        );

        return $trans_req;
    }
}
