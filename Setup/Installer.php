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
     * @var \Magento\Indexer\Model\Processor
     */
    protected $index;


    /**
     * Installer constructor.
     * @param \MagentoEse\SalesSampleData\Model\Order $sampleOrder
     * @param \Magento\Indexer\Model\Processor $index
     * @param \MagentoEse\B2BOrderSampleData\Model\NegotiableQuotes $negotiableQuotes
     */

    public function __construct(
        \MagentoEse\SalesSampleData\Model\Order $sampleOrder,
        \Magento\Indexer\Model\Processor $index,
        \MagentoEse\B2BOrderSampleData\Model\NegotiableQuotes $negotiableQuotes
    ) {

        $this->sampleOrder = $sampleOrder;
        $this->index = $index;
        $this->negotiableQuotes = $negotiableQuotes;

    }

    /**
     * {@inheritdoc}
     */
    public function install()
    {
        $this->index->reindexAll();
        $this->sampleOrder->install(['MagentoEse_B2BOrderSampleData::fixtures/orders_test.csv'],true);
        //$this->negotiableQuotes->install(['MagentoEse_B2BOrderSampleData::fixtures/quotes.csv']);
    }
}