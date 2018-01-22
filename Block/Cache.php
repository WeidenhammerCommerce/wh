<?php
namespace Hammer\WH\Block;

use Magento\Framework\App\Filesystem\DirectoryList;

use Hammer\WH\Settings\StoreInfo;

class Cache
{
    protected $directoryList;
    protected $storeInfo;
    protected $root;

    protected $varCache;
    protected $varPageCache;
    protected $varGeneration;
    protected $varViewPreprocessed;
    protected $pubStatic;
    protected $themeStyles;

    public function __construct(
        DirectoryList $directoryList,
        StoreInfo $storeInfo
    )
    {
        $this->directoryList = $directoryList;
        $this->storeInfo = $storeInfo;

        $this->root = $this->directoryList->getRoot();
        $this->varCache = $this->root.'/var/cache/';
        $this->varPageCache = $this->root.'/var/page_cache/';
        $this->varGeneration = $this->root.'/var/generation/';
        $this->varViewPreprocessed = $this->root.'/var/view_preprocessed/';
        $this->pubStatic = $this->root.'/pub/static/';
        $this->themeStyles = $this->pubStatic.'frontend/'.$this->storeInfo->getDefaultThemeCompany().'/THEMENAME/'.$this->storeInfo->getLocalization().'/css/';
    }



    /**
     * Remove cache for Templates & Layouts
     */
    public function removeBasicCache()
    {
        $this->deleteDirectory($this->varCache);
        $this->deleteDirectory($this->varPageCache);
    }
    /**
     * Remove cache for Templates & Layouts
     * @param $themeRoot
     */
    public function removeStyleCache($theme)
    {
        $pubCss = str_replace('THEMENAME', $theme, $this->themeStyles);

        if(!is_dir($pubCss)){
            return false;
        } else {
            $this->deleteDirectory($pubCss);
            $this->deleteDirectory($this->varCache);
            $this->deleteDirectory($this->varPageCache);
            $this->deleteDirectory($this->varViewPreprocessed);
            return true;
        }
    }


    /**
     * Remove cache for Templates & Layouts
     */
    public function removeAllCache()
    {
        $this->deleteDirectory($this->pubStatic);
        $this->deleteDirectory($this->varCache);
        $this->deleteDirectory($this->varPageCache);
        $this->deleteDirectory($this->varViewPreprocessed);
        $this->deleteDirectory($this->varGeneration);
    }


    /**
     * Remove custom cache
     * @param $selectedCache
     */
    public function removeCustomCache($selectedCache)
    {
        $varRoot = $this->root . '/var/';
        foreach ($selectedCache as $c) {
            $this->deleteDirectory($varRoot . $c . '/');
        }
    }


    /**
     * Remove content for given folder
     * @param $dir
     * @return bool
     */
    protected function deleteDirectory($dir)
    {
        if (!file_exists($dir)) {
            return true;
        }
        if (!is_dir($dir) || is_link($dir)) {
            return unlink($dir);
        }

        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }
            if (in_array($item, $this->storeInfo->getKeepFiles())) {
                continue;
            }
            if (!$this->deleteDirectory($dir . "/" . $item, false)) {
                chmod($dir . "/" . $item, 0777);
                if (!$this->deleteDirectory($dir . "/" . $item, false)) {
                    return false;
                }
            }
        }

        return true;
    }
}