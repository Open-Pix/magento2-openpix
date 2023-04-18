<?php

namespace OpenPix\Pix\Helper\WebHookHandlers;

use Magento\Sales\Model\Order\Invoice;
use OpenPix\Pix\Helper\Data;

class ChargePaid
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected \Psr\Log\LoggerInterface $logger;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var \Magento\Sales\Api\InvoiceRepositoryInterface
     */
    protected $invoiceRepository;

    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\InvoiceSender
     */
    protected $invoiceSender;

    /**
     * @var Order
     */
    protected $order;

    /**
     * @var Data
     */
    protected $_helperData;

    protected $messageManager;

    const LOG_NAME = 'charge_paid';

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Sales\Api\InvoiceRepositoryInterface $invoiceRepository,
        \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender,
        Order $order,
        Data $_helperData
    ) {
        $this->logger = $logger;
        $this->orderRepository = $orderRepository;
        $this->invoiceRepository = $invoiceRepository;
        $this->invoiceSender = $invoiceSender;
        $this->order = $order;
        $this->_helperData = $_helperData;
    }

    /**
     * Handle 'charge_paid' event.
     * The charge can be related to a subscription or a single payment.
     *
     * @param array $charge
     * @param array $pix
     *
     * @return bool
     */
    public function chargePaid($charge, $pix)
    {
        $this->_helperData->log('OpenPix::chargePaid Start', self::LOG_NAME);

        if (!($order = $this->order->getOrder($charge))) {
            $this->_helperData->log(
                'OpenPix::chargePaid Order Not Found',
                self::LOG_NAME
            );

            $this->logger->error(__(sprintf('Order Not Found')));

            return ['error' => 'Order Not Found', 'success' => null];
        }

        $hasEndToEndId = $this->hasEndToEndId($order);

        if ($hasEndToEndId) {
            $this->_helperData->log(
                'OpenPix::chargePaid Order Already Invoiced',
                self::LOG_NAME
            );

            return ['error' => 'Order Already Invoiced', 'success' => null];
        }

        return $this->createInvoice($order, $pix);
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
     * @return bool
     */
    public function createInvoice(\Magento\Sales\Model\Order $order, $pix)
    {
        if (!$order->getId()) {
            return ['error' => 'Order Not Found', 'success' => null];
        }

        if (!$order->canInvoice()) {
            $this->logger->error(
                __(
                    sprintf(
                        'Impossible to generate invoice for order %s.',
                        $order->getId()
                    )
                )
            );
            return [
                'error' => sprintf(
                    'Impossible to generate invoice for order %s.',
                    $order->getId()
                ),
                'success' => null,
            ];
        }

        $this->_helperData->log(
            "Generating invoice for the order {$order->getId()}.",
            self::LOG_NAME
        );
        $this->logger->info(
            __(sprintf('Generating invoice for the order %s.', $order->getId()))
        );

        $invoice = $order->prepareInvoice();
        $invoice->setRequestedCaptureCase(Invoice::CAPTURE_OFFLINE);
        $invoice->register();
        $invoice->setSendEmail(true);
        $invoice->setTransactionId($pix['charge']['correlationID']);

        $this->invoiceRepository->save($invoice);

        try {
            $this->invoiceSender->send($invoice);
        } catch (\Exception $e) {
            $this->_helperData->log(
                'We can\'t send the invoice email right now.',
                self::LOG_NAME
            );
            $this->messageManager->addError(
                __('We can\'t send the invoice email right now.')
            );
        }

        $order->setOpenpixEndtoendid($pix['endToEndId']);

        $this->_helperData->log('Invoice created with success', self::LOG_NAME);
        $this->logger->info(__('Invoice created with success'));

        $order->addStatusHistoryComment(
            __(
                'The payment was confirmed by OpenPix and the order is being processed'
            ),
            $order
                ->getConfig()
                ->getStateDefaultStatus(
                    \Magento\Sales\Model\Order::STATE_PROCESSING
                )
        );

        $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);

        $this->orderRepository->save($order);
        return [
            'error' => null,
            'success' =>
                'The payment was confirmed by OpenPix and the order is being processed',
        ];
    }
}
