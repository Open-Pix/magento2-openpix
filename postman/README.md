# Magento2 Endpoint Postman Tests
This postman json contains 8 requests testing the endpoints of OpenPix on magento2 store

- GET Version
  ![GetVersion](./assets/get_version.png);
  
- POST Webhook Test
  ![test](./assets/test_webhook.png);
  
- POST Webhook Invalid Payload
  ![Invalid](./assets/invalid_payload.png);
  
- POST Webhook Order Already Invoiced
  ![Invoiced](./assets/order_invoiced.png);
  
- POST Webhook Order Not Found
  ![NotFound](./assets/order_not_found.png);
  
- POST Webhook Valid Payload
  ![Valid](./assets/valid_webhook.png);
  
- POST Webhook Pix Detached
  ![Detached](./assets/pix_detached.png);
  
- POST Webhook Pix Detached with Charge Null
  ![DetachedNull](./assets/pix_detached_charge_null.png);
  
- POST Webhook Pix Detached with Charge Empty Object
  ![DetachedEmpty](./assets/pix_detached_charge_empty.png);

## Using
When use import `magento2-local-webhook-tests.postman_collection.json` into your postman and change some values:

- URL: should be your store URL
- Authorization: should be the Webhook Authorization registered in you Store
- BODY: only on valid payload endpoint you must have a valid value from an order in your store waiting to be paid

## Fix
When fixing some endpoint remember to generate a new Postman.json and update it here.