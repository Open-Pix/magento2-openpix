<?php

namespace OpenPix\Pix\Model;

use \Magento\Config\Model\Config\CommentInterface;

class WebhookKey implements CommentInterface
{
    public function __construct(
        \Magento\Framework\UrlInterface $urlInterface
    ) {
        $this->urlInterface = $urlInterface;
    }

    public function getCommentText($elementValue)
    {
        return sprintf(
            __("Use this key as Webhook Authorization value on OpenPix side when creating a new Plugin for Magento")
        ) .
        " <strong>" .
        $elementValue .
        "</strong>";
    }
}
