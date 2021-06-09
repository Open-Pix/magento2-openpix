<?php

namespace OpenPix\Pix\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

class UpgradeSchema implements UpgradeSchemaInterface {
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        $orderTable = 'sales_order';

        //OrderTable
        $setup->getConnection()
            ->addColumn(
                $setup->getTable($orderTable),
                "openpix_paymentlinkurl",
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => '500',
                    'default' => null,
                    'nullable' => true,
                    'comment' => 'OpenPix Payment Link Url'
                ]
            );

        $setup->getConnection()
            ->addColumn(
                $setup->getTable($orderTable),
                "openpix_qrcodeimage",
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => '500',
                    'default' => null,
                    'nullable' => true,
                    'comment' => 'OpenPix QrCode Image URL'
                ]
            );


        $setup->getConnection()
            ->addColumn(
                $setup->getTable($orderTable),
                "openpix_brcode",
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => '500',
                    'default' => null,
                    'nullable' => true,
                    'comment' => 'OpenPix brCode EMV'
                ]
            );

        $setup->endSetup();
    }
}
