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
        return true;
//        if (!($order = $this->order->getOrder($data))) {
//            $this->logger->error(
//                __(sprintf(
//                    'There is no cycle %s of signature %d.',
//                    $data['charge']['period']['cycle'],
//                    $data['charge']['subscription']['id']
//                ))
//            );
//
//            return false;
//        }
//
//        return $this->createInvoice($order);
    }

    /**
     * @return bool
     */
    public function createInvoice(\Magento\Sales\Model\Order $order)
    {
        if (!$order->getId()) {
            return false;
        }

        $this->logger->info(__(sprintf('Generating invoice for the order %s.', $order->getId())));

        if (!$order->canInvoice()) {
            $this->logger->error(__(sprintf('Impossible to generate invoice for order %s.', $order->getId())));

            return false;
        }

        $invoice = $order->prepareInvoice();
        $invoice->setRequestedCaptureCase(Invoice::CAPTURE_OFFLINE);
        $invoice->register();
        $invoice->setSendEmail(true);
        $this->invoiceRepository->save($invoice);
        $this->logger->info(__('Invoice created with success'));

        $order->addStatusHistoryComment(
            __('The payment was confirmed and the order is beeing processed')->getText(),
            $order->getConfig()->getStateDefaultStatus(\Magento\Sales\Model\Order::STATE_PROCESSING)
        );
        $this->orderRepository->save($order);

        return true;
    }
}
