/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*global define*/
define(['OpenPix_Pix/js/view/summary/openpix_discount'], function (Component) {
  'use strict';
  return Component.extend({
    defaults: {
      template: 'OpenPix_Pix/cart/totals/openpix_discount',
    },
    /**
     * @override
     *
     * @returns {boolean}
     */
    isDisplayed: function () {
      return this.getPureValue() !== 0;
    },
  });
});
