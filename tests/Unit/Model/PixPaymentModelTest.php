<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../Pix/Model/Pix/Pix.php';

/**
 * Tests for OpenPix Pix Payment Model - Woovi features:
 * - Payment method code retrieval
 * - Refund capabilities
 * - Payment info block configuration
 */
class PixPaymentModelTest extends TestCase
{
    public function testPaymentMethodCodeIsCorrect()
    {
        $this->assertEquals('openpix_pix', \OpenPix\Pix\Model\Pix\Pix::CODE);
    }

    public function testPaymentMethodSupportsRefund()
    {
        // Test that the model declares refund support
        $ref = new ReflectionClass(\OpenPix\Pix\Model\Pix\Pix::class);
        $canRefundProp = $ref->getProperty('_canRefund');
        $canRefundProp->setAccessible(true);

        $instance = $ref->newInstanceWithoutConstructor();
        $this->assertTrue($canRefundProp->getValue($instance));
    }

    public function testPaymentMethodSupportsPartialRefund()
    {
        $ref = new ReflectionClass(\OpenPix\Pix\Model\Pix\Pix::class);
        $canRefundPartialProp = $ref->getProperty('_canRefundInvoicePartial');
        $canRefundPartialProp->setAccessible(true);

        $instance = $ref->newInstanceWithoutConstructor();
        $this->assertTrue($canRefundPartialProp->getValue($instance));
    }

    public function testPaymentMethodHasCorrectInfoBlock()
    {
        $ref = new ReflectionClass(\OpenPix\Pix\Model\Pix\Pix::class);
        $infoBlockProp = $ref->getProperty('_infoBlockType');
        $infoBlockProp->setAccessible(true);

        $instance = $ref->newInstanceWithoutConstructor();
        $this->assertEquals(
            \OpenPix\Pix\Block\Info\Pix::class,
            $infoBlockProp->getValue($instance)
        );
    }
}
