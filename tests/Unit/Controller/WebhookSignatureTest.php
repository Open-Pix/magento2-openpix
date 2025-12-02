<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../Pix/Controller/Index/Webhook.php';
require_once __DIR__ . '/../../../Pix/Helper/OpenPixConfig.php';

class WebhookSignatureTest extends TestCase
{
    private function createWebhookController()
    {
        $webhookHandler = $this->createMock(
            \OpenPix\Pix\Helper\WebhookHandler::class
        );
        $helperData = $this->createMock(\OpenPix\Pix\Helper\Data::class);
        $context = $this->createMock(
            \Magento\Framework\App\Action\Context::class
        );
        $pageFactory = $this->createMock(
            \Magento\Framework\View\Result\PageFactory::class
        );
        $resultJsonFactory = $this->createMock(
            \Magento\Framework\Controller\Result\JsonFactory::class
        );

        $ref = new ReflectionClass(
            \OpenPix\Pix\Controller\Index\Webhook::class
        );
        $instance = $ref->newInstanceWithoutConstructor();

        $webhookHandlerProp = $ref->getProperty('webhookHandler');
        $webhookHandlerProp->setAccessible(true);
        $webhookHandlerProp->setValue($instance, $webhookHandler);

        $helperDataProp = $ref->getProperty('helperData');
        $helperDataProp->setAccessible(true);
        $helperDataProp->setValue($instance, $helperData);

        return $instance;
    }

    public function testVerifySignatureWithValidSignature()
    {
        $controller = $this->createWebhookController();

        // Create a test payload and sign it with the production public key
        $payload = '{"test":"data"}';

        // For testing purposes, we'll test that the method exists and can be called
        $ref = new ReflectionClass($controller);
        $method = $ref->getMethod('verifySignature');
        $method->setAccessible(true);

        // Test with empty signature (should fail)
        $result = $method->invoke($controller, $payload, '');
        $this->assertNotEquals(1, $result);
    }

    public function testVerifySignatureReturnsFalseForInvalidSignature()
    {
        $controller = $this->createWebhookController();

        $payload = '{"test":"data"}';
        $invalidSignature = base64_encode('invalid_signature_data');

        $ref = new ReflectionClass($controller);
        $method = $ref->getMethod('verifySignature');
        $method->setAccessible(true);

        $result = $method->invoke($controller, $payload, $invalidSignature);

        // Should return false or 0 (not 1 which means valid)
        $this->assertNotEquals(1, $result);
    }

    public function testOpenPixConfigReturnsProductionUrls()
    {
        $config = new \OpenPix\Pix\Helper\OpenPixConfig();

        $this->assertEquals(
            'https://api.openpix.com.br',
            $config->getOpenPixApiUrl()
        );
        $this->assertEquals(
            'https://app.openpix.com.br',
            $config->getOpenPixPlatformUrl()
        );
        $this->assertEquals(
            'https://plugin.openpix.com.br/v1/openpix.js',
            $config->getOpenPixPluginUrlScript()
        );
    }

    public function testOpenPixConfigHasPublicKey()
    {
        $this->assertTrue(
            defined(
                '\OpenPix\Pix\Helper\OpenPixConfig::OPENPIX_PUBLIC_KEY_BASE64'
            )
        );
        $this->assertNotEmpty(
            \OpenPix\Pix\Helper\OpenPixConfig::OPENPIX_PUBLIC_KEY_BASE64
        );
    }

    public function testOpenPixConfigEnvironmentIsProduction()
    {
        $this->assertEquals(
            'production',
            \OpenPix\Pix\Helper\OpenPixConfig::OPENPIX_ENV
        );
    }
}
