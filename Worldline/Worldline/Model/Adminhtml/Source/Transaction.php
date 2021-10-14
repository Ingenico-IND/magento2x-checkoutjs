<?php

/**
 * Custom Options for Worldline backend configuration for Transaction Request Type
 **/

namespace Worldline\Worldline\Model\Adminhtml\Source;

class Transaction implements \Magento\Framework\Option\ArrayInterface

{
    protected $_options;

    public function toOptionArray()
    {
        $trans_req = array(
            array('value' => 'T', 'label' => 'T'),
        );

        return $trans_req;
    }
}
