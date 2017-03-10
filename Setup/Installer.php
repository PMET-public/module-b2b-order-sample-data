<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MagentoEse\B2BOrderSampleData\Setup;

use Magento\Framework\Setup;


class Installer implements Setup\SampleData\InstallerInterface
{
    protected $sampleOrder;


    public function __construct(
        \MagentoEse\SalesSampleData\Model\Order $sampleOrder
    ) {

        $this->sampleOrder = $sampleOrder;

    }

    /**
     * {@inheritdoc}
     */
    public function install()
    {
       $this->sampleOrder->install(['MagentoEse_B2BOrderSampleData::fixtures/orders.csv']);
    }
}