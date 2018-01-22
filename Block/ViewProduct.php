<?php
/**
 * Show a "View Product" link at the top of the
 * Admin > Products > Catalog > [Product] page
 */
namespace Hammer\WH\Block;

class ViewProduct extends \Magento\Framework\View\Element\Template
{
    /**
     * core registry
     * @var string
     */
    protected $_registry;

    /**
     * ViewProduct constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    )
    {
        $this->_registry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * @return mixed
     */
    public function getCurrentProduct()
    {
        return $this->_registry->registry('current_product');
    }
}