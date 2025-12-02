<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../Pix/Block/Info/Boleto.php';

/**
 * Tests for OpenPix Boleto Info Block:
 * - Template configuration
 * - Data retrieval methods
 * - PDF generation
 */
class BoletoInfoBlockTest extends TestCase
{
    public function testBoletoInfoBlockExtendsPaymentInfoBlock()
    {
        $ref = new ReflectionClass(\OpenPix\Pix\Block\Info\Boleto::class);
        $this->assertTrue(
            $ref->isSubclassOf(\Magento\Payment\Block\Info::class)
        );
    }

    public function testBoletoInfoBlockHasCorrectTemplate()
    {
        $ref = new ReflectionClass(\OpenPix\Pix\Block\Info\Boleto::class);
        $templateProp = $ref->getProperty('_template');
        $templateProp->setAccessible(true);

        $instance = $ref->newInstanceWithoutConstructor();
        $this->assertEquals(
            'OpenPix_Pix::info/boleto.phtml',
            $templateProp->getValue($instance)
        );
    }

    public function testBoletoInfoBlockHasToPdfMethod()
    {
        $ref = new ReflectionClass(\OpenPix\Pix\Block\Info\Boleto::class);
        $this->assertTrue($ref->hasMethod('toPdf'));
    }

    public function testBoletoInfoBlockHasGetBoletoDigitableMethod()
    {
        $ref = new ReflectionClass(\OpenPix\Pix\Block\Info\Boleto::class);
        $this->assertTrue($ref->hasMethod('getBoletoDigitable'));
    }

    public function testBoletoInfoBlockHasGetBoletoBarcodeMethod()
    {
        $ref = new ReflectionClass(\OpenPix\Pix\Block\Info\Boleto::class);
        $this->assertTrue($ref->hasMethod('getBoletoBarcode'));
    }

    public function testBoletoInfoBlockHasGetBoletoImageUrlMethod()
    {
        $ref = new ReflectionClass(\OpenPix\Pix\Block\Info\Boleto::class);
        $this->assertTrue($ref->hasMethod('getBoletoImageUrl'));
    }

    public function testBoletoInfoBlockHasGetPaymentLinkUrlMethod()
    {
        $ref = new ReflectionClass(\OpenPix\Pix\Block\Info\Boleto::class);
        $this->assertTrue($ref->hasMethod('getPaymentLinkUrl'));
    }
}
