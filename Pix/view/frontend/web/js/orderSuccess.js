require(['jquery', 'uiComponent'], function ($) {
  console.log('loading the document');
  $(document).ready(function () {
    console.log('rendering the plugin');
    const url =
      'https://plugin.openpix.com.br/v1/openpix.js?appID=Q2xpZW50X0lkX2I5MmQ1NjhlLTVkNjktNDhhNS1iYjhhLWNlNTU4N2VhNjE0ZTpDbGllbnRfU2VjcmV0X3VJdXY5S1BnMkkxeVp4eXVUelJWeFZTWmZhanJMK25hSktWSlZ5TXhUNVE9&correlationID=e53b6083-8eeb-4e1d-a0da-9c4fbcd2c225&node=openpix-order';
    $.ajax({
      url,
      dataType: 'script',
      success: () => {
        console.log(window.$openpix);
        window.$openpix = [
          'config',
          {
            appID:
              'Q2xpZW50X0lkX2I5MmQ1NjhlLTVkNjktNDhhNS1iYjhhLWNlNTU4N2VhNjE0ZTpDbGllbnRfU2VjcmV0X3VJdXY5S1BnMkkxeVp4eXVUelJWeFZTWmZhanJMK25hSktWSlZ5TXhUNVE9',
          },
        ];

        window.$openpix.push([
          'pix',
          {
            value: 10,
            correlationID: '1444',
          },
        ]);
      },
    });
  });
});
