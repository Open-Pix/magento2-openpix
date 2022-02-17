<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace OpenPix\Pix\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Patch is mechanism, that allows to do atomic upgrade data changes
 */
class UpdateConfigWebhook implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface $moduleDataSetup
     */
    private $moduleDataSetup;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(ModuleDataSetupInterface $moduleDataSetup)
    {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    /**
     * Do Upgrade
     *
     * @return void
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $data = [
            'scope' => 'default',
            'scope_id' => 0,
            'path' => 'openpixconfiguration/general/webhook_key',
            'value' => self::generateRandomHash(),
        ];

        $this->moduleDataSetup
            ->getConnection()
            ->insertOnDuplicate(
                $this->moduleDataSetup->getTable('core_config_data'),
                $data,
                ['value']
            );

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    public static function generateRandomHash()
    {
        $length = 15;
        $characters =
            '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $encoding = '8bit';

        if (false === ($max = mb_strlen($characters, $encoding))) {
            throw new \BadMethodCallException('Invalid encoding passed');
        }
        $string = '';
        $max--;
        for ($i = 0; $i < $length; ++$i) {
            $string .= $characters[random_int(0, $max)];
        }
        return $string;
    }
}
