<?php

namespace OpenPix\Pix\Block\Order\Payment;

class Info extends \Magento\Framework\View\Element\Template {
  protected $_checkoutSession;
  protected $_orderFactory;

    /**
     * OpenPix Helper
     *
     * @var OpenPix\Pix\Helper\Data;
     */
    protected $_helperData;

    const LOG_NAME = 'pix_sales_order_block';

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

  public function getPaymentMethod() {
    $order_id = $this->getRequest()->getParam('order_id');
    $order = $this->_orderFactory->load($order_id);
    $payment = $order->getPayment();

    $this->_helperData->log('Pix::Block - Sales Order getPaymentMethod', self::LOG_NAME, $payment->getMethod());
    return $payment->getMethod();
  }

  public function getPaymentInfo() {
    $order_id = $this->getRequest()->getParam('order_id');
    $order = $this->_orderFactory->load($order_id);

      return array(
            'tipo' => 'Pix',
            'qrcodeimage' => $order->getOpenpixQrcodeimage(),
            'text' => 'Clique aqui para ver seu QRCode.',
            'brcode' => $order->getOpenpixBrcode()
          );
  }
}
