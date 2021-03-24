define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'ingenicopayment',
                component: 'Ingenico_Ingenico/js/view/payment/method-renderer/ingenicopayment-method'
            }
        );
        return Component.extend({});
    }
);