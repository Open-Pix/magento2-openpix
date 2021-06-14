<?php

namespace OpenPix\Pix\Api;

interface OpenPixWebhookInterface {
    /**
     * POST for OpenPix Webhook
     * @param string $param
     * @return string
     */

    public function processWebhook($param);
}
