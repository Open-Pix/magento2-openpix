<?php
namespace OpenPix\Pix\Model;


class OpenPixWebhook {

    /**
     * {@inheritdoc}
     */
    public function processWebhook($param)
    {
        return 'api GET return the $param ' . $param;
    }
}
