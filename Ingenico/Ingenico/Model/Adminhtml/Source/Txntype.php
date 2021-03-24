<?php

/**
 * Custom Options for ingenico backend configuration for TXNtype
 **/

namespace Ingenico\Ingenico\Model\Adminhtml\Source;

use Magento\Payment\Model\Method\AbstractMethod;

class Txntype implements \Magento\Framework\Option\ArrayInterface

{
    protected $_options;

    public function toOptionArray()
    {
        $trans_req = array(
            array('value' => 'SALE', 'label' => 'SALE')
        );

        return $trans_req;
    }
}
