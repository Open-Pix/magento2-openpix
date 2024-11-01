<?php

namespace OpenPix\Pix\Block\Multishipping\Checkout;

class Success extends \Magento\Multishipping\Block\Checkout\Success
{
    protected $checkoutSession;
    protected $customerSession;
    protected $_orderFactory;
    protected $_helperData;

    const LOG_NAME = 'pix_multishipping_checkout_success_block';

    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Multishipping\Model\Checkout\Type\Multishipping $multishipping,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \OpenPix\Pix\Helper\Data $helper,
        array $data = [],
    ) {
        parent::__construct($context, $multishipping, $data);

        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->_helperData = $helper;
        $this->_orderFactory = $orderFactory;
    }

    public function getQrCodes()
    {
        $orderIds = $this->_session->getOrderIds();

        if (! $orderIds || ! is_array($orderIds)) {
            return [];
        }

        $qrCodes = [];

        foreach ($orderIds as $orderId) {
            $order = $this->_orderFactory->create()
                ->loadByIncrementId($orderId);

            if (! $order->getId()) {
                continue;
            }

            $correlationID = $order->getOpenpixCorrelationid();

            if (empty($correlationID)) {
                continue;
            }

            $qrCodes[] = [
                'paymentLinkUrl' => $order->getOpenpixPaymentlinkurl(),
                'brCodeImage' => $order->getOpenpixQrcodeimage(),
                'brCode' => $order->getOpenpixBrcode(),
                'correlationID' => $correlationID,
            ];
        }

        return $qrCodes;
    }

    public function getAppID(): string
    {
        $appID = $this->_helperData->getAppID();

        if (isset($appID)) {
            return $appID;
        }

        return '';
    }

    public function getPluginSrc(): string
    {
        return $this->_helperData->getOpenPixPluginUrlScript();
    }

    public function getCustomerId()
    {
        return $this->customerSession->getCustomer()->getId();
    }
}
