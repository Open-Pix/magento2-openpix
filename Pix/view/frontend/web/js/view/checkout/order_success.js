define(['jquery', 'underscore', 'ko', 'uiComponent', 'OpenPixJs'], function (
  $,
  _,
  ko,
  Component,
  OpenPixJs,
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
      let isLoaded = false;

      // @todo MUST improve this before landing
      while (!isLoaded) {
        if (window.$openpix) {
          console.log('loaded');
          console.log('window.$openpix', window.$openpix);
          console.log({ OpenPixJs });
          console.log('window.$openpix.status()', window.$openpix.status());

          window.$openpix.push([
            'config',
            {
              appID:
                'Q2xpZW50X0lkXzZhZjY0MTNiLTM3NTgtNGMzYi04NzBmLTNkMWUxMDQ5NjU4NzpDbGllbnRfU2VjcmV0X0pqbGhSUlNaNU11Wll1YkQvQTdadjJ2UiswdjNocmFqNVRZN2NCRTcrUEU9',
            },
          ]);

          window.$openpix.push([
            'pix',
            {
              value: 1000, // R$ 10,00
              correlationID: 'd041533a-2ffb-46ac-83bc-b431379d3126',
              // description: 'product A',
            },
          ]);

          console.log('here');

          isLoaded = true;
        }
      }
      // if(window.$openpix) {
      //     window.$openpix.push([
      //       'config',
      //       {
      //         appID:
      //           'Q2xpZW50X0lkXzZhZjY0MTNiLTM3NTgtNGMzYi04NzBmLTNkMWUxMDQ5NjU4NzpDbGllbnRfU2VjcmV0X0pqbGhSUlNaNU11Wll1YkQvQTdadjJ2UiswdjNocmFqNVRZN2NCRTcrUEU9',
      //       },
      //     ]);
      // }

      // const script = document.createElement('script');
      //
      // script.id = 'plugin';
      // script.src = this.src;
      // script.async = true;
      // script.onload = function () {
      //   console.log('script loaded, you can use it now.');
      //
      //   console.log(window?.$openpix);
      //   console.log(window.$openpix.status());
      // };
      //
      // document.body.appendChild(script);

      // @todo check the payment code when the order success script to be working
      // if(this.paymentCode() === "openpix_pix") {
      // }
    },
  });
});
