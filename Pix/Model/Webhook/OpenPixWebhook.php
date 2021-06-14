<?php
namespace OpenPix\Pix\Model;


class OpenPixWebhook {

    /**
     * {@inheritdoc}
     */
    public function processWebhook($correlationID)
    {
        return 'api GET return the $param ' . $correlationID;
    }
}
