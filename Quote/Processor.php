<?php


namespace MagentoEse\B2BOrderSampleData\Model\Quote;

use Magento\Quote\Api\Data\CartInterface;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;

class Processor
{

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;


    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $cartRepository;

    /**
     * @var \Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterfaceFactory
     */
    protected $negotiableQuoteInterface;

    /**
     * @var \Magento\NegotiableQuote\Api\NegotiableQuoteManagementInterface
     */
    protected $negotiableQuoteManagement;

    /**
     * @var \Magento\Quote\Api\Data\CartItemInterfaceFactory
     */
    protected $cartItemInterface;

    /**
     * @var \Magento\Quote\Api\CartManagementInterface
     */
    protected $cartManagement;

    /**
     * @var \Magento\Quote\Model\QuoteRepository
     */
    protected $quoteRepository;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $product;

    /**
     * @var \Magento\NegotiableQuote\Api\Data\CommentInterfaceFactory
     */
    protected $commentInterface;

    /**
     * @var \Magento\NegotiableQuote\Model\ResourceModel\QuoteGrid
     */
    protected $quoteGrid;

    /**
     * @var \Magento\NegotiableQuote\Model\Quote\TotalsFactory
     */
    protected $quoteTotalsFactory;

    /**
     * Processor constructor.
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Quote\Api\CartRepositoryInterface $cartRepository
     * @param \Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterfaceFactory $negotiableQuoteInterface
     * @param NegotiableQuoteManagement $negotiableQuoteManagement
     * @param \Magento\Quote\Api\Data\CartItemInterfaceFactory $cartItemInterface
     * @param \Magento\Quote\Api\CartManagementInterface $cartManagement
     * @param \Magento\Quote\Model\QuoteRepository $quoteRepository
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $product
     * @param \Magento\NegotiableQuote\Api\Data\CommentInterfaceFactory $commentInterface
     * @param \Magento\NegotiableQuote\Model\ResourceModel\QuoteGrid $quoteGrid
     * @param \Magento\NegotiableQuote\Model\Quote\TotalsFactory $quoteTotalsFactory
     */
    public function __construct(
        \Magento\Customer\Api\CustomerRepositoryInterface $customerFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository,
        \Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterfaceFactory $negotiableQuoteInterface,
        NegotiableQuoteManagement $negotiableQuoteManagement,
        \Magento\Quote\Api\Data\CartItemInterfaceFactory $cartItemInterface,
        \Magento\Quote\Api\CartManagementInterface $cartManagement,
        \Magento\Quote\Model\QuoteRepository $quoteRepository,
        \Magento\Catalog\Api\ProductRepositoryInterface $product,
        \Magento\NegotiableQuote\Api\Data\CommentInterfaceFactory $commentInterface,
        \Magento\NegotiableQuote\Model\ResourceModel\QuoteGrid $quoteGrid,
        \Magento\NegotiableQuote\Model\Quote\TotalsFactory $quoteTotalsFactory
    ) {
        $this->customerRepository = $customerFactory;
        $this->storeManager = $storeManager;
        $this->cartRepository = $cartRepository;
        $this->negotiableQuoteInterface = $negotiableQuoteInterface;
        $this->negotiableQuoteManagement = $negotiableQuoteManagement;
        $this->cartItemInterface = $cartItemInterface;
        $this->cartmanagement = $cartManagement;
        $this->quoteRepository = $quoteRepository;
        $this->product = $product;
        $this->commentInterface = $commentInterface;
        $this->quoteGrid = $quoteGrid;
        $this->quoteTotalsFactory = $quoteTotalsFactory;
    }


    public function createQuote($quoteData)
    {
        if (!empty($quoteData)) {
            //Get Customer
            $customer = $this->customerRepository->get(
                $quoteData['customer_email'],
                $this->storeManager->getWebsite()->getId());
            //create empty cart for customer
            $quoteId = $this->cartmanagement->createEmptyCartForCustomer($customer->getId());
            //Get empty quote
            $quote=$this->quoteRepository->getForCustomer($customer->getId());

            //add product to quote
            $this->addProductToQuote($quote,$quoteData['product']);

            $this->quoteRepository->save($quote);

            //create negotiable quote
            $this->negotiableQuoteManagement->create($quoteId,$quoteData['quote_name'],$quoteData['comments']);
            //TODO: set discount and status
            /*$newQuote = $this->negotiableQuoteInterface->create();
            $newQuote->load($quoteId);
            //$quote->load($quoteId);
            $newQuote->setStatus('processing_by_admin');
             if($quoteData['discount_type']!='') {
                $newQuote->setNegotiatedPriceType($quoteData['discount_type']);
                $newQuote->setNegotiatedPriceValue($quoteData['discount_amount']);
            }

            $newQuote->save();
            $quote=$this->quoteRepository->get($quoteId);
            $quote->getExtensionAttributes()->setNegotiableQuote($newQuote);
            $this->setNegotiableQuotePrices($newQuote, $quote);
            $newQuote->save();
            $quote->save();
            $this->quoteGrid->refresh($quote);*/

            //TODO: set negotiable quote date

            //TODO: quote history?
            // $this->addComment($quoteId,$customer,$quoteData['comments']);
        }
    }
    private function setNegotiableQuotePrices(
    NegotiableQuoteInterface $negotiableQuote,
        CartInterface $quote
    ) {
        $totals = $this->quoteTotalsFactory->create(['quote' => $quote]);
        $negotiableQuote->setData(NegotiableQuoteInterface::ORIGINAL_TOTAL_PRICE, $totals->getCatalogTotalPrice(true));
        $negotiableQuote->setData(NegotiableQuoteInterface::BASE_ORIGINAL_TOTAL_PRICE, $totals->getCatalogTotalPrice());
        if ($negotiableQuote->getStatus() !== NegotiableQuoteInterface::STATUS_CREATED) {
            $negotiableQuote->setData(NegotiableQuoteInterface::BASE_NEGOTIATED_TOTAL_PRICE, $totals->getSubtotal());
            $negotiableQuote->setData(NegotiableQuoteInterface::NEGOTIATED_TOTAL_PRICE, $totals->getSubtotal(true));
        }else {
            $negotiableQuote
                ->setData(NegotiableQuoteInterface::BASE_NEGOTIATED_TOTAL_PRICE, $totals->getCatalogTotalPrice());
            $negotiableQuote
               ->setData(NegotiableQuoteInterface::NEGOTIATED_TOTAL_PRICE, $totals->getCatalogTotalPrice(true));
        }
    }

    private function addProductToQuote($quote,$productString){
        //split product array
        $items = explode(';',$productString);
        //split into values
        foreach($items as $item){
            parse_str($item,$productInfo);
            $product = $this->product->get($productInfo['sku']);
            $quoteItem = $this->cartItemInterface->create();
            $quoteItem->setProduct($product);
            $quoteItem->setQty($productInfo['qty']);
            $quote->addItem($quoteItem);
            $quote->collectTotals()->save();

        }

    }
    private function addComment($quoteId,$customer,$commentsData){
        $items = explode(';',$commentsData);
        //split into values
        foreach($items as $item){
            parse_str($item,$commentInfo);
            $comment = $this->commentInterface->create();
            //determine createor type
            if($commentInfo['type']=='customer'){
                $creatorType = 3;
                $creatorId = $customer->getId();
            }else{
                $creatorType=2;
                $creatorId = 3;
            }
            $comment->setCreatorType($creatorType)->setCreatorId($creatorId)->setComment($commentInfo['comment'])->setParentId($quoteId);
            //$comment->setCreatedAt();
            $comment->save();

        }


    }

}