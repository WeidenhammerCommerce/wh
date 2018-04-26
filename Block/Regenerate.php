<?php
/**
 * Regenerate URL rewrites
 *
 * @package Hammer_WH
 * @author Sebastian De Cicco <seb.decc@gmail.com>
 * @copyright 2018 Sebastian De Cicco
 * @license OSL-3.0, AFL-3.0
 */

namespace Hammer\WH\Block;

use Magento\Framework\App\ResourceConnection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Catalog\Helper\Category;
use Magento\Framework\App\State;

class Regenerate
{
    protected $categoryCollectionFactory;
    protected $categoryHelper;
    protected $storeManager;
    protected $appState;

    /**
     * Constructor of RegenerateUrlRewrites
     *
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory
     * @param \Magento\Catalog\Helper\Category $categoryHelper
     */
    public function __construct(
        ResourceConnection $resource,
        CollectionFactory $categoryCollectionFactory,
        Category $categoryHelper,
        State $appState
    ) {
        $this->resource = $resource;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->categoryHelper = $categoryHelper;
        $this->appState = $appState;
    }


    /**
     * Remove all current Url rewrites of categories/products from DB
     *
     * @param array $storesList
     * @return void
     */
    public function removeAllUrlRewrites($storesList) 
    {
        $storeIds = implode(',', array_keys($storesList));
        $sql = "DELETE FROM {$this->resource->getTableName('url_rewrite')} WHERE `entity_type` IN ('category', 'product') AND `store_id` IN ({$storeIds});";
        $this->resource->getConnection()->query($sql);

        $sql = "DELETE FROM {$this->resource->getTableName('catalog_url_rewrite_product_category')} WHERE `url_rewrite_id` NOT IN (
            SELECT `url_rewrite_id` FROM {$this->resource->getTableName('url_rewrite')}
        );";
        $this->resource->getConnection()->query($sql);
    }

    /**
     * @param $storeId
     */
    public function createCategory($storeId, $output)
    {
        $step = 0;
        $categories = $this->categoryCollectionFactory->create()
            ->addAttributeToSelect('*')
            ->setStore($storeId)
            ->addFieldToFilter('level', array('gt' => '1'))
            ->setOrder('level', 'DESC');

        foreach ($categories as $category) {
            try {
                $category->setStoreId($storeId);
                $category->setOrigData('url_key', '');
                $category->save();

                $step++;
                $output->write('.');
                if ($step > 19) {
                    $output->writeln('');
                    $step = 0;
                }
            } catch (\Exception $e) {
                $output->writeln($e->getMessage());
            }
        }
    }
}
