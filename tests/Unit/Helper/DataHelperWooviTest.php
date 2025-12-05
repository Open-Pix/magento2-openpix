<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../Pix/Helper/Data.php';

/**
 * Tests for OpenPix Helper Data - Woovi features:
 * - CPF/CNPJ validation (Brazilian tax ID validation)
 * - UUID v4 generation for correlation IDs
 * - Configuration getters for OpenPix URLs
 */
class DataHelperWooviTest extends TestCase
{
    private function createHelper()
    {
        $ref = new \ReflectionClass(\OpenPix\Pix\Helper\Data::class);
        return $ref->newInstanceWithoutConstructor();
    }

    // UUID v4 generation tests (used for correlation IDs in OpenPix charges)
    public function testUuidV4GeneratesValidFormat()
    {
        $uuid = \OpenPix\Pix\Helper\Data::uuid_v4();

        // UUID v4 format: xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $uuid
        );
    }

    public function testUuidV4GeneratesUniqueValues()
    {
        $uuid1 = \OpenPix\Pix\Helper\Data::uuid_v4();
        $uuid2 = \OpenPix\Pix\Helper\Data::uuid_v4();

        $this->assertNotEquals($uuid1, $uuid2);
    }

    // CPF validation tests (Brazilian individual tax ID - required for Pix payments)
    public function testValidateCPFAcceptsValidCPF()
    {
        $helper = $this->createHelper();

        // Valid CPF: 529.982.247-25
        $this->assertTrue($helper->validateCPF('52998224725'));
        $this->assertTrue($helper->validateCPF('529.982.247-25')); // with formatting
    }

    public function testValidateCPFRejectsInvalidCPF()
    {
        $helper = $this->createHelper();

        $this->assertFalse($helper->validateCPF('12345678901')); // invalid check digits
        $this->assertFalse($helper->validateCPF('00000000000')); // all zeros
        $this->assertFalse($helper->validateCPF('11111111111')); // repeated digits
    }

    public function testValidateCPFRejectsEmptyValue()
    {
        $helper = $this->createHelper();
        $this->assertFalse($helper->validateCPF(''));
    }

    public function testValidateCPFRejectsInvalidLength()
    {
        $helper = $this->createHelper();
        $this->assertFalse($helper->validateCPF('123')); // too short
        $this->assertFalse($helper->validateCPF('123456789012345')); // too long
    }

    // CNPJ validation tests (Brazilian company tax ID - required for corporate Pix payments)
    public function testValidateCNPJAcceptsValidCNPJ()
    {
        $helper = $this->createHelper();

        // Valid CNPJ: 11.222.333/0001-81
        $this->assertTrue($helper->validateCNPJ('11222333000181'));
        $this->assertTrue($helper->validateCNPJ('11.222.333/0001-81')); // with formatting
    }

    public function testValidateCNPJRejectsInvalidCNPJ()
    {
        $helper = $this->createHelper();

        $this->assertFalse($helper->validateCNPJ('12345678000199')); // invalid check digits
        $this->assertFalse($helper->validateCNPJ('00000000000000')); // all zeros
        $this->assertFalse($helper->validateCNPJ('11111111111111')); // repeated digits
    }

    public function testValidateCNPJRejectsEmptyValue()
    {
        $helper = $this->createHelper();
        $this->assertFalse($helper->validateCNPJ(''));
    }

    public function testValidateCNPJRejectsInvalidLength()
    {
        $helper = $this->createHelper();
        $this->assertFalse($helper->validateCNPJ('123')); // too short
        $this->assertFalse($helper->validateCNPJ('123456789012345')); // too long
    }

    // Numeric helpers used in discount/total calculations
    public function testAbsintConvertsToPositiveInteger()
    {
        $helper = $this->createHelper();

        $this->assertEquals(5, $helper->absint(-5));
        $this->assertEquals(10, $helper->absint(10));
        $this->assertEquals(0, $helper->absint(0));
        $this->assertEquals(7, $helper->absint('7'));
        $this->assertEquals(3, $helper->absint('-3'));
    }

    public function testSumAbsValuesCalculatesCorrectly()
    {
        $helper = $this->createHelper();

        $this->assertEquals(6, $helper->sumAbsValues([1, -2, 3]));
        $this->assertEquals(15, $helper->sumAbsValues([5, -5, 5]));
        $this->assertEquals(0, $helper->sumAbsValues([]));
        $this->assertEquals(10, $helper->sumAbsValues([10]));
    }
}
