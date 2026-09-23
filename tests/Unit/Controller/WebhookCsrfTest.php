<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../Pix/Controller/Index/Webhook.php';

/**
 * The webhook is the only controller exempt from the form key (CSRF) validation:
 * the store keeps the Magento CSRF protection everywhere else.
 */
class WebhookCsrfTest extends TestCase
{
    public function testWebhookIsExemptFromCsrfValidation()
    {
        $ref = new ReflectionClass(
            \OpenPix\Pix\Controller\Index\Webhook::class
        );
        $webhook = $ref->newInstanceWithoutConstructor();
        $request = $this->createMock(
            \Magento\Framework\App\RequestInterface::class
        );

        $this->assertInstanceOf(
            \Magento\Framework\App\CsrfAwareActionInterface::class,
            $webhook
        );
        $this->assertTrue($webhook->validateForCsrf($request));
        $this->assertNull($webhook->createCsrfValidationException($request));
    }

    public function testCsrfValidationIsNotDisabledStoreWide()
    {
        $di = file_get_contents(__DIR__ . '/../../../Pix/etc/di.xml');

        $this->assertStringNotContainsString(
            'Magento\Framework\App\Request\CsrfValidator',
            $di
        );
    }
}
