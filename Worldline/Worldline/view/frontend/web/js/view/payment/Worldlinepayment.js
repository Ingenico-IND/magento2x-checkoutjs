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
                type: 'Worldlinepayment',
                component: 'Worldline_Worldline/js/view/payment/method-renderer/Worldlinepayment-method'
            }
        );
        return Component.extend({});
    }
);