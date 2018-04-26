<?php
/**
 * Show a 'View Product' link at the top of the
 * Admin > Products > Catalog > [Product] page
 *
 * @package Hammer_WH
 * @author Sebastian De Cicco <seb.decc@gmail.com>
 * @copyright 2018 Sebastian De Cicco
 * @license OSL-3.0, AFL-3.0
 */

namespace Hammer\WH\Block;

use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\Registry;
use Hammer\WH\Settings\StoreInfo;

class ViewProduct extends \Magento\Framework\View\Element\Template
{
    protected $registry;
    protected $storeInfo;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        StoreInfo $storeInfo,
        array $data = []
    )
    {
        $this->registry = $registry;
        $this->storeInfo = $storeInfo;
        parent::__construct($context, $data);
    }


    /**
     * @return mixed|null
     */
    public function getViewProduct()
    {
        return $this->storeInfo->getViewProduct();
    }

    /**
     * @return mixed
     */
    public function getCurrentProduct()
    {
        return $this->registry->registry('current_product');
    }
}