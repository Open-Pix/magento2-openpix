define([
  'jquery',
  'Magento_Checkout/js/view/summary/abstract-total',
  'Magento_Checkout/js/model/quote',
], function ($, Component, quote) {
  'use strict';
  return Component.extend({
    defaults: {
      template: 'OpenPix_Pix/summary/openpix_discount',
    },
    totals: quote.getTotals(),
    isDisplayed: function () {
      return this.getPureValue() !== 0;
    },
    getPureValue: function () {
      let price = 0;
      if (this.totals() && this.totals().openpix_discount) {
        price = parseFloat(this.totals().openpix_discount);
      } else {
        $.each(this.totals().total_segments, function (index, total_segment) {
          if (total_segment.code === 'openpix_discount') {
            price = total_segment.value;
          }
        });
      }
      return price;
    },

    getValue: function () {
      return this.getFormattedPrice(this.getPureValue());
    },

    getTitle: function () {
      return 'Giftback Discount';
    },
  });
});
