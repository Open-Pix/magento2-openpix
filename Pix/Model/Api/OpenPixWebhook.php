<?php

namespace OpenPix\Pix\Model\Api;

use OpenPix\Pix\Api\Data\OpenPixChargeInterface;
use OpenPix\Pix\Api\Data\PixTransactionInterface;
use OpenPix\Pix\Api\OpenPixWebhookInterface;

class OpenPixWebhook implements OpenPixWebhookInterface {
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

    public function isValidWebhookPayload($charge, $pix = null)
    {
        if (!isset($charge) || !isset($charge->correlationId)) {
            return false;
        }

        if (!isset($pix) || !isset($pix->endToEndId)) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function processWebhook(OpenPixChargeInterface $charge = null, PixTransactionInterface $pix = null, string $evento = null): string
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

        if(!$this->isValidWebhookPayload($charge, $pix)) {
            $this->_helperData->log('OpenPix WebApi::ProcessWebhook Invalid Payload', self::LOG_NAME);

            $response = [
                'error' => 'Invalid Webhook Payload',
            ];

            echo json_encode($response);

            exit();
        }

        // @todo prepare fields -> correlationId, status, endToEndId

        // @todo get order_id by correlationId

        // @todo get order by id

        // @todo check if order has correlation id, if have return error order already paid

        $response = [
            'message' => 'api process webhook being built',
        ];

        echo json_encode($response);
        exit();
    }
}
