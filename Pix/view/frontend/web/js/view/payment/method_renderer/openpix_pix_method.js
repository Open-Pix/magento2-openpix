define(['Magento_Checkout/js/view/payment/default', 'underscore'], function (
  Component,
  _,
) {
  'use strict';

  return Component.extend({
    defaults: {
      template: 'OpenPix_Pix/payment/pix',
      wallet: '',
    },

    initObservable: function () {
      this._super().observe(['wallet']);

      return this;
    },

    getData: function () {
      return {
        method: this.item.method,
        additional_data: {
          wallet: this.wallet(),
        },
      };
    },

    getCode: function () {
      return 'openpix_pix';
    },
  });
});
