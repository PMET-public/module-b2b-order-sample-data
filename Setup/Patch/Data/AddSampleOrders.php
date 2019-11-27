<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace MagentoEse\B2BOrderSampleData\Setup\Patch\Data;


use Magento\Framework\Setup\Patch\DataPatchInterface;
use MagentoEse\SalesSampleData\Model\Order;
use Magento\Indexer\Model\Processor ;


class AddSampleOrders implements DataPatchInterface
{

    /** @var Order  */
    protected $sampleOrder;

    /** @var Processor  */
    protected $index;


    public function __construct(Order $sampleOrder, Processor $index)
    {
        $this->sampleOrder = $sampleOrder;
        $this->index = $index;
    }

    public function apply()
    {
        //$this->index->reindexAll();
        $this->sampleOrder->install(['MagentoEse_B2BOrderSampleData::fixtures/orders.csv'],true);
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }
}