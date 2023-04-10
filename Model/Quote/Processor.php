<?php


namespace MagentoEse\B2BOrderSampleData\Model\Quote;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Model\QuoteRepository;

class Processor
{

    /**
     * @var \Magento\Backend\Model\Session\QuoteFactory
     */
    protected $sessionQuoteFactory;


    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Sales\Model\AdminOrder\CreateFactory
     */
    protected $createOrderFactory;


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
     * 
     * @var CartManagementInterface
     */
    protected $cartmanagement;

    /**
     * 
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * 
     * @var Magento\Quote\Api\Data\CartItemInterfaceFactory
     */
    protected $cartItemInterface;

    /**
     * 
     * @var QuoteRepository
     */
    protected $quoteRepository;

    /**
     * 
     * @var ProductRepositoryInterface
     */
    protected $product;

    /**
     * 
     * @var Magento\NegotiableQuote\Api\Data\CommentInterfaceFactory
     */
    protected $commentInterface;

    public function __construct(
        //\Magento\Backend\Model\Session\QuoteFactory $sessionQuoteFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        //\Magento\Sales\Model\AdminOrder\CreateFactory $createOrderFactory,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository,
        // \Magento\Quote\Api\Data\CartInterfaceFactory $cartManagement,
        \Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterfaceFactory $negotiableQuoteInterface,
        \Magento\NegotiableQuote\Api\NegotiableQuoteManagementInterface $negotiableQuoteManagement,
        \Magento\Quote\Api\Data\CartItemInterfaceFactory $cartItemInterface,
        \Magento\Quote\Api\CartManagementInterface $cartManagement,
        \Magento\Quote\Model\QuoteRepository $quoteRepository,
        \Magento\Catalog\Api\ProductRepositoryInterface $product,
        \Magento\NegotiableQuote\Api\Data\CommentInterfaceFactory $commentInterface
    ) {
        //$this->sessionQuoteFactory = $sessionQuoteFactory;
        $this->customerRepository = $customerFactory;
        $this->storeManager = $storeManager;
        //$this->createOrderFactory = $createOrderFactory;
        $this->cartRepository = $cartRepository;
        $this->negotiableQuoteInterface = $negotiableQuoteInterface;
        $this->negotiableQuoteManagement = $negotiableQuoteManagement;
        $this->cartItemInterface = $cartItemInterface;
        $this->cartmanagement = $cartManagement;
        $this->quoteRepository = $quoteRepository;
        $this->product = $product;
        $this->commentInterface = $commentInterface;
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
            //Get quote
            $quote=$this->quoteRepository->getForCustomer($customer->getId());

            //add product to quote
            $this->addProductToQuote($quote,$quoteData['product']);


            //get full cart
            $cart = $this->cartRepository->get($quoteId);

            //convert cart to negotiable quote
            //$cart->getExtensionAttributes()->getNegotiableQuote()->setCreatorId($customer->getId())->setCreatorType(3);
            $this->cartRepository->save($cart);
            //create negotiable quote
            $this->negotiableQuoteManagement->create($quoteId,$quoteData['quote_name']);

            //set discount
            $newQuote = $this->negotiableQuoteInterface->create();
            $newQuote->load($quoteId);
            if($quoteData['discount_type']!='') {
                $newQuote->setNegotiatedPriceType($quoteData['discount_type']);
                $newQuote->setNegotiatedPriceValue($quoteData['discount_amount']);
            }
            //set status
            $newQuote->setStatus($quoteData['status']);
            $newQuote->save();
            $quoteId =$newQuote->getQuoteId();
            $this->negotiableQuoteManagement->recalculateQuote($quoteId);
            //TODO: set negotiable quote date
            //TODO: update prices after discount
            //TODO: figure out status issues
            //TODO: quote history?
            $this->addComment($quoteId,$customer,$quoteData['comments']);
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