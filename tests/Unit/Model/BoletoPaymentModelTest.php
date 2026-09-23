<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../Pix/Model/Pix/Boleto.php';
require_once __DIR__ . '/../../../Pix/Helper/Data.php';

/**
 * Tests for OpenPix Boleto Payment Model - Woovi features:
 * - Payment method code retrieval
 * - Refund capabilities
 * - Payment info block configuration
 * - BOLETO-specific functionality
 */
class BoletoPaymentModelTest extends TestCase
{
    public function testPaymentMethodCodeIsCorrect()
    {
        $this->assertEquals(
            'openpix_boleto',
            \OpenPix\Pix\Model\Pix\Boleto::CODE
        );
    }

    public function testPaymentMethodSupportsRefund()
    {
        // Test that the model declares refund support
        $ref = new ReflectionClass(\OpenPix\Pix\Model\Pix\Boleto::class);
        $canRefundProp = $ref->getProperty('_canRefund');
        $canRefundProp->setAccessible(true);

        $instance = $ref->newInstanceWithoutConstructor();
        $this->assertTrue($canRefundProp->getValue($instance));
    }

    public function testPaymentMethodSupportsPartialRefund()
    {
        $ref = new ReflectionClass(\OpenPix\Pix\Model\Pix\Boleto::class);
        $canRefundPartialProp = $ref->getProperty('_canRefundInvoicePartial');
        $canRefundPartialProp->setAccessible(true);

        $instance = $ref->newInstanceWithoutConstructor();
        $this->assertTrue($canRefundPartialProp->getValue($instance));
    }

    public function testPaymentMethodHasCorrectInfoBlock()
    {
        $ref = new ReflectionClass(\OpenPix\Pix\Model\Pix\Boleto::class);
        $infoBlockProp = $ref->getProperty('_infoBlockType');
        $infoBlockProp->setAccessible(true);

        $instance = $ref->newInstanceWithoutConstructor();
        $this->assertEquals(
            \OpenPix\Pix\Block\Info\Boleto::class,
            $infoBlockProp->getValue($instance)
        );
    }

    public function testBoletoPaymentMethodExtendsAbstractMethod()
    {
        $ref = new ReflectionClass(\OpenPix\Pix\Model\Pix\Boleto::class);
        $this->assertTrue(
            $ref->isSubclassOf(
                \Magento\Payment\Model\Method\AbstractMethod::class
            )
        );
    }

    public function testBoletoModelHasGetPayloadMethod()
    {
        $ref = new ReflectionClass(\OpenPix\Pix\Model\Pix\Boleto::class);
        $this->assertTrue($ref->hasMethod('getPayload'));
    }

    public function testBoletoModelHasOrderMethod()
    {
        $ref = new ReflectionClass(\OpenPix\Pix\Model\Pix\Boleto::class);
        $this->assertTrue($ref->hasMethod('order'));
    }

    public function testBoletoModelHasRefundMethod()
    {
        $ref = new ReflectionClass(\OpenPix\Pix\Model\Pix\Boleto::class);
        $this->assertTrue($ref->hasMethod('refund'));
    }

    public function testBoletoModelHasHandleCreateChargeMethod()
    {
        $ref = new ReflectionClass(\OpenPix\Pix\Model\Pix\Boleto::class);
        $this->assertTrue($ref->hasMethod('handleCreateCharge'));
    }

    public function testIsAvailableWhenBoletoIsActive()
    {
        $boleto = $this->createBoletoWithConfig([
            'payment/openpix_boleto/active' => '1',
        ]);

        $this->assertTrue($boleto->isAvailable());
    }

    public function testIsNotAvailableWhenBoletoIsInactive()
    {
        $boleto = $this->createBoletoWithConfig([
            'payment/openpix_boleto/active' => '0',
        ]);

        $this->assertFalse($boleto->isAvailable());
    }

    private function createBoletoWithConfig(array $config)
    {
        $helperRef = new ReflectionClass(\OpenPix\Pix\Helper\Data::class);
        $helper = $helperRef->newInstanceWithoutConstructor();
        $scopeConfig = $helperRef->getProperty('scopeConfig');
        $scopeConfig->setAccessible(true);
        $scopeConfig->setValue(
            $helper,
            new class ($config) {
                private $config;

                public function __construct(array $config)
                {
                    $this->config = $config;
                }

                public function getValue($path, $scope = null)
                {
                    return $this->config[$path] ?? null;
                }
            }
        );

        $boletoRef = new ReflectionClass(\OpenPix\Pix\Model\Pix\Boleto::class);
        $boleto = $boletoRef->newInstanceWithoutConstructor();
        $helperData = $boletoRef->getProperty('_helperData');
        $helperData->setAccessible(true);
        $helperData->setValue($boleto, $helper);

        return $boleto;
    }
}
