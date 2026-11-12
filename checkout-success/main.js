(function () {
  document.addEventListener('DOMContentLoaded', function () {
    const script = document.createElement('script');

    script.id = 'plugin';
    script.src =
      'http://localhost:4444/openpix.js?appID=<appID>&correlationID=f4060b5e-0f20-4fdf-91b2-b99e01fe239e&node=openpix-order';
    script.async = true;
    script.onload = function () {
      console.log('script loaded, you can use it now.');
    };

    document.body.append(script);
  });
})();
