/*browser:true*/
/*global define*/
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
                type: 'veriteworks_paypal',
                component: 'Veriteworks_Paypal/js/view/payment/method-renderer/paypal-method'
            }
        );

        /** Add view logic here if needed */
        return Component.extend({});
    }
);
