<?php

namespace OpenPix\Pix\Controller\Index;

class Webhook extends \Magento\Framework\App\Action\Action
{
    const LOG_NAME = 'pix_checkout';
    protected $helper;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \OpenPix\Pix\Helper\Data $helper
    ) {
        $this->helper = helper;

        return parent::__construct($context);
    }

    /**
     * The route that webhooks will use.
     */
    public function execute()
    {
        $body = file_get_contents('php://input');

        $this->helper->log('Webhook Execute', self::LOG_NAME, $body);
    }
}
