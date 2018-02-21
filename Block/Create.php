<?php
namespace Hammer\WH\Block;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\Module\Dir\Reader;
use Magento\User\Model\UserFactory;

use Hammer\WH\Settings\StoreInfo;

class Create
{
    protected $storeInfo;
    protected $directoryList;
    protected $root;
    protected $app;

    protected $filesystem;
    protected $io;
    protected $moduleReader;

    protected $whModulePath;
    protected $whDrafts;

    public function __construct(
        DirectoryList $directoryList,
        StoreInfo $storeInfo,
        Filesystem $filesystem,
        File $io,
        Reader $moduleReader
    )
    {
        $this->directoryList = $directoryList;
        $this->storeInfo = $storeInfo;
        $this->root = $this->directoryList->getRoot();
        $this->app = $this->directoryList->getPath('app');

        $this->filesystem = $filesystem;
        $this->io = $io;
        $this->moduleReader = $moduleReader;

        $this->whModulePath = $this->getModulePath('Hammer_WH').'/';
        $this->whDrafts = $this->whModulePath.'resources/drafts/';
    }



    public function createModule($name, $diClassName, $installOption) // $version, $composer
    {
        $draftSubFolder = 'module/';

        // Define module's variables
        $variables = array(
            'COMPANYNAMELOWER' => strtolower($this->storeInfo->getCompanyName()),
            'COMPANYNAME' => $this->storeInfo->getCompanyName(),
            'MODULENAMELOWER' => strtolower($name),
            'MODULENAME' => $name,
            'MODULEVERSION' => $this->storeInfo->getModuleVersion()
        );

        // Create main module folder
        $newModulePath = $this->app.'/code/'.$this->storeInfo->getCompanyName().'/'.$name.'/';
        $this->io->checkAndCreateFolder($newModulePath, 0775);

        // Create /registration.php
        $this->createNewFile(
            $this->whDrafts.$draftSubFolder.'registration.txt',
            $newModulePath.'registration.php',
            $variables
        );

        // Create /etc
        $this->io->checkAndCreateFolder($newModulePath.'etc', 0775);

        // Create /etc/module.xml
        $this->createNewFile(
            $this->whDrafts.$draftSubFolder.'module.txt',
            $newModulePath.'etc/module.xml',
            $variables
        );

        // Create Setup/InstallData.php
        if($installOption) {
            // Create /Setup
            $this->io->checkAndCreateFolder($newModulePath.'Setup', 0775);

            // Create /etc/InstallData.php
            $this->createNewFile(
                $this->whDrafts.$draftSubFolder.'installdata.txt',
                $newModulePath.'Setup/InstallData.php',
                $variables
            );
        }

        // Create /etc/di.xml & extended class
        if($diClassName) {

            // Create /etc/di.xml for Block or Model
            $variables['EXTENDFROM'] = $diClassName;

            $b = null;
            $m = null;
            $block = strpos($diClassName, 'Block');
            $model = strpos($diClassName, 'Model');

            if ($block !== false) {
                $b = explode('\Block\\', $diClassName);
            } elseif ($model !== false) {
                $m = explode('\Model\\', $diClassName);
            }

            if($b) {
                $variables['EXTENDTO'] = $this->storeInfo->getCompanyName().'\\'.
                    $name .'\Block\\'.
                    end($b);
            } elseif($m) {
                $variables['EXTENDTO'] = $this->storeInfo->getCompanyName().'\\'.
                    $name .'\Model\\'.
                    end($m);
            } else {
                return false;
            }

            $this->createNewFile(
                $this->whDrafts.$draftSubFolder.'di.txt',
                $newModulePath.'etc/di.xml',
                $variables
            );

            // Create path to the new class
            if($b) {
                $folders = explode('\\', $b[1]);
                array_unshift($folders, 'Block');
            } elseif($m) {
                $folders = explode('\\', $m[1]);
                array_unshift($folders, 'Model');
            }
            array_pop($folders); // remove Wishlist
            $finalPath = implode('/', $folders);
            $this->io->checkAndCreateFolder($newModulePath . $finalPath, 0775);

            // Create the class in the new path
            $namespace = explode('\\', $variables['EXTENDTO']);
            array_pop($namespace); // remove Wishlist
            $variables['NAMESPACENAME'] = implode('\\', $namespace);

            $newClass = explode('\\', $variables['EXTENDTO']);
            $className = array_pop($newClass);
            $variables['CLASSNAME'] = $className;

            $this->createNewFile(
                $this->whDrafts.$draftSubFolder.'extend.txt',
                $newModulePath.$finalPath.'/'.$className.'.php',
                $variables
            );
        }

        // Create /composer.json
        if($this->storeInfo->getComposerFile()) {
            $this->createNewFile(
                $this->whDrafts.$draftSubFolder.'composer.txt',
                $newModulePath.'composer.json',
                $variables
            );
        }

        return true;
    }

    public function createTheme($name, $extend)
    {
        $draftSubFolder = 'theme/';

        // Define theme's variables
        $variables = array(
            'COMPANYNAME' => $this->storeInfo->getCompanyName(),
            'THEMENAMELOWER' => strtolower($name),
            'THEMENAME' => $name,
            'EXTENDFROM' => $extend
        );

        // Create main module folder
        $newThemePath = $this->app.'/design/frontend/'.$this->storeInfo->getCompanyName().'/'.$name.'/';
        $this->io->checkAndCreateFolder($newThemePath, 0775);

        // Create /registration.php
        $this->createNewFile(
            $this->whDrafts.$draftSubFolder.'registration.txt',
            $newThemePath.'registration.php',
            $variables
        );

        // Create /theme.xml
        $this->createNewFile(
            $this->whDrafts.$draftSubFolder.'theme.txt',
            $newThemePath.'theme.xml',
            $variables
        );

        // Create /media
        $this->io->checkAndCreateFolder($newThemePath.'media', 0775);

        // Copy preview.jpg to /media
        $this->io->cp($this->whDrafts.$draftSubFolder.'preview.jpg', $newThemePath.'media/preview.jpg');

        // Create /web and subfolders
        $this->io->checkAndCreateFolder($newThemePath.'web/css/source', 0775);
        $this->io->checkAndCreateFolder($newThemePath.'web/fonts', 0775);
        $this->io->checkAndCreateFolder($newThemePath.'web/images', 0775);
        $this->io->checkAndCreateFolder($newThemePath.'web/js', 0775);

        // Create /web/css/source/_theme.less
        $this->createNewFile(
            $this->whDrafts.$draftSubFolder.'_theme.txt',
            $newThemePath.'web/css/source/_theme.less',
            $variables
        );

        // Create /web/css/source/_extend.less
        $this->createNewFile(
            $this->whDrafts.$draftSubFolder.'_extend.txt',
            $newThemePath.'web/css/source/_extend.less',
            $variables
        );
    }

    public function overrideTemplate($file, $theme)
    {
        // Get file
        $vFile = str_replace('code/vendor', 'vendor', $file);
        // ie of $vFile: vendor/magento/module-checkout/view/frontend/templates/cart.phtml

        // Get module name
        $m = explode('/', $vFile);
        $module = explode('module-', $m[2]);
        $module = end($module);
        $module = str_replace('-', ' ', $module);
        $module = ucwords($module);
        $module = str_replace(' ', '', $module); // ie: Checkout
        $module = 'Magento_'.$module;

        // Get template path
        $t = explode('view', $vFile);
        $template = end($t);
        $template = str_replace('frontend/', '', $template);
        $template = str_replace('base/', '', $template); // ie: /templates/cart.phtml

        // Get full path
        $fullPath = 'app/design/frontend/'.$this->storeInfo->getDefaultThemeCompany().'/'.$theme.'/';
        $fullPath .= $module;
        $fullPath .= $template;

        return $fullPath;
    }






    protected function getModulePath($moduleName, $subfolder = '')
    {
        return $this->moduleReader->getModuleDir($subfolder, $moduleName);
    }

    protected function createNewFile($draftFile, $newFile, $variables)
    {
        $draftContent = file_get_contents($draftFile);
        foreach($variables as $key => $value) {
            $draftContent = str_replace($key, $value, $draftContent);
        }

        $this->io->write(
            $newFile,
            $draftContent,
            0666
        );
    }

    protected function convertCamel($string, $type = 'title')
    {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $string, $matches);
        $ret = $matches[0];

        foreach ($ret as &$match) {
            $match = $match == strtoupper($match) ?
                strtolower($match) :
                ($type == 'title' ? ucfirst($match) : lcfirst($match));
        }

        $glue = $type == 'title' ? ' ' : '_';

        return implode($glue, $ret);
    }

}