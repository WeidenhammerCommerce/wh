<?php
namespace Hammer\WH\Settings;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\View\DesignInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreRepository;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\View\Design\Theme\ThemeProviderInterface;

class StoreInfo
{
    protected $deploymentConfig;
    protected $scopeConfig;
    protected $storeRepository;
    protected $storeManager;
    protected $themeProvider;

    public function __construct(
        DeploymentConfig $deploymentConfig,
        ScopeConfigInterface $scopeConfig,
        StoreRepository $storeRepository,
        StoreManagerInterface $storeManager,
        ThemeProviderInterface $themeProvider
    )
    {
        $this->deploymentConfig = $deploymentConfig;
        $this->scopeConfig = $scopeConfig;
        $this->storeRepository = $storeRepository;
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

    public function getDefaultFullThemeName()
    {
        $themeData = $this->getThemeData();
        if(array_key_exists('theme_path', $themeData)) {
            return $themeData['theme_path'];
        }
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
        return $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB);
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
        return $this->deploymentConfig->get('wh/company_name');
    }

    public function getDefaultTheme()
    {
        return $this->deploymentConfig->get('wh/default_theme');
    }

    public function getLocalization()
    {
        return $this->deploymentConfig->get('wh/localization');
    }

    /*public function getKeepFiles()
    {
        return self::KEEP_FILES;
    }*/

    public function getComposerFile()
    {
        return $this->deploymentConfig->get('wh/composer_files');
    }

    public function getModuleVersion()
    {
        return $this->deploymentConfig->get('wh/module_version');
    }

    public function getDefaultDummyCategoriesQty()
    {
        return $this->deploymentConfig->get('wh/dummy_categories');
    }

    public function getDefaultDummyProductsQty()
    {
        return $this->deploymentConfig->get('wh/dummy_products');
    }

    public function getAskIfMultistore()
    {
        return $this->deploymentConfig->get('wh/ask_if_multistore');
    }




    public function getAllStores()
    {
        return $this->storeRepository->getList();
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
