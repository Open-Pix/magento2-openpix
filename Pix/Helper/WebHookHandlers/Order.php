<?php


namespace OpenPix\Pix\Helper\WebHookHandlers;
use OpenPix\Pix\Helper\Data;

class Order
{
    protected $_helperData;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    protected $orderCollectionFactory;

    public function __construct(
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        Data $_helperData
    ) {
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->_helperData = $_helperData;
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
            $this->_helperData->log(
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
