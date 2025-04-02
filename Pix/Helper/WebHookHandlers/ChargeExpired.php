<?php

namespace OpenPix\Pix\Helper\WebHookHandlers;

use OpenPix\Pix\Helper\Data;

class ChargeExpired
{
    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var Order
     */
    protected $order;

    /**
     * @var Data
     */
    protected $_helperData;

    protected $messageManager;

    const LOG_NAME = 'charge_expired';

    public function __construct(
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        Order $order,
        Data $_helperData
    ) {
        $this->orderRepository = $orderRepository;
        $this->order = $order;
        $this->_helperData = $_helperData;
    }

    /**
     * Handle 'charge_expired' event.
     * The charge can be related to a subscription or a single payment.
     *
     * @param array $charge
     *
     * @return bool
     */
    public function chargeExpired($charge)
    {
        $this->_helperData->log('OpenPix::chargeExpired Start', self::LOG_NAME);

        if (!($order = $this->order->getOrder($charge))) {
            $this->_helperData->log(
                'OpenPix::chargeExpired Order Not Found',
                self::LOG_NAME
            );

            $this->_helperData->log(__(sprintf('Order Not Found')));

            return ['error' => 'Order Not Found', 'success' => null];
        }

        $hasEndToEndId = $this->hasEndToEndId($order);

        if ($hasEndToEndId) {
            $this->_helperData->log(
                'OpenPix::chargeExpired Order Already Expired',
                self::LOG_NAME
            );

            return ['error' => 'Order Already Expired', 'success' => null];
        }

        return $this->updateStatus($order, $charge);
    }

    public function hasEndToEndId(\Magento\Sales\Model\Order $order): bool
    {
        $hasEndToEndId = $order->getData('openpix_endtoendid');

        if (isset($hasEndToEndId)) {
            return true;
        }

        return false;
    }

    /**
     * create expired invoice
     * @return bool
     */
    public function updateStatus(\Magento\Sales\Model\Order $order, $charge)
    {
        if (!$order->getId()) {
            return ['error' => 'Order Not Found', 'success' => null];
        }

        $this->_helperData->log(
            "Generating invoice for the order {$order->getId()}.",
            self::LOG_NAME
        );
        $this->_helperData->log(
            __(sprintf('Generating invoice for the order %s.', $order->getId()))
        );

        $order->setOpenpixEndtoendid('');

        $order->addStatusHistoryComment(
            __(
                'The payment was expired by OpenPix and the order is canceled'
            ),
            $order
                ->getConfig()
                ->getStateDefaultStatus(
                    \Magento\Sales\Model\Order::STATE_CANCELED
                )
        );

        $order->setState(\Magento\Sales\Model\Order::STATE_CANCELED);

        $this->orderRepository->save($order);
        return [
            'error' => null,
            'success' =>
                'The payment was expired by OpenPix and the order is canceled',
        ];
    }
}
