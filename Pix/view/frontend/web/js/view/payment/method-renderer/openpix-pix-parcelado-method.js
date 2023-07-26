define([
    'Magento_Checkout/js/view/payment/default',
    'jquery',
    'Magento_Checkout/js/model/payment/additional-validators',
    'Magento_Ui/js/model/messageList',
], function (Component, $, validators, messageList) {
    'use strict';
    return Component.extend({
        defaults: {
            template: 'OpenPix_Pix/payment/openpix_pix_parcelado',
        },

        initObservable: function () {
            this._super().observe(['cpfCnpj']);

            return this;
        },

        getData: function () {
            return {
                method: this.item.method,
            };
        },
        getCode: function () {
            return 'openpix_pix_parcelado';
        },
        // add required logic here
    });
});