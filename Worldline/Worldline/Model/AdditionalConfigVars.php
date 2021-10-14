<?php

namespace Worldline\Worldline\Model;

use \Magento\Checkout\Model\ConfigProviderInterface;
use Worldline\Worldline\Helper\Data as Config;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;

class AdditionalConfigVars implements ConfigProviderInterface
{
    protected $config;

    protected $customerSession;

    protected $checkoutSession;

    protected $quote;

    protected $merchantTxnRefNumber;

    protected $customerId;

    protected $customerMobileNo;

    protected $customerEmail;

    public function __construct(Config $config, CheckoutSession $checkoutSession, CustomerSession $customerSession)
    {
        $this->config = $config;
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->quote = $checkoutSession->getQuote();
        $this->merchantTxnRefNumber = (string)rand(1, 1000000);

        $customer = $customerSession->getCustomer();
        if ($customer->getId()) {
            $this->customerId = $customer->getId();
        } else {
            $this->customerId = 'con' . rand(1, 1000000);
        }
    }

    public function getConfig()
    {
        $config = [
            'payment' => [
                'Worldline' => [
                    'merchantId' => $this->getMerchantCode(),
                    'schemeCode' => $this->getSchemeCode(),
                    'token' => $this->generateToken(),
                    'customerId' => $this->getCustomerId(),
                    'consumerMobileNo' => $this->getCustomerMobileNo(),
                    'customerEmail' => $this->getCustomerEmail(),
                    'amount' => $this->getAmount(),
                    'currency' => $this->getCurrencyCode(),
                    'txnId' => $this->getmerchantTxnRefNumber(),
                    "customerName" => $this->getCustomerName(),
                    "orderId" => $this->getOrderId(),
                    "primaryColourCode" => $this->getPrimaryColourCode(),
                    "secondaryColourCode" => $this->getSecondaryColourCode(),
                    "buttonColourCode1" => $this->getButtonColourCode1(),
                    "buttonColourCode2" => $this->getButtonColourCode2(),
                    "merchantLogoUrl" => $this->getMerchantLogoUrl(),
                    "showLoader" => $this->getShowLoader(),
                    "merchantMsg" => $this->getMerchantMsg(),
                    "disclaimerMsg" => $this->getDisclaimerMsg(),
                    "enableExpressPay" => $this->getenableExpressPay(),
                    "enableNewWindowFlow" => $this->getenableNewWindowFlow(),
                    "separateCardMode" => $this->getseparateCardMode(),
                    "paymentOrderArray" => $this->getpaymentorderarray(),
                    "paymentmodes" => $this->getPaymentMode(),
                    "txnType" => $this->getTxnType(),
                    "enableInstrumentDeRegistration" => $this->getenableInstrumentDeRegistration(),
                    "hideSavedInstruments" => $this->gethideSavedInstruments(),
                    "saveInstrument" => $this->getsaveInstrument(),
                    "embedPaymentGatewayOnPage" => $this->embedPaymentGatewayOnPage(),
                    "displayErrorMessageOnPopup" => $this->displayErrorMessageOnPopup()
                ]
            ]
        ];

        return $config;
    }


    private function getCustomerName()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $sales_order = $objectManager->get('Magento\Sales\Model\Order');
        $checkout_session = $objectManager->get('Magento\Checkout\Model\Session');
        $orderId = $checkout_session->getLastRealOrderId();
        $order = $sales_order->loadByIncrementId($orderId);
        $billing = $order->getBillingAddress();
        $customerName = $billing->getFirstname() . " " . $billing->getLastname();

        return $customerName;
    }

    private function getOrderId()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $sales_order = $objectManager->get('Magento\Sales\Model\Order');
        $checkout_session = $objectManager->get('Magento\Checkout\Model\Session');
        $orderId = $checkout_session->getLastRealOrderId();

        return $orderId;
    }

    private function getMerchantCode()
    {
        return $this->config->getConfig('payment/Worldlinepayment/Worldline_mercode');
    }

    private function embedPaymentGatewayOnPage()
    {
        return $this->config->getConfig('payment/Worldlinepayment/embedPaymentGatewayOnPage') ? true : false;
    }

    private function displayErrorMessageOnPopup()
    {
        return $this->config->getConfig('payment/Worldlinepayment/displayErrorMessageOnPopup') ? true : false;
    }

    private function getSchemeCode()
    {
        return $this->config->getConfig('payment/Worldlinepayment/Worldline_scode');
    }

    private function generateToken()
    {
        $generatorString = $this->getMerchantCode() . "|" .
            $this->getmerchantTxnRefNumber() . "|" .
            $this->getAmount() . "|" . "|" . $this->getCustomerId() . "|" .
            $this->getCustomerMobileNo() . "|" . $this->getCustomerEmail() . "||||||||||" . $this->getMerchantKey();

        return hash('sha512',  $generatorString);
    }

    public function logToken()
    {
        $generatorString = $this->getMerchantCode() . "|" .
            $this->getmerchantTxnRefNumber() . "|" .
            $this->getAmount() . "|" . "|" . $this->getCustomerId() . "|" .
            $this->getCustomerMobileNo() . "|" . $this->getCustomerEmail() . "||||||||||" . $this->getMerchantKey();

        return $generatorString;
    }

    private function getMerchantKey()
    {
        return $this->config->getConfig('payment/Worldlinepayment/Worldline_key');
    }

    private function getCustomerId()
    {
        return $this->customerId;
    }

    public function getmerchantTxnRefNumber()
    {
        return $this->merchantTxnRefNumber;
    }

    private function getCustomerMobileNo()
    {
        if ($this->customerMobileNo) {
            if (strpos($this->customerMobileNo, '+') !== false) {
                $customerMobNumber = str_replace("+", "", $this->customerMobileNo);
                return $customerMobNumber;
            } else {
                return $this->customerMobileNo;
            }
        }

        if (strpos($this->quote->getBillingAddress()->getTelephone(), '+') !== false) {
            $customerMobNumber = str_replace("+", "", $this->quote->getBillingAddress()->getTelephone());
            return $customerMobNumber;
        } else {
            return $this->quote->getBillingAddress()->getTelephone();
        }
    }

    public function setCustomerEmail($email)
    {
        $this->customerEmail = $email;
    }

    public function setcustomerMobileNo($mobileNo)
    {
        $this->customerMobileNo = $mobileNo;
    }

    private function getCustomerEmail()
    {
        if ($this->customerEmail) {
            return $this->customerEmail;
        }

        return $this->quote->getCustomerEmail();
    }

    private function getAmount()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $sales_order = $objectManager->get('Magento\Sales\Model\Order');
        $checkout_session = $objectManager->get('Magento\Checkout\Model\Session');
        $orderId = $checkout_session->getLastRealOrderId();
        $order = $sales_order->loadByIncrementId($orderId);
        if ($this->config->getConfig('payment/Worldlinepayment/webservice_locator') == 'test') {
            $Amount = "1.0";
        } else {
            $Amount = round($order->getBaseGrandTotal(), 2);
        }

        return $Amount;
    }

    private function getCurrencyCode()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $store_manager = $objectManager->get('Magento\Store\Model\StoreManagerInterface');
        return $store_manager->getStore()->getCurrentCurrency()->getCode();
    }

    private function getPrimaryColourCode()
    {
        if ($this->config->getConfig('payment/Worldlinepayment/primary_color_code')) {
            $primary_color_code = $this->config->getConfig('payment/Worldlinepayment/primary_color_code');
        } else {
            $primary_color_code = '#3977b7';
        }

        return $primary_color_code;
    }

    private function getSecondaryColourCode()
    {
        if ($this->config->getConfig('payment/Worldlinepayment/secondary_color_code')) {
            $secondary_color_code = $this->config->getConfig('payment/Worldlinepayment/secondary_color_code');
        } else {
            $secondary_color_code = '#FFFFFF';
        }

        return $secondary_color_code;
    }

    private function getButtonColourCode1()
    {
        if ($this->config->getConfig('payment/Worldlinepayment/button_color_code_1')) {
            $button_color_code_1 = $this->config->getConfig('payment/Worldlinepayment/button_color_code_1');
        } else {
            $button_color_code_1 = '#1969bb';
        }

        return $button_color_code_1;
    }

    private function getButtonColourCode2()
    {
        if ($this->config->getConfig('payment/Worldlinepayment/button_color_code_2')) {
            $button_color_code_2 = $this->config->getConfig('payment/Worldlinepayment/button_color_code_2');
        } else {
            $button_color_code_2 = '#FFFFFF';
        }

        return $button_color_code_2;
    }

    private function getMerchantLogoUrl()
    {
        $logo_url = $this->config->getConfig('payment/Worldlinepayment/merchant_logo_url');
        if ($logo_url && @getimagesize($logo_url)) {
            $merchant_logo_url = $logo_url;
        } else {
            $merchant_logo_url = 'https://www.paynimo.com/CompanyDocs/company-logo-md.png';
        }

        return $merchant_logo_url;
    }

    private function getShowLoader()
    {
        return $this->config->getConfig('payment/Worldlinepayment/showLoader') ? true : false;
    }

    private function getMerchantMsg()
    {
        $merchantMsg = $this->config->getConfig('payment/Worldlinepayment/merchantMsg');
        $merchantMsg = $merchantMsg ? $merchantMsg : '';
        return $merchantMsg;
    }

    private function getDisclaimerMsg()
    {
        $disclaimerMsg = $this->config->getConfig('payment/Worldlinepayment/disclaimerMsg');
        $disclaimerMsg = $disclaimerMsg ? $disclaimerMsg : '';
        return $disclaimerMsg;
    }

    private function getenableExpressPay()
    {
        return $this->config->getConfig('payment/Worldlinepayment/enableExpressPay') ? true : false;
    }

    private function getseparateCardMode()
    {
        return $this->config->getConfig('payment/Worldlinepayment/separateCardMode') ? true : false;
    }

    private function getenableNewWindowFlow()
    {
        return $this->config->getConfig('payment/Worldlinepayment/enableNewWindowFlow') ? true : false;
    }

    private function getpaymentorderarray()
    {
        $paymentModeArray = array();
        if ($this->config->getConfig('payment/Worldlinepayment/paymentModeOrder')) {
            $paymentModeOrder = $this->config->getConfig('payment/Worldlinepayment/paymentModeOrder');
            $paymentorderarray = explode(',', $paymentModeOrder);
            $paymentModeOrder_1 = isset($paymentorderarray[0]) ? $paymentorderarray[0] : null;
            array_push($paymentModeArray, $paymentModeOrder_1);
            $paymentModeOrder_2 = isset($paymentorderarray[1]) ? $paymentorderarray[1] : null;
            array_push($paymentModeArray, $paymentModeOrder_2);
            $paymentModeOrder_3 = isset($paymentorderarray[2]) ? $paymentorderarray[2] : null;
            array_push($paymentModeArray, $paymentModeOrder_3);
            $paymentModeOrder_4 = isset($paymentorderarray[3]) ? $paymentorderarray[3] : null;
            array_push($paymentModeArray, $paymentModeOrder_4);
            $paymentModeOrder_5 = isset($paymentorderarray[4]) ? $paymentorderarray[4] : null;
            array_push($paymentModeArray, $paymentModeOrder_5);
            $paymentModeOrder_6 = isset($paymentorderarray[5]) ? $paymentorderarray[5] : null;
            array_push($paymentModeArray, $paymentModeOrder_6);
            $paymentModeOrder_7 = isset($paymentorderarray[6]) ? $paymentorderarray[6] : null;
            array_push($paymentModeArray, $paymentModeOrder_7);
            $paymentModeOrder_8 = isset($paymentorderarray[7]) ? $paymentorderarray[7] : null;
            array_push($paymentModeArray, $paymentModeOrder_8);
            $paymentModeOrder_9 = isset($paymentorderarray[8]) ? $paymentorderarray[8] : null;
            array_push($paymentModeArray, $paymentModeOrder_9);
            $paymentModeOrder_10 = isset($paymentorderarray[9]) ? $paymentorderarray[9] : null;
            array_push($paymentModeArray, $paymentModeOrder_10);
        } else {
            $paymentModeOrder_1 = "cards";
            array_push($paymentModeArray, $paymentModeOrder_1);
            $paymentModeOrder_2 = "netBanking";
            array_push($paymentModeArray, $paymentModeOrder_2);
            $paymentModeOrder_3 = "imps";
            array_push($paymentModeArray, $paymentModeOrder_3);
            $paymentModeOrder_4 = "wallets";
            array_push($paymentModeArray, $paymentModeOrder_4);
            $paymentModeOrder_5 = "cashCards";
            array_push($paymentModeArray, $paymentModeOrder_5);
            $paymentModeOrder_6 =  "UPI";
            array_push($paymentModeArray, $paymentModeOrder_6);
            $paymentModeOrder_7 =  "MVISA";
            array_push($paymentModeArray, $paymentModeOrder_7);
            $paymentModeOrder_8 = "debitPin";
            array_push($paymentModeArray, $paymentModeOrder_8);
            $paymentModeOrder_9 = "emiBanks";
            array_push($paymentModeArray, $paymentModeOrder_9);
            $paymentModeOrder_10 = "NEFTRTGS";
            array_push($paymentModeArray, $paymentModeOrder_10);
        }

        return json_encode($paymentModeArray);
    }

    private function getPaymentMode()
    {
        $paymentmodes = $this->config->getConfig('payment/Worldlinepayment/paymentMode');
        $paymentmodes = $paymentmodes ? $paymentmodes : 'all';
        return $paymentmodes;
    }

    private function getTxnType()
    {
        return $this->config->getConfig('payment/Worldlinepayment/txnType');
    }

    private function getenableInstrumentDeRegistration()
    {
        if ($this->getenableExpressPay() && $this->config->getConfig('payment/Worldlinepayment/enableInstrumentDeRegistration')) {
            return true;
        } else {
            return false;
        }
    }

    private function gethideSavedInstruments()
    {
        if ($this->getenableExpressPay() && $this->config->getConfig('payment/Worldlinepayment/hideSavedInstruments')) {
            return true;
        } else {
            return false;
        }
    }

    private function getsaveInstrument()
    {
        return $this->config->getConfig('payment/Worldlinepayment/saveInstrument') ? true : false;
    }
}
