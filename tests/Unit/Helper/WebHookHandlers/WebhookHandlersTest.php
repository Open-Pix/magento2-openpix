<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../../Pix/Helper/WebHookHandlers/ChargePaid.php';
require_once __DIR__ .
    '/../../../../Pix/Helper/WebHookHandlers/ChargeExpired.php';
require_once __DIR__ .
    '/../../../../Pix/Helper/WebHookHandlers/ConfigureHandler.php';
require_once __DIR__ . '/../../../../Pix/Helper/WebHookHandlers/Order.php';

/**
 * Tests for OpenPix Webhook Event Handlers - Woovi features:
 * - ChargePaid: Invoice creation when payment is confirmed
 * - ChargeExpired: Order cancellation when payment expires
 * - ConfigureHandler: One-click setup with App ID
 * - Order: Finding orders by correlation ID
 */
class WebhookHandlersTest extends TestCase
{
    // ChargePaid Tests
    public function testChargePaidReturnsErrorWhenOrderNotFound()
    {
        $orderHelper = $this->createMock(
            \OpenPix\Pix\Helper\WebHookHandlers\Order::class
        );
        $orderHelper->method('getOrder')->willReturn(false);

        $orderRepo = $this->createMock(
            \Magento\Sales\Api\OrderRepositoryInterface::class
        );
        $invoiceRepo = $this->createMock(
            \Magento\Sales\Api\InvoiceRepositoryInterface::class
        );
        $invoiceSender = $this->createMock(
            \Magento\Sales\Model\Order\Email\Sender\InvoiceSender::class
        );
        $helperData = $this->createMock(\OpenPix\Pix\Helper\Data::class);

        $chargePaid = new \OpenPix\Pix\Helper\WebHookHandlers\ChargePaid(
            $orderRepo,
            $invoiceRepo,
            $invoiceSender,
            $orderHelper,
            $helperData
        );

        $result = $chargePaid->chargePaid(
            ['correlationID' => 'test'],
            ['endToEndId' => 'E123']
        );

        $this->assertEquals('Order Not Found', $result['error']);
        $this->assertNull($result['success']);
    }

    public function testChargePaidReturnsErrorWhenOrderAlreadyInvoiced()
    {
        $order = $this->createMock(\Magento\Sales\Model\Order::class);
        $order
            ->method('getData')
            ->with('openpix_endtoendid')
            ->willReturn('E12345678');

        $orderHelper = $this->createMock(
            \OpenPix\Pix\Helper\WebHookHandlers\Order::class
        );
        $orderHelper->method('getOrder')->willReturn($order);

        $orderRepo = $this->createMock(
            \Magento\Sales\Api\OrderRepositoryInterface::class
        );
        $invoiceRepo = $this->createMock(
            \Magento\Sales\Api\InvoiceRepositoryInterface::class
        );
        $invoiceSender = $this->createMock(
            \Magento\Sales\Model\Order\Email\Sender\InvoiceSender::class
        );
        $helperData = $this->createMock(\OpenPix\Pix\Helper\Data::class);

        $chargePaid = new \OpenPix\Pix\Helper\WebHookHandlers\ChargePaid(
            $orderRepo,
            $invoiceRepo,
            $invoiceSender,
            $orderHelper,
            $helperData
        );

        $result = $chargePaid->chargePaid(
            ['correlationID' => 'test'],
            ['endToEndId' => 'E123']
        );

        $this->assertEquals('Order Already Invoiced', $result['error']);
    }

    public function testHasEndToEndIdReturnsTrueWhenPresent()
    {
        $order = $this->createMock(\Magento\Sales\Model\Order::class);
        $order
            ->method('getData')
            ->with('openpix_endtoendid')
            ->willReturn('E12345678');

        $orderRepo = $this->createMock(
            \Magento\Sales\Api\OrderRepositoryInterface::class
        );
        $invoiceRepo = $this->createMock(
            \Magento\Sales\Api\InvoiceRepositoryInterface::class
        );
        $invoiceSender = $this->createMock(
            \Magento\Sales\Model\Order\Email\Sender\InvoiceSender::class
        );
        $orderHelper = $this->createMock(
            \OpenPix\Pix\Helper\WebHookHandlers\Order::class
        );
        $helperData = $this->createMock(\OpenPix\Pix\Helper\Data::class);

        $chargePaid = new \OpenPix\Pix\Helper\WebHookHandlers\ChargePaid(
            $orderRepo,
            $invoiceRepo,
            $invoiceSender,
            $orderHelper,
            $helperData
        );

        $this->assertTrue($chargePaid->hasEndToEndId($order));
    }

    public function testHasEndToEndIdReturnsFalseWhenNotPresent()
    {
        $order = $this->createMock(\Magento\Sales\Model\Order::class);
        $order
            ->method('getData')
            ->with('openpix_endtoendid')
            ->willReturn(null);

        $orderRepo = $this->createMock(
            \Magento\Sales\Api\OrderRepositoryInterface::class
        );
        $invoiceRepo = $this->createMock(
            \Magento\Sales\Api\InvoiceRepositoryInterface::class
        );
        $invoiceSender = $this->createMock(
            \Magento\Sales\Model\Order\Email\Sender\InvoiceSender::class
        );
        $orderHelper = $this->createMock(
            \OpenPix\Pix\Helper\WebHookHandlers\Order::class
        );
        $helperData = $this->createMock(\OpenPix\Pix\Helper\Data::class);

        $chargePaid = new \OpenPix\Pix\Helper\WebHookHandlers\ChargePaid(
            $orderRepo,
            $invoiceRepo,
            $invoiceSender,
            $orderHelper,
            $helperData
        );

        $this->assertFalse($chargePaid->hasEndToEndId($order));
    }

    // ChargeExpired Tests
    public function testChargeExpiredReturnsErrorWhenOrderNotFound()
    {
        $orderHelper = $this->createMock(
            \OpenPix\Pix\Helper\WebHookHandlers\Order::class
        );
        $orderHelper->method('getOrder')->willReturn(false);

        $orderRepo = $this->createMock(
            \Magento\Sales\Api\OrderRepositoryInterface::class
        );
        $helperData = $this->createMock(\OpenPix\Pix\Helper\Data::class);

        $chargeExpired = new \OpenPix\Pix\Helper\WebHookHandlers\ChargeExpired(
            $orderRepo,
            $orderHelper,
            $helperData
        );

        $result = $chargeExpired->chargeExpired(['correlationID' => 'test']);

        $this->assertEquals('Order Not Found', $result['error']);
    }

    public function testChargeExpiredReturnsErrorWhenAlreadyExpired()
    {
        $order = $this->createMock(\Magento\Sales\Model\Order::class);
        $order
            ->method('getData')
            ->with('openpix_endtoendid')
            ->willReturn('expired');

        $orderHelper = $this->createMock(
            \OpenPix\Pix\Helper\WebHookHandlers\Order::class
        );
        $orderHelper->method('getOrder')->willReturn($order);

        $orderRepo = $this->createMock(
            \Magento\Sales\Api\OrderRepositoryInterface::class
        );
        $helperData = $this->createMock(\OpenPix\Pix\Helper\Data::class);

        $chargeExpired = new \OpenPix\Pix\Helper\WebHookHandlers\ChargeExpired(
            $orderRepo,
            $orderHelper,
            $helperData
        );

        $result = $chargeExpired->chargeExpired(['correlationID' => 'test']);

        $this->assertEquals('Order Already Expired', $result['error']);
    }

    // ConfigureHandler Tests
    public function testConfigureReturnsErrorWhenAppIDAlreadyConfigured()
    {
        $helperData = $this->createMock(\OpenPix\Pix\Helper\Data::class);
        $helperData->method('getAppID')->willReturn('existing-app-id');

        $configureHandler = new \OpenPix\Pix\Helper\WebHookHandlers\ConfigureHandler(
            $helperData
        );

        $result = $configureHandler->configure('new-app-id');

        $this->assertEquals('App ID already configured', $result['error']);
        $this->assertNull($result['success']);
    }

    public function testConfigureSucceedsWhenNoExistingAppID()
    {
        $helperData = $this->createMock(\OpenPix\Pix\Helper\Data::class);
        $helperData->method('getAppID')->willReturn('');
        $helperData
            ->expects($this->once())
            ->method('setAppID')
            ->with('new-app-id', true);

        $configureHandler = new \OpenPix\Pix\Helper\WebHookHandlers\ConfigureHandler(
            $helperData
        );

        $result = $configureHandler->configure('new-app-id');

        $this->assertNull($result['error']);
        $this->assertEquals('success', $result['success']);
    }

    // Order Helper Tests
    public function testGetOrderReturnsFalseWhenCorrelationIDMissing()
    {
        $collectionFactory = $this->createMock(
            \Magento\Sales\Model\ResourceModel\Order\CollectionFactory::class
        );
        $helperData = $this->createMock(\OpenPix\Pix\Helper\Data::class);

        $orderHelper = new \OpenPix\Pix\Helper\WebHookHandlers\Order(
            $collectionFactory,
            $helperData
        );

        $result = $orderHelper->getOrder(['no_correlation_id' => 'test']);

        $this->assertFalse($result);
    }
}
