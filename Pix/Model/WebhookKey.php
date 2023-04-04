<?php

namespace OpenPix\Pix\Model;

use Magento\Config\Model\Config\CommentInterface;

class WebhookKey implements CommentInterface
{
    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected \Magento\Framework\UrlInterface $urlInterface;

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
