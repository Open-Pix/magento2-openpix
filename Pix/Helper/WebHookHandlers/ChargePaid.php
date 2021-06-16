<?php

namespace OpenPix\Pix\Helper\WebHookHandlers;

use Magento\Sales\Model\Order\Invoice;
use OpenPix\Pix\Helper\Data;

class ChargePaid
{
    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var \Magento\Sales\Api\InvoiceRepositoryInterface
     */
    protected $invoiceRepository;


    const LOG_NAME = 'charge_paid';

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Sales\Api\InvoiceRepositoryInterface $invoiceRepository,
        Order $order,
        Data $_helperData
    ) {
        $this->logger = $logger;
        $this->orderRepository = $orderRepository;
        $this->invoiceRepository = $invoiceRepository;
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
            $this->_helperData->log('OpenPix::chargePaid Order Not Found', self::LOG_NAME);

            $this->logger->error(
                __(sprintf(
                    'Order Not Found'
                ))
            );

            header('HTTP/1.2 400 Bad Request');
            $response = [
                'error' => 'Order Not Found',
            ];

            return json_encode($response);
        }

        $hasEndToEndId = $this->hasEndToEndId($order);

        if($hasEndToEndId) {
            $this->_helperData->log('OpenPix::chargePaid Order Already Invoiced', self::LOG_NAME);

            header('HTTP/1.2 400 Bad Request');
            $response = [
                'error' => 'Order Already Invoiced',
            ];

            return json_encode($response);
        }

        return $this->createInvoice($order, $pix);
    }

    public function hasEndToEndId(\Magento\Sales\Model\Order $order): bool {
        $hasEndToEndId = $order->getData("openpix_endtoendid");

        if(isset($hasEndToEndId)) {
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
            return false;
        }

        if (!$order->canInvoice()) {
            $this->logger->error(__(sprintf('Impossible to generate invoice for order %s.', $order->getId())));

            return false;
        }

        $this->logger->info(__(sprintf('Generating invoice for the order %s.', $order->getId())));

        $invoice = $order->prepareInvoice();
        $invoice->setRequestedCaptureCase(Invoice::CAPTURE_OFFLINE);
        $invoice->register();
        $invoice->setSendEmail(true);
        $this->invoiceRepository->save($invoice);

        $order->setOpenpixEndtoendid($pix["endToEndId"]);

        $this->logger->info(__('Invoice created with success'));

        $order->addStatusHistoryComment(
            __('The payment was confirmed by OpenPix and the order is being processed')->getText(),
            $order->getConfig()->getStateDefaultStatus(\Magento\Sales\Model\Order::STATE_PROCESSING)
        );

        $this->orderRepository->save($order);

        return true;
    }
}
