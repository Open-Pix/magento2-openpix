define(['jquery', 'underscore', 'ko', 'uiComponent'], function (
  $,
  _,
  ko,
  Component,
) {
  return Component.extend({
    defaults: {
      src: ko.observable(null),
      paymentCode: ko.observable(null),
    },
    initialize: function () {
      this._super();
      this.loadPluginJs();
    },
    loadPluginJs() {
      console.log(this.paymentCode);
      console.log(this.src);
      console.log(window.$openpix);
      console.log(window.$openpix.status());

      // @todo check the payment code when the order success script to be working
      // if(this.paymentCode() === "openpix_pix") {
      // }
    },
  });
});
