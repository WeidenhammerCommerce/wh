<?php
namespace Hammer\WH\Block;

use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\Customer\Model\CustomerFactory;

use Hammer\WH\Settings\StoreInfo;

class Dummy
{
    protected $storeInfo;
    protected $directoryList;
    protected $root;
    protected $app;

    protected $storeManager;
    protected $category;
    protected $categoryRepository;
    protected $productFactory;
    protected $productRepository;
    protected $categoryLinkManagement;
    protected $customerFactory;

    public function __construct(
        DirectoryList $directoryList,
        StoreInfo $storeInfo,
        StoreManagerInterface $storeManager,
        Category $category,
        CategoryRepositoryInterface $categoryRepository,
        ProductInterfaceFactory $productFactory,
        ProductRepositoryInterface $productRepository,
        CategoryLinkManagementInterface $categoryLinkManagement,
        CustomerFactory $customerFactory
    )
    {
        $this->directoryList = $directoryList;
        $this->storeInfo = $storeInfo;
        $this->root = $this->directoryList->getRoot();
        $this->app = $this->directoryList->getPath('app');

        $this->storeManager = $storeManager;
        $this->category = $category;
        $this->categoryRepository = $categoryRepository;
        $this->productFactory = $productFactory;
        $this->productRepository = $productRepository;
        $this->categoryLinkManagement = $categoryLinkManagement;
        $this->customerFactory = $customerFactory;
    }



    public function createDummyContent($categoriesQty, $productsQty)
    {
        // Prepare category names
        $categories = array();
        for($i=1; $i<=$categoriesQty; $i++){
            $categories[] = 'Dummy Category '.$i;
        }

        // Prepare product names
        for($i=1; $i<=$productsQty; $i++){
            $products[] = 'Dummy Product '.$i;
        }

        // Get root category
        $store = $this->storeManager->getStore();
        $rootCategoryId = $store->getRootCategoryId();

        // Create dummy categories
        $categoryIds = array();
        foreach($categories as $categoryName) {
            $data = [
                "parent_id" => $rootCategoryId,
                'name' => $categoryName,
                "include_in_menu" => true,
                "position" => 10,
                "is_active" => true
            ];

            $this->category->setData($data);
            $this->category->setStoreId($this->storeInfo->getDefaultStoreId());
            $this->categoryRepository->save($this->category);
            $categoryIds[] = $this->category->getId();

            // Create dummy products
            foreach($products as $productName) {
                $productSku = strtolower(str_replace(' ', '-', $productName));
                $product = $this->productFactory->create();
                $product->setName($productName);
                $product->setSku($productSku);
                $product->setTypeId(Type::TYPE_SIMPLE);
                $product->setVisibility(4); // Catalog, Search
                $product->setPrice(100);
                $product->setAttributeSetId(4); // Default attribute set for products
                $product->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);
                /*$product->addImageToMediaGallery('resources/images/dummy_product.jpg',
                    array('image', 'small_image', 'thumbnail'), false, false);*/
                $product->setStockData([
                    'qty' => 100,
                    'is_in_stock' => 1
                ]);
                $this->productRepository->save($product);

                // Assign products to categories
                $this->categoryLinkManagement->assignProductToCategories(
                    $productSku,
                    $categoryIds
                );
            }
        }
    }

    public function createDummyCustomers($firstName, $lastName, $email, $password)
    {
        $websiteId = $this->storeManager->getWebsite()->getWebsiteId();
        $customer = $this->customerFactory->create();
        $customer->setWebsiteId($websiteId);

        // Save new customer
        $customer->setFirstname($firstName);
        $customer->setLastname($lastName);
        $customer->setEmail($email);
        $customer->setPassword($password);
        $customer->save();
    }
}