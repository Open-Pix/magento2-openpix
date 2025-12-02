<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../Pix/Helper/WebhookHandler.php';

class WebhookHandlerTest extends TestCase
{
    private function createWebhookHandler(
        $chargePaid = null,
        $chargeExpired = null,
        $configureHandler = null,
        $remoteAddress = null,
        $helperData = null
    ) {
        $remoteAddress =
            $remoteAddress ??
            $this->createMock(
                \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress::class
            );
        $remoteAddress->method('getRemoteAddress')->willReturn('127.0.0.1');

        $chargePaid =
            $chargePaid ??
            $this->createMock(
                \OpenPix\Pix\Helper\WebHookHandlers\ChargePaid::class
            );
        $chargeExpired =
            $chargeExpired ??
            $this->createMock(
                \OpenPix\Pix\Helper\WebHookHandlers\ChargeExpired::class
            );
        $configureHandler =
            $configureHandler ??
            $this->createMock(
                \OpenPix\Pix\Helper\WebHookHandlers\ConfigureHandler::class
            );
        $helper =
            $helperData ?? $this->createMock(\OpenPix\Pix\Helper\Data::class);
        $resultJsonFactory = $this->createMock(
            \Magento\Framework\Controller\Result\JsonFactory::class
        );

        $ref = new ReflectionClass(\OpenPix\Pix\Helper\WebhookHandler::class);
        $instance = $ref->newInstanceWithoutConstructor();

        $remoteAddressProp = $ref->getProperty('remoteAddress');
        $remoteAddressProp->setAccessible(true);
        $remoteAddressProp->setValue($instance, $remoteAddress);

        $chargePaidProp = $ref->getProperty('chargePaid');
        $chargePaidProp->setAccessible(true);
        $chargePaidProp->setValue($instance, $chargePaid);

        $chargeExpiredProp = $ref->getProperty('chargeExpired');
        $chargeExpiredProp->setAccessible(true);
        $chargeExpiredProp->setValue($instance, $chargeExpired);

        $configureHandlerProp = $ref->getProperty('configureHandler');
        $configureHandlerProp->setAccessible(true);
        $configureHandlerProp->setValue($instance, $configureHandler);

        $helperProp = $ref->getProperty('_helperData');
        $helperProp->setAccessible(true);
        $helperProp->setValue($instance, $helper);

        return $instance;
    }

    public function testIsValidWebhookPayloadReturnsTrueForValidPayload()
    {
        $handler = $this->createWebhookHandler();
        $validPayload = [
            'charge' => ['correlationID' => 'test123'],
            'pix' => ['endToEndId' => 'E123456789'],
        ];

        $this->assertTrue($handler->isValidWebhookPayload($validPayload));
    }

    public function testIsValidWebhookPayloadReturnsFalseWhenMissingCharge()
    {
        $handler = $this->createWebhookHandler();
        $invalidPayload = [
            'pix' => ['endToEndId' => 'E123456789'],
        ];

        $this->assertFalse($handler->isValidWebhookPayload($invalidPayload));
    }

    public function testIsValidWebhookPayloadReturnsFalseWhenMissingPix()
    {
        $handler = $this->createWebhookHandler();
        $invalidPayload = [
            'charge' => ['correlationID' => 'test123'],
        ];

        $this->assertFalse($handler->isValidWebhookPayload($invalidPayload));
    }

    public function testIsChargeExpiredPayloadReturnsTrueForExpiredCharge()
    {
        $handler = $this->createWebhookHandler();
        $expiredPayload = [
            'charge' => [
                'correlationID' => 'test123',
                'status' => 'EXPIRED',
            ],
        ];

        $this->assertTrue($handler->isChargeExpiredPayload($expiredPayload));
    }

    public function testIsChargeExpiredPayloadReturnsFalseForNonExpired()
    {
        $handler = $this->createWebhookHandler();
        $activePayload = [
            'charge' => [
                'correlationID' => 'test123',
                'status' => 'ACTIVE',
            ],
        ];

        $this->assertFalse($handler->isChargeExpiredPayload($activePayload));
    }

    public function testIsPixDetachedPayloadReturnsTrueWhenNoCharge()
    {
        $handler = $this->createWebhookHandler();
        $detachedPayload = [
            'pix' => ['endToEndId' => 'E123456789'],
        ];

        $this->assertTrue($handler->isPixDetachedPayload($detachedPayload));
    }

    public function testHandleTestWebhookReturnsSuccess()
    {
        $handler = $this->createWebhookHandler();
        $testWebhook = json_encode(['evento' => 'teste_webhook']);

        $result = $handler->handle($testWebhook);

        $this->assertNull($result['error']);
        $this->assertStringContainsString(
            'Webhook Test Call',
            $result['success']
        );
    }

    public function testHandleConfigureEventCallsConfigureHandler()
    {
        $configureHandler = $this->createMock(
            \OpenPix\Pix\Helper\WebHookHandlers\ConfigureHandler::class
        );
        $configureHandler
            ->expects($this->once())
            ->method('configure')
            ->with('test-app-id')
            ->willReturn(['error' => null, 'success' => 'success']);

        $handler = $this->createWebhookHandler(null, null, $configureHandler);
        $configureWebhook = json_encode([
            'evento' => 'magento2-configure',
            'appID' => 'test-app-id',
        ]);

        $result = $handler->handle($configureWebhook);

        $this->assertEquals('success', $result['success']);
    }

    public function testHandleChargeExpiredCallsChargeExpiredHandler()
    {
        $chargeExpired = $this->createMock(
            \OpenPix\Pix\Helper\WebHookHandlers\ChargeExpired::class
        );
        $chargeExpired
            ->expects($this->once())
            ->method('chargeExpired')
            ->willReturn(['error' => null, 'success' => 'expired']);

        $handler = $this->createWebhookHandler(null, $chargeExpired);
        $expiredWebhook = json_encode([
            'evento' => 'OPENPIX:CHARGE_EXPIRED', // add event
            'charge' => [
                'correlationID' => 'test123',
                'status' => 'EXPIRED',
            ],
        ]);

        $result = $handler->handle($expiredWebhook);

        $this->assertEquals('expired', $result['success']);
    }
}
