<?php

namespace OpenPix\Pix\Api;

interface OpenPixWebhookInterface {
    /**
     * POST for OpenPix Webhook
     * @param string $correlationID
     * @return string
     */

    public function processWebhook($correlationID);
}
