<?php


namespace OpenPix\Pix\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;

class UpgradeData implements UpgradeDataInterface
{

    /**
     * {@inheritdoc}
     */
    public function upgrade(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $setup->startSetup();

        $data = [
            'scope' => 'default',
            'scope_id' => 0,
            'path' => 'openpixconfiguration/general/webhook_key',
            'value' => self::generateRandomHash(),
        ];
        $setup->getConnection()
            ->insertOnDuplicate($setup->getTable('core_config_data'), $data, ['value']);

        $setup->endSetup();
    }

    public static function generateRandomHash()
    {
        $length = 15;
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
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
