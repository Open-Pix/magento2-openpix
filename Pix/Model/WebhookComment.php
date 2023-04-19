<?php

namespace OpenPix\Pix\Model;

use Magento\Config\Model\Config\CommentInterface;

class WebhookComment implements CommentInterface
{
    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlInterface;

    public function __construct(\Magento\Framework\UrlInterface $urlInterface)
    {
        $this->urlInterface = $urlInterface;
    }

    public function getCommentText($elementValue)
    {
        return sprintf(
            __(
                'Or use the url below to set up the Webhook on OpenPix Platform.'
            )
        ) .
            ' <strong>' .
            $this->urlInterface->getBaseUrl() .
            'openpix/index/webhook' .
            '</strong>';
    }
}
