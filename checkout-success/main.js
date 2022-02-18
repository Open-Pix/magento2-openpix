(function () {
  document.addEventListener('DOMContentLoaded', function () {
    const script = document.createElement('script');

    script.id = 'plugin';
    script.src =
      'http://localhost:4444/openpix.js?appID=Q2xpZW50X0lkX2RiMjVlYmY0LTZkOTQtNGY2ZS1hYzdkLTFhOGI3Y2ZlOGY2YTpDbGllbnRfU2VjcmV0X3IvbU16R3p1bmpvSEVseVFFeUw1LzVtYU9TQW9PaXpFaTh1NFgzeHBxU2c9&correlationID=f4060b5e-0f20-4fdf-91b2-b99e01fe239e&node=openpix-order';
    script.async = true;
    script.onload = function () {
      console.log('script loaded, you can use it now.');
    };

    document.body.append(script);
  });
})();
