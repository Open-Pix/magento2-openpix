<?php

namespace OpenPix\Pix\Model;

use Magento\Config\Model\Config\CommentInterface;

class WebhookKey implements CommentInterface
{
    public function __construct(\Magento\Framework\UrlInterface $urlInterface)
    {
        $this->urlInterface = $urlInterface;
    }

    public function getCommentText($elementValue)
    {
        return sprintf(
            __(
                'Use this authorization to set up the Webhook on OpenPix Platform and the url below.'
            )
        ) .
            ' <strong>' .
            $this->urlInterface->getBaseUrl() .
            'openpix/index/webhook' .
            '</strong>';
    }
}
