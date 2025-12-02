define([
  'uiComponent',
  'Magento_Checkout/js/model/payment/renderer-list',
], function (Component, rendererList) {
  'use strict';
  rendererList.push({
    type: 'openpix_pix',
    component: 'OpenPix/Pix/js/view/payment/method_renderer/openpix_pix_method',
  });
  /** Add view logic here if needed */
  return Component.extend({});
});
