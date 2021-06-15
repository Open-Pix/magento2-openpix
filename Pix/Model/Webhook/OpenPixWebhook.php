<?php

namespace OpenPix\Pix\Model\Webhook;

class OpenPixWebhook {
    const MODULE_NAME = 'OpenPix_Pix';

    protected $_moduleList;

    /**
     * OpenPix Helper
     *
     * @var OpenPix\Pix\Helper\Data;
     */
    protected $_helperData;


    const LOG_NAME = 'pix_webpapi';

    public function __construct(
        \OpenPix\Pix\Helper\Data $helper
    )
    {
        $this->_helperData = $helper;
    }

    public function isValidTestWebhookPayload($evento)
    {
        if (isset($evento)) {
            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function processWebhook($charge = null, $pix = null, $evento = null)
    {
        $this->_helperData->log('OpenPix WebApi::ProcessWebhook Start', self::LOG_NAME);
        $this->_helperData->log('OpenPix WebApi::ProcessWebhook Charge', self::LOG_NAME, $charge);
        $this->_helperData->log('OpenPix WebApi::ProcessWebhook Pix Transaction', self::LOG_NAME, $pix);

        // @todo validate authorization

        if($this->isValidTestWebhookPayload($evento)) {
            $this->_helperData->log('OpenPix WebApi::ProcessWebhook Test Call', self::LOG_NAME, $evento);

            $response = [
                'message' => 'success',
            ];

            echo json_encode($response);

            exit();
        }

        // @todo validate if is a valid webhook payload

        // @todo prepare fields -> correlationID, status, endToEndId

        // @todo get order_id by correlationID

        // @todo get order by id

        // @todo check if order has correlation id, if have return error order already paid

        return 'api process webhook';
    }
}
