<?php

namespace OpenPix\Pix\Block\Order\Payment;

class Info extends \Magento\Payment\Block\Info
{
    protected $_checkoutSession;
    protected $_orderFactory;

    /**
     * OpenPix Helper
     *
     * @var OpenPix\Pix\Helper\Data;
     */
    protected $_helperData;

    const LOG_NAME = 'pix_sales_order_block';

    /**
     * @var string
     */
    protected $_template = 'OpenPix_Pix::order/payment/info.phtml';

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\Order $orderFactory,
        \OpenPix\Pix\Helper\Data $helper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_checkoutSession = $checkoutSession;
        $this->_orderFactory = $orderFactory;
        $this->_helperData = $helper;
    }

    public function getOrder()
    {
        try {
            $order_id = $this->getRequest()->getParam('order_id');
            if (empty($order_id)) {
                $info = $this->getInfo();
                if (!empty($info)) {
                    return $info->getOrder();
                }
            }

            return $this->_orderFactory->load($order_id);
        } catch (\Exception $exception) {
            return null;
        }
    }

    public function getPaymentMethod()
    {
        $order = $this->getOrder();
        if (empty($order) || !$order->getId()) {
            return null;
        }

        $payment = $order->getPayment();

        $this->_helperData->log(
            'Pix::Block - Sales Order getPaymentMethod',
            self::LOG_NAME,
            $payment->getMethod()
        );
        return $payment->getMethod();
    }

    public function getPaymentInfo()
    {
        $order = $this->getOrder();
        if (empty($order) || !$order->getId()) {
            return null;
        }

        return [
            'tipo' => 'Pix',
            'qrcodeimage' => $order->getOpenpixQrcodeimage(),
            'text' => __('Clique aqui para ver seu QRCode.'),
            'brcode' => $order->getOpenpixBrcode(),
        ];
    }

    public function getAppID(): string
    {
        $appID = $this->_helperData->getAppID();

        if (isset($appID)) {
            return $appID;
        }

        return '';
    }

    public function getCorrelationID(): string
    {
        $order = $this->getOrder();
        if (empty($order) || !$order->getId()) {
            return '';
        }

        $correlationID = $order->getOpenpixCorrelationid();

        if (isset($correlationID)) {
            return $correlationID;
        }

        return '';
    }

    public function getPluginSrc(): string
    {
        return $this->_helperData->getOpenPixPluginUrlScript();
    }

    public function isSendEmail()
    {
        return $this->getIsSendingEmail() !== null &&
            $this->getIsSendingEmail() == 1;
    }
}
