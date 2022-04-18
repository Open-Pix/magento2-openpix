<?php

namespace OpenPix\Pix\Block\Checkout;

use Magento\Backup\Helper\Data;

class Success extends \Magento\Sales\Block\Order\Totals
{
    protected \Magento\Checkout\Model\Session $checkoutSession;
    protected \Magento\Customer\Model\Session $customerSession;
    protected \Magento\Sales\Model\OrderFactory $_orderFactory;

    /**
     * OpenPix Helper
     *
     * @var OpenPix\Pix\Helper\Data;
     */
    protected $_helperData;

    const LOG_NAME = 'pix_checkout_success_block';

    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \OpenPix\Pix\Helper\Data $helper,
        array $data = []
    ) {
        parent::__construct($context, $registry, $data);
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->_orderFactory = $orderFactory;
        $this->_helperData = $helper;
    }

    public function getOrder(): ?\Magento\Sales\Model\Order
    {
        $order = $this->_order = $this->_orderFactory
            ->create()
            ->loadByIncrementId($this->checkoutSession->getLastRealOrderId());

        $paymentLinkUrl = $order->getOpenpixPaymentlinkurl();
        $brCodeImage = $order->getOpenpixQrcodeimage();
        $brCode = $order->getOpenpixBrcode();

        $this->_helperData->log(
            'Pix::Block - Checkout Success $paymentLinkUrl',
            self::LOG_NAME,
            $paymentLinkUrl
        );
        $this->_helperData->log(
            'Pix::Block - Checkout Success $brCodeImage',
            self::LOG_NAME,
            $brCodeImage
        );
        $this->_helperData->log(
            'Pix::Block - Checkout Success $brCode',
            self::LOG_NAME,
            $brCode
        );

        return $order;
    }

    public function getAppID(): string
    {
        return $this->_helperData->getAppID();
    }

    public function getPluginSrc(): string
    {
        $order = $this->getOrder();
        $pluginUrl = $this->_helperData->getOpenPixPluginUrlScript();
        $appID = $this->_helperData->getAppID();
        $correlationID = $order->getOpenpixCorrelationid();

        $queryString = "appID={$appID}&correlationID={$correlationID}&node=openpix-order";
        $src = "$pluginUrl?$queryString";

        $this->_helperData->log(
            'Pix::Block - Checkout Success $src',
            self::LOG_NAME,
            $src
        );

        return "$pluginUrl?$queryString";
    }

    public function getCustomerId()
    {
        return $this->customerSession->getCustomer()->getId();
    }
}
