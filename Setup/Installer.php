<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MagentoEse\B2BOrderSampleData\Setup;

use Magento\Framework\Setup;


class Installer implements Setup\SampleData\InstallerInterface
{
    /**
     * @var \MagentoEse\SalesSampleData\Model\Order
     */
    protected $sampleOrder;


    /**
     * Installer constructor.
     * @param \MagentoEse\SalesSampleData\Model\Order $sampleOrder
     * @param \Magento\Indexer\Model\Processor $index
     */
    public function __construct(
        \MagentoEse\SalesSampleData\Model\Order $sampleOrder,
        \Magento\Indexer\Model\Processor $index
    ) {

        $this->sampleOrder = $sampleOrder;
        $this->index = $index;

    }

    /**
     * {@inheritdoc}
     */
    public function install()
    {
        $this->index->reindexAll();
        $this->sampleOrder->install(['MagentoEse_B2BOrderSampleData::fixtures/orders.csv'],true);
    }
}