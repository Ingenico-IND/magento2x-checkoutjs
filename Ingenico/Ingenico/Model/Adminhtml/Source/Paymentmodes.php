<?php

/**
 * Custom Options for ingenico backend configuration for Payment modes
 **/

namespace Ingenico\Ingenico\Model\Adminhtml\Source;

class Paymentmodes implements \Magento\Framework\Option\ArrayInterface

{
    protected $_options;

    public function toOptionArray()
    {
        $trans_req = array(
            array('value' => 'all', 'label' => 'All'),
            array('value' => 'cards', 'label' => 'Cards'),
            array('value' => 'netBanking', 'label' => 'NetBanking'),
            array('value' => 'UPI', 'label' => 'UPI'),
            array('value' => 'imps', 'label' => 'Imps'),
            array('value' => 'wallets', 'label' => 'Wallets'),
            array('value' => 'cashCards', 'label' => 'CashCards'),
            array('value' => 'NEFTRTGS', 'label' => 'NEFTRTGS'),
            array('value' => 'emiBanks', 'label' => 'EmiBanks'),
        );

        return $trans_req;
    }
}
