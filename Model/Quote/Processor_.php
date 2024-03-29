<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MagentoEse\B2BOrderSampleData\Model\Quote;

use Magento\Framework\DataObject;
use Magento\NegotiableQuote\Api\NegotiableQuoteManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote\Item\ToOrderItem;

/**
 * Class Processor
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Processor_
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;

    /**
     * @var \Magento\Framework\Phrase\Renderer\CompositeFactory
     */
    protected $rendererCompositeFactory;

    /**
     * @var \Magento\Sales\Model\AdminOrder\CreateFactory
     */
    protected $createOrderFactory;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var \Magento\Framework\DB\TransactionFactory
     */
    protected $transactionFactory;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderFactory;

    /**
     * @var \Magento\Sales\Model\Service\InvoiceService
     */
    protected $invoiceManagement;

    /**
     * @var \Magento\Shipping\Controller\Adminhtml\Order\ShipmentLoaderFactory
     */
    protected $shipmentLoaderFactory;

    /**
     * @var \Magento\Sales\Controller\Adminhtml\Order\CreditmemoLoaderFactory
     */
    protected $creditmemoLoaderFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Sales\Api\CreditmemoManagementInterface
     */
    protected $creditmemoManagement;

    /**
     * @var \Magento\Backend\Model\Session\QuoteFactory
     */
    protected $sessionQuoteFactory;

    /**
     * @var \Magento\Backend\Model\Session\Quote
     */
    protected $currentSession;
    
    /**
     * 
     * @var NegotiableQuoteManagementInterface
     */
    protected $negotiableQuoteManagement;

    /**
     * 
     * @var ToOrderItem
     */
    protected $toOrderItem;

    /**
     * 
     * @var CartRepositoryInterface
     */
    protected $cartRepository;

    /**
     * 
     * @var Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterfaceFactory
     */
    protected $negotiableQuoteInterface;

    /**
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Framework\Phrase\Renderer\CompositeFactory $rendererCompositeFactory
     * @param \Magento\Sales\Model\AdminOrder\CreateFactory $createOrderFactory
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerFactory
     * @param \Magento\Backend\Model\Session\QuoteFactory $sessionQuoteFactory
     * @param \Magento\Framework\DB\TransactionFactory $transactionFactory
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Sales\Model\Service\InvoiceService $invoiceManagement
     * @param \Magento\Shipping\Controller\Adminhtml\Order\ShipmentLoaderFactory $shipmentLoaderFactory
     * @param \Magento\Sales\Controller\Adminhtml\Order\CreditmemoLoaderFactory $creditmemoLoaderFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Sales\Api\CreditmemoManagementInterface $creditmemoManagement
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\Phrase\Renderer\CompositeFactory $rendererCompositeFactory,
        \Magento\Sales\Model\AdminOrder\CreateFactory $createOrderFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerFactory,
        \Magento\Backend\Model\Session\QuoteFactory $sessionQuoteFactory,
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Sales\Model\Service\InvoiceService $invoiceManagement,
        \Magento\Shipping\Controller\Adminhtml\Order\ShipmentLoaderFactory $shipmentLoaderFactory,
        \Magento\Sales\Controller\Adminhtml\Order\CreditmemoLoaderFactory $creditmemoLoaderFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Sales\Api\CreditmemoManagementInterface $creditmemoManagement,
        \Magento\Quote\Model\Quote\Item\ToOrderItem $toOrderItem,
        \Magento\NegotiableQuote\Api\NegotiableQuoteManagementInterface $negotiableQuoteManagement,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository,
        \Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterfaceFactory $negotiableQuoteInterface
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->rendererCompositeFactory = $rendererCompositeFactory;
        $this->createOrderFactory = $createOrderFactory;
        $this->customerRepository = $customerFactory;
        $this->sessionQuoteFactory = $sessionQuoteFactory;
        $this->transactionFactory = $transactionFactory;
        $this->orderFactory = $orderFactory;
        $this->invoiceManagement = $invoiceManagement;
        $this->shipmentLoaderFactory = $shipmentLoaderFactory;
        $this->creditmemoLoaderFactory = $creditmemoLoaderFactory;
        $this->storeManager = $storeManager;
        $this->creditmemoManagement = $creditmemoManagement;
        $this->toOrderItem = $toOrderItem;
        $this->negotiableQuoteManagement = $negotiableQuoteManagement;
        $this->cartRepository = $cartRepository;
        $this->negotiableQuoteInterface = $negotiableQuoteInterface;
    }

    /**
     * @param array $orderData
     * @return void
     */
    public function createQuote($orderData)
    {
        $this->setPhraseRenderer();
        if (!empty($orderData)) {
            $this->currentSession = $this->sessionQuoteFactory->create();
            $customer = $this->customerRepository->get(
                $orderData['order']['account']['email'],
                $this->storeManager->getWebsite()->getId()
            );
            $this->currentSession->setCustomerId($customer->getId());
            $quote = $this->processQuote($orderData);
            $quote->getQuote()->setCustomer($customer);
            //$t= $orderCreateModel->getQuote();
           // $q = $t->getAllItems();
            //$i = $t->getId();

            $quote->addData(['creator_type'=>3,'creator_id'=>6]);

            $order = $quote->createOrder();
            $cart = $this->cartRepository->get($order->getQuoteId());
            //convert cart to negotiable quote
            $cart->getExtensionAttributes()->getNegotiableQuote()->setCreatorId(6)->setCreatorType(3);
            $this->cartRepository->save($cart);
            //add name and comment
            $this->negotiableQuoteManagement->create($order->getQuoteId(),'name of quote','quote comment');
            // get quote
            //$newQuote = $this->negotiableQuoteManagement->getNegotiableQuote($order->getQuoteId());
            $newQuote = $this->negotiableQuoteInterface->create();
            $newQuote->load($order->getQuoteId());
            $newQuote->setNegotiatedPriceType(1);
            $newQuote->setNegotiatedPriceValue(10);
            $newQuote->setStatus();
            $newQuote->save();

            exit;

            //$orderItems = $order->getAllItems();
            $quoteItems = $orderCreateModel->getQuote()->getAllItems();



            exit;

            foreach($quoteItems as $quoteItem){
                $newOrderItem = $this->toOrderItem->convert($quoteItem);
                $quoteItemId = $newOrderItem->getProductId();
                foreach ($orderItems as $oItem){
                    $oItemId = $oItem->getProductId();
                    if($oItemId!=$quoteItemId){
                        $order->addItem($newOrderItem);
                    }
                }

            }
            //fix totals on order
            $realTotal = $order->getBaseTotalDue();
            $order->setBaseSubtotal($realTotal);
            $order->setSubtotal($realTotal);
            $order->setBaseSubtotalInclTax($realTotal);
            $order->setTotalItemCount(count($quoteItems));
            $order->setBaseSubtotal($realTotal);
            $order->setBaseSubtotal($realTotal);
            //$order->save();
            $orderItem = $this->getOrderItemForTransaction($order);
            // set default date if not provided by data
            if(! array_key_exists('created_at',$orderData['order'])){
                $orderData['order']['created_at'] = date('Y-m-d h:i:s');

            }
            if(! array_key_exists('updated_at',$orderData['order'])){
                $orderData['order']['updated_at'] = date('Y-m-d h:i:s');
            }

            $order->setStatus($orderData['order']['status']);
            $order->setState($orderData['order']['status']);
            $order->setCreatedAt($orderData['order']['created_at']);
            $order->setCreatedAt($orderData['order']['updated_at']);
            $order->save();
            //create shipment and invoice if order is complete
            if($orderData['order']['status']=='complete'){
                $this->invoiceOrder($orderItem,$orderData['order']['created_at'],$orderData['order']['updated_at']);
                $this->shipOrder($orderItem,$orderData['order']['created_at'],$orderData['order']['updated_at']);
            }

            $registryItems = [
                'rule_data',
                'currently_saved_addresses',
                'current_invoice',
                'current_shipment',
            ];
            $this->unsetRegistryData($registryItems);
            $this->currentSession->unsQuoteId();
            $this->currentSession->unsStoreId();
            $this->currentSession->unsCustomerId();
            return $order->getId();
        }
    }

    /**
     * @param array $data
     * @return \Magento\Sales\Model\AdminOrder\Create
     */
    protected function processQuote($data = [])
    {
        /** @var \Magento\Sales\Model\AdminOrder\Create $orderCreateModel */
        $orderCreateModel = $this->createOrderFactory->create(
            ['quoteSession' => $this->currentSession]
        );
        $orderCreateModel->importPostData($data['order'])->initRuleData();
        $orderCreateModel->getBillingAddress();
        $orderCreateModel->setShippingAsBilling(1);
        $orderCreateModel->addProducts($data['add_products']);
        $orderCreateModel->getQuote()->getShippingAddress()->unsetData('cached_items_all');
        $orderCreateModel->getQuote()->getShippingAddress()->setShippingMethod($data['order']['shipping_method']);
        $orderCreateModel->getQuote()->setTotalsCollectedFlag(false);
        $orderCreateModel->collectShippingRates();
        $orderCreateModel->getQuote()->getPayment()->addData($data['payment'])->setQuote($orderCreateModel->getQuote());
        return $orderCreateModel;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return mixed
     */
    protected function getOrderItemForTransaction(\Magento\Sales\Model\Order $order)
    {
        $order->getItemByQuoteItemId($order->getQuoteId());
        foreach ($order->getItemsCollection() as $item) {
            if (!$item->isDeleted() && !$item->getParentItemId()) {
                return $item;
            }
        }
        return null;
    }

    /**
     * @param \Magento\Sales\Model\Order\Item $orderItem
     * @return void
     */
    protected function invoiceOrder($orderItem,$createDate,$updateDate)
    {
        $invoiceData = [$orderItem->getId() => $orderItem->getQtyToInvoice()];
        $invoice = $this->createInvoice($orderItem->getOrderId(), $invoiceData);
        $invoice->setCreatedAt($createDate);
        $invoice->setUpdatedAt($updateDate);
        //$invoice->save();
        if ($invoice) {
            $invoice->register();
            $invoice->getOrder()->setIsInProcess(true);
            $invoiceTransaction = $this->transactionFactory->create()
                ->addObject($invoice)
                ->addObject($invoice->getOrder());
            $invoiceTransaction->save();
        }
    }

    /**
     * @param int $orderId
     * @param array $invoiceData
     * @return bool | \Magento\Sales\Model\Order\Invoice
     */
    protected function createInvoice($orderId, $invoiceData)
    {
        $order = $this->orderFactory->create()->load($orderId);
        if (!$order) {
            return false;
        }
        $invoice = $this->invoiceManagement->prepareInvoice($order, $invoiceData);
        return $invoice;
    }

    /**
     * @param \Magento\Sales\Model\Order\Item $orderItem
     * @return void
     */
    protected function shipOrder($orderItem,$createDate,$updateDate)
    {
        $shipmentLoader = $this->shipmentLoaderFactory->create();
        $shipmentData = [$orderItem->getId() => $orderItem->getQtyToShip()];
        $shipmentLoader->setOrderId($orderItem->getOrderId());
        $shipmentLoader->setShipment($shipmentData);
        $shipment = $shipmentLoader->load();
        $shipment->setCreatedAt($createDate);
        $shipment->setUpdatedAt($updateDate);
        //$shipment->save();
        if ($shipment) {
            $shipment->register();
            $shipment->getOrder()->setIsInProcess(true);
            $shipmentTransaction = $this->transactionFactory->create()
                ->addObject($shipment)
                ->addObject($shipment->getOrder());
            $shipmentTransaction->save();
        }
    }

    /**
     * @param \Magento\Sales\Model\Order\Item $orderItem
     * @return void
     */
    protected function refundOrder(\Magento\Sales\Model\Order\Item $orderItem)
    {
        $creditmemoLoader = $this->creditmemoLoaderFactory->create();
        $creditmemoLoader->setOrderId($orderItem->getOrderId());
        $creditmemoLoader->setCreditmemo($this->getCreditmemoData($orderItem));
        $creditmemo = $creditmemoLoader->load();
        if ($creditmemo && $creditmemo->isValidGrandTotal()) {
            $creditmemo->setOfflineRequested(true);
            $this->creditmemoManagement->refund($creditmemo, true);
        }
    }

    /**
     * @param \Magento\Sales\Model\Order\Item $orderItem
     * @return array
     */
    public function getCreditmemoData(\Magento\Sales\Model\Order\Item $orderItem)
    {
        $data = [$orderItem->getId() => $orderItem->getQtyToRefund()];

        return $data;
    }

    /**
     * Set phrase renderer
     * @return void
     */
    protected function setPhraseRenderer()
    {
        \Magento\Framework\Phrase::setRenderer($this->rendererCompositeFactory->create());
    }

    /**
     * Unset registry item
     * @param array|string $unsetData
     * @return void
     */
    protected function unsetRegistryData($unsetData)
    {
        if (is_array($unsetData)) {
            foreach ($unsetData as $item) {
                $this->coreRegistry->unregister($item);
            }
        } else {
            $this->coreRegistry->unregister($unsetData);
        }
    }

}
