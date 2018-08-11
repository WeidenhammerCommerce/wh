<?php
/**
 * Remove cache
 *
 * @package Hammer_WH
 * @author Sebastian De Cicco <seb.decc@gmail.com>
 * @copyright 2018 Sebastian De Cicco
 * @license OSL-3.0, AFL-3.0
 */

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

    protected $generated;
    protected $generatedCompany;

    protected $cssPath;
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
        $this->generated = $this->root.'/generated/';
        $this->generatedCompany = $this->root.'/generated/code/'.$this->storeInfo->getCompanyName().'/';

        $this->cssPath = 'frontend/THEMENAME/'.$this->storeInfo->getLocalization().'/css/';
        $this->themeStyles = $this->pubStatic.$this->cssPath;
        //$this->varViewPreprocessedCss = $this->varViewPreprocessed.'css/'.$this->cssPath;
        //$this->varViewPreprocessedPubCss = $this->varViewPreprocessed.'pub/static/'.$this->cssPath;
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
     * @param $theme
     */
    public function removeStyleCache($theme)
    {
        $pubCss = str_replace('THEMENAME', $theme, $this->themeStyles);

        if(is_dir($pubCss)){
            $this->deleteDirectory($pubCss);
        }

        $this->deleteDirectory($this->varCache);
        $this->deleteDirectory($this->varPageCache);
        $this->deleteDirectory($this->varViewPreprocessed);

        if($this->storeInfo->getMagentoCloud()) {
            $this->deleteDirectory($this->generated);
            //$this->deleteDirectory($this->generatedCompany);
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
        $this->deleteDirectory($this->generated);
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
     * Remove cache for Admin
     */
    public function removeAdminCache()
    {
        $this->deleteDirectory($this->pubStatic.'adminhtml');
        $this->deleteDirectory($this->varCache);
        $this->deleteDirectory($this->varPageCache);
        $this->deleteDirectory($this->varViewPreprocessed);
    }





    /**
     * Remove content for given folder
     * @param $path
     * @return bool
     */
    protected function deleteDirectory($path)
    {
        $files = glob($path . '/*');
        foreach ($files as $file) :
            if(is_dir($file)) :
                if ($file == '.' || $file == '..' || $file == '.htaccess') {
                    continue;
                }
                /*if (in_array($file, $this->storeInfo->getKeepFiles())) {
                    continue;
                }*/
                $this->deleteDirectory($file);
            else :
                unlink($file);
            endif;
        endforeach;

        if(!$this->dirIsEmpty($path)) {
            return true;
        }

        try {
            rmdir($path);
        } catch(\Exception $e) {
            echo 'Path: '.$path.'. Error: '.$e->getMessage();
            return false;
        }

        return true;
    }

    /**
     * Check if given folder is empty
     * @param $dir
     * @return bool
     */
    protected function dirIsEmpty($dir)
    {
        if (!is_dir($dir)) {
            return;
        }
        $handle = opendir($dir);
        while (false !== ($entry = readdir($handle))) {
            if ($entry != '.' && $entry != '..') {
                return false;
            }
        }
        return true;
    }
}