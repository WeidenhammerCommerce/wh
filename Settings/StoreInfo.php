<?php
namespace Hammer\WH\Settings;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\DesignInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\View\Design\Theme\ThemeProviderInterface;

class StoreInfo
{
    /* Set your custom variables here */
    const COMPANY_NAME = 'MyCompanyName'; // folder where you place your modules (app/code/[COMPANY_NAME])
    /* ------------------------------ */
    const LOCALIZATION = 'en_US'; // folder name under pub/static/frontend/COMPANY/THEME/[LOCALIZATION]
    const KEEP_FILES = array('.htaccess'); // files you don't want to remove when clearing the cache
    const COMPOSER_FILE = true; // if true, it creates a composer file within the new modules
    const MODULE_VERSION = '0.0.1'; // default version of new modules
    const DUMMY_CATEGORIES = 1; // quantity of dummy categories by default
    const DUMMY_PRODUCTS = 1; // quantity of dummy products by default
    /* ------------------------------ */

    protected $scopeConfig;
    protected $storeManager;
    protected $themeProvider;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        ThemeProviderInterface $themeProvider
    )
    {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->themeProvider = $themeProvider;
    }



    /**
     * Get Theme Data
     */
    public function getDefaultThemeId()
    {
        $themeData = $this->getThemeData();
        return $themeData['theme_id'];
    }

    public function getDefaultThemeCompany()
    {
        $themeData = $this->getThemeData();
        if(array_key_exists('theme_path', $themeData)) {
            $segments = explode('/', $themeData['theme_path']);
            $company = reset($segments);
        } else {
            $company = null;
        }

        return $company;
    }

    public function getDefaultThemeName()
    {
        $themeData = $this->getThemeData();
        $segments = explode('/', $themeData['theme_path']);
        $theme = end($segments);

        return $theme;
    }

    public function getDefaultThemePath()
    {
        $themeData = $this->getThemeData();
        return $themeData['theme_path'];
    }



    /**
     * Get Store Data
     */
    public function getDefaultStoreId()
    {
        return $this->storeManager->getStore()->getId();
    }

    public function getDefaultStoreName()
    {
        return $this->storeManager->getStore()->getName();
    }

    public function getDefaultStoreUrl()
    {
        return $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_WEB);
    }

    /**
     * Get all theme data
     * @return mixed
     */
    protected function getThemeData()
    {
        $themeId = $this->scopeConfig->getValue(
            DesignInterface::XML_PATH_THEME_ID,
            ScopeInterface::SCOPE_STORE,
            $this->storeManager->getStore()->getId()
        );
        $theme = $this->themeProvider->getThemeById($themeId);

        return $theme->getData();
    }

    /**
     * Get variables
     */
    public function getCompanyName()
    {
        return self::COMPANY_NAME;
    }

    public function getLocalization()
    {
        return self::LOCALIZATION;
    }

    public function getKeepFiles()
    {
        return self::KEEP_FILES;
    }

    public function getComposerFile()
    {
        return self::COMPOSER_FILE;
    }

    public function getModuleVersion()
    {
        return self::MODULE_VERSION;
    }

    public function getDefaultDummyCategoriesQty()
    {
        return self::DUMMY_CATEGORIES;
    }

    public function getDefaultDummyProductsQty()
    {
        return self::DUMMY_PRODUCTS;
    }

    

    public function isMultistore()
    {
        $storeManagerDataList = $this->storeManager->getStores();
        $options = array();

        foreach ($storeManagerDataList as $key => $value) {
            if($value['is_active']) {
                $options[] = ['label' => $value['name'] . ' - ' . $value['code'], 'value' => $key];
            }
        }

        return count($options) > 1 ? true : false;
    }
}
