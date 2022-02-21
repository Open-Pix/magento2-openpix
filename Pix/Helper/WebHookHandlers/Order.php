<?php

namespace OpenPix\Pix\Helper\WebHookHandlers;

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
     * @param array $charge
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder($charge)
    {
        if (!isset($charge['correlationID'])) {
            return false;
        }

        $order = $this->getOrderByCorrelationID($charge['correlationID']);

        if (!$order || !$order->getId()) {
            $this->logger->warning(
                __(
                    sprintf(
                        'No order was found to invoice: %d',
                        $charge['correlationID']
                    )
                )
            );

            return false;
        }

        return $order;
    }

    /**
     * @param int $correlationID
     *
     * @return \Magento\Sales\Model\Order
     */
    private function getOrderByCorrelationID($correlationID)
    {
        if (!$correlationID) {
            return false;
        }

        $order = $this->orderCollectionFactory
            ->create()
            ->addAttributeToFilter('openpix_correlationid', [
                'eq' => $correlationID,
            ])
            ->getFirstItem();

        if (!$order) {
            return false;
        }

        return $order;
    }
}
