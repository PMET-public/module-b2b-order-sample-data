<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace MagentoEse\B2BOrderSampleData\Setup;

use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

class UpgradeData implements UpgradeDataInterface
{
    public $_resourceConfig;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    private $encrypted;

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;

    }

    public function upgrade( ModuleDataSetupInterface $setup, ModuleContextInterface $context )
    {
        if (version_compare($context->getVersion(), '0.0.3', '<=')) {
            //add base currency code to existing orders for advanced Reporting
            $connection = $this->resourceConnection->getConnection();
            $orderTableName = $connection->getTableName('sales_order');
            $sql = "update " . $orderTableName . " set base_currency_code =  'USD' where base_currency_code is NULL";
            $connection->query($sql);
            $orderItemTableName = $connection->getTableName('sales_order_item');
            $sql = "update " . $orderTableName . " so, ".$orderItemTableName." oi set oi.created_at = so.created_at, oi.updated_at = so.updated_at where oi.order_id = so.entity_id";
            $connection->query($sql);
        }
    }

}
