require(['jquery', 'uiComponent'], function ($) {
  console.log('loading the document');
  $(document).ready(function () {
    console.log('rendering the plugin');
    const url =
      'https://plugin.openpix.com.br/v1/openpix.js?appID=Q2xpZW50X0lkX2Y1MTEyMzgxLTJkZmUtNGYyZS1iMWU5LWRhM2IyZjk2OTMyMjpDbGllbnRfU2VjcmV0X2RDdHFlRDU2REFxWURzWHJDbFRJTCtSRmp1dlZpYTY2Q2s1dUxBNFAyTzQ9&correlationID=cd2b28e6-5a2f-4ff6-bb34-56412feea815&node=openpix-order';
    $.ajax({
      url,
      dataType: 'script',
      complete: (response) => {
        console.log({ response });
        setTimeout(() => {
          console.log(window.$openpix);
          window.$openpix.push([
            'config',
            {
              appID:
                'Q2xpZW50X0lkX2Y1MTEyMzgxLTJkZmUtNGYyZS1iMWU5LWRhM2IyZjk2OTMyMjpDbGllbnRfU2VjcmV0X2RDdHFlRDU2REFxWURzWHJDbFRJTCtSRmp1dlZpYTY2Q2s1dUxBNFAyTzQ9',
            },
          ]);

          window.$openpix.push([
            'pix',
            {
              value: 10,
              correlationID: '1449',
            },
          ]);
        }, 3000);
      },
    });
  });
});
