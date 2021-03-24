<?php


namespace Ingenico\Ingenico\Model\Payment;

use Magento\Payment\Model\InfoInterface;
use PHPUnit\Framework\Constraint\Exception;
use Magento\Framework\Exception\CouldNotSaveException;

class IngenicoPayment extends \Magento\Payment\Model\Method\AbstractMethod
{
    protected $_code = "ingenicopayment";

    protected $_isInitializeNeeded      = false;
    protected $redirect_uri;
    protected $_canOrder = true;
    protected $_isGateway = true;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;

    public function isAvailable(
        \Magento\Quote\Api\Data\CartInterface $quote = null
    ) {
        return parent::isAvailable($quote);
    }


    public function getOrderPlaceRedirectUrl()
    {
        return \Magento\Framework\App\ObjectManager::getInstance()
            ->get('Magento\Framework\UrlInterface')->getUrl("ingenico/redirect");
    }

    public function refund(InfoInterface $payment, $amount)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $store_manager = $objectManager->get('Magento\Store\Model\StoreManagerInterface');
        $currencycode = $store_manager->getStore()->getCurrentCurrency()->getCode();

        $config_data = $objectManager->create('Ingenico\Ingenico\Helper\Data');

        $merchant_identifier = $config_data->getConfig('payment/ingenicopayment/ingenico_mercode');

        $invoice_date = date("d-m-Y", strtotime($payment->getCreditmemo()->getInvoice()->getCreatedAt()));

        $token = $payment->getCreditmemo()->getInvoice()->getTransactionId();

        $request_array = [
            "merchant" => [
                "identifier" => $merchant_identifier
            ],
            "cart" => (new \stdClass()),
            "transaction" => [
                "deviceIdentifier" => "S",
                "amount" => $amount,
                "currency" => $currencycode,
                "dateTime" => $invoice_date,
                "token" => $token,
                "requestType" => "R"
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

        $data = json_decode(file_get_contents($url, false, $context));

        if ($data->paymentMethod->paymentTransaction->statusCode != 400) {

            $message = "Ingenico Message: " .
                $data->paymentMethod->paymentTransaction->statusMessage .
                " - " .
                $data->paymentMethod->paymentTransaction->errorMessage;

            throw new CouldNotSaveException(__($message));
        }

        return $this;
    }
}
