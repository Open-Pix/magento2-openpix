<?php

namespace OpenPix\Pix\Model;

use Magento\Config\Model\Config\CommentInterface;

class OneclickConfigButtonComment implements CommentInterface
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
                'By pressing this button, you will be redirected to our platform where we will quickly configure a new integration. Or use the url below to set up the Webhook on OpenPix Platform.'
            )
        ) .
            ' <strong>' .
            $this->urlInterface->getBaseUrl() .
            'openpix/index/webhook' .
            '</strong>';
    }
}
