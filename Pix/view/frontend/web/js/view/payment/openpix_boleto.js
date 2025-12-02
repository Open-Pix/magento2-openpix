define([
  'uiComponent',
  'Magento_Checkout/js/model/payment/renderer-list',
], function (Component, rendererList) {
  'use strict';
  rendererList.push({
    type: 'openpix_boleto',
    component:
      'OpenPix_Pix/js/view/payment/method-renderer/openpix-boleto-method',
  });
  /** Add view logic here if needed */
  return Component.extend({});
});
