<?php

namespace OpenPix\PixHelper\WebHookHandlers;

class Order
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    protected $orderCollectionFactory;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
    ) {
        $this->logger = $logger;
        $this->orderCollectionFactory = $orderCollectionFactory;
    }

    /**
     * @param array $data
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder($data)
    {
        if (!isset($data['bill'])) {
            return false;
        }

        $order = $this->getOrderByBillId($data['bill']['id']);

        if (!$order || !$order->getId()) {
            $this->logger->warning(__(sprintf('No order was found to invoice: %d', $data['bill']['id'])));

            return false;
        }

        return $order;
    }

    /**
     * @param int $correlationID
     *
     * @return \Magento\Sales\Model\Order
     */
    private function getOrderByBillId($correlationID)
    {
        if (!$correlationID) {
            return false;
        }

        $order = $this->orderCollectionFactory->create()
            ->addAttributeToFilter('openpix_correlationid', ['eq' => $correlationID])
            ->getFirstItem();

        if (!$order) {
            return false;
        }

        return $order;
    }
}
