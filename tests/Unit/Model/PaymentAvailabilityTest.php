<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../Pix/Model/Pix/Pix.php';
require_once __DIR__ . '/../../../Pix/Model/Pix/PixParcelado.php';
require_once __DIR__ . '/../../../Pix/Model/Pix/Boleto.php';
require_once __DIR__ . '/../../../Pix/Helper/Data.php';

/**
 * The payment methods are offered only when enabled and configured with an App ID:
 * an unconfigured store keeps its own payment methods (the EQP MFTF tests rely on it).
 */
class PaymentAvailabilityTest extends TestCase
{
    public function paymentMethods()
    {
        return [
            'pix' => [
                \OpenPix\Pix\Model\Pix\Pix::class,
                'payment/openpix_pix/active',
            ],
            'pix parcelado' => [
                \OpenPix\Pix\Model\Pix\PixParcelado::class,
                'payment/openpix_pix/active',
            ],
            'boleto' => [
                \OpenPix\Pix\Model\Pix\Boleto::class,
                'payment/openpix_boleto/active',
            ],
        ];
    }

    /**
     * @dataProvider paymentMethods
     */
    public function testIsAvailableWhenEnabledAndConfigured($class, $activePath)
    {
        $method = $this->createMethodWithConfig($class, [
            $activePath => '1',
            'payment/openpix_pix/app_ID' => 'app-id',
        ]);

        $this->assertTrue($method->isAvailable());
    }

    /**
     * @dataProvider paymentMethods
     */
    public function testIsNotAvailableWithoutAppId($class, $activePath)
    {
        $method = $this->createMethodWithConfig($class, [$activePath => '1']);

        $this->assertFalse($method->isAvailable());
    }

    /**
     * @dataProvider paymentMethods
     */
    public function testIsNotAvailableWhenDisabled($class, $activePath)
    {
        $method = $this->createMethodWithConfig($class, [
            $activePath => '0',
            'payment/openpix_pix/app_ID' => 'app-id',
        ]);

        $this->assertFalse($method->isAvailable());
    }

    private function createMethodWithConfig($class, array $config)
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

        $methodRef = new ReflectionClass($class);
        $method = $methodRef->newInstanceWithoutConstructor();
        $helperData = $methodRef->getProperty('_helperData');
        $helperData->setAccessible(true);
        $helperData->setValue($method, $helper);

        return $method;
    }
}
