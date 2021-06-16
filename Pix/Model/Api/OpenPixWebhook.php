<?php

namespace OpenPix\Pix\Model\Api;

use OpenPix\Pix\Api\Data\OpenPixChargeInterface;
use OpenPix\Pix\Api\Data\PixTransactionInterface;
use OpenPix\Pix\Api\OpenPixWebhookInterface;

class OpenPixWebhook implements OpenPixWebhookInterface {
    const MODULE_NAME = 'OpenPix_Pix';

    protected $_moduleList;

    /**
     * OpenPix Helper
     *
     * @var OpenPix\Pix\Helper\Data;
     */
    protected $_helperData;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    protected $orderCollectionFactory;


    const LOG_NAME = 'pix_webpapi';

    public function __construct(
        \OpenPix\Pix\Helper\Data $helper,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
    )
    {
        $this->_helperData = $helper;
        $this->orderCollectionFactory = $orderCollectionFactory;
    }

    public function isValidTestWebhookPayload($evento)
    {
        if (isset($evento)) {
            return true;
        }

        return false;
    }

    public function isValidWebhookPayload($charge, $pix = null)
    {
        if (!isset($charge) || !isset($charge->correlationId)) {
            return false;
        }

        if (!isset($pix) || !isset($pix->endToEndId)) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function processWebhook(OpenPixChargeInterface $charge = null, PixTransactionInterface $pix = null, string $evento = null): string
    {
        $this->_helperData->log('OpenPix WebApi::ProcessWebhook Start', self::LOG_NAME);
        $this->_helperData->log('OpenPix WebApi::ProcessWebhook Charge', self::LOG_NAME, $charge);
        $this->_helperData->log('OpenPix WebApi::ProcessWebhook Pix Transaction', self::LOG_NAME, $pix);

        // @todo validate authorization

        if($this->isValidTestWebhookPayload($evento)) {
            $this->_helperData->log('OpenPix WebApi::ProcessWebhook Test Call', self::LOG_NAME, $evento);

            $response = [
                'message' => 'success',
            ];

            echo json_encode($response);

            exit();
        }

        if(!$this->isValidWebhookPayload($charge, $pix)) {
            $this->_helperData->log('OpenPix WebApi::ProcessWebhook Invalid Payload', self::LOG_NAME);

            $response = [
                'error' => 'Invalid Webhook Payload',
            ];

            echo json_encode($response);

            exit();
        }

        // @todo prepare fields -> correlationId, status, endToEndId

        // @todo get order_id by correlationId
        $order = $this->getOrderByCorrelationID($charge->correlationId);

        $this->_helperData->log('OpenPix WebApi::getOrderByCorrelationID Order', self::LOG_NAME, $order);

        // @todo get order by id

        // @todo check if order has correlation id, if have return error order already paid

        $response = [
            'message' => 'api process webhook being built',
        ];

        echo json_encode($response);
        exit();
    }

    /**
     * @param $correlationId
     *
     * @return \Magento\Framework\Controller\ResultInterface|\Magento\Sales\Model\Order
     *
     * @throws LocalizedException
     */
    private function getOrderByCorrelationID($correlationId)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->orderCollectionFactory->create()->addFieldToFilter('openpix_correlationid', ["eq" => $correlationId])->getFirstItem();

        echo '$order: ' . json_encode($order);
        echo PHP_EOL;

        /**
         * If the order is empty it means that this order ID does not exist.
         */
        if (!$order) {
            $this->_helperData->log('OpenPix WebApi::getOrderByCorrelationID This order does not exist.', self::LOG_NAME);
            throw new LocalizedException(__('This order does not exist.'), self::RESULT_NOT_FOUND);
        }

        return $order;
    }
}
