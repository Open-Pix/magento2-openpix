<?php
namespace OpenPix\Pix\Controller\Index;

//use OpenPix\Pix\Model\Api;

class Index extends \Magento\Framework\App\Action\Action
{
    protected $_pageFactory;

    public function __construct(
//        \OpenPix\Pix\Model\Payment\Api $api,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory
    ) {
        $this->_pageFactory = $pageFactory;
//        $this->api = $api;
        return parent::__construct($context);
    }

    public function execute()
    {
        return $this->_pageFactory->create();
    }
}
