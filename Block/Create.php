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
    protected $extensions;

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
        $this->app = $this->root.'/app';
        $this->extensions = $this->root.'/extensions';

        $this->filesystem = $filesystem;
        $this->io = $io;
        $this->moduleReader = $moduleReader;

        $this->whModulePath = $this->getModulePath('Hammer_WH').'/';
        $this->whDrafts = $this->whModulePath.'resources/drafts/';
    }



    public function createModule($name, $setupFiles, $feature)
    {
        $draftSubFolder = 'module/';

        // Define module's variables
        $variables = array(
            '{COMPANYNAMELOWER}' => strtolower($this->storeInfo->getCompanyName()),
            '{COMPANYNAME}' => $this->storeInfo->getCompanyName(),
            '{MODULENAMELOWER}' => strtolower($name),
            '{MODULENAME}' => $name,
            '{MODULEVERSION}' => $this->storeInfo->getModuleVersion()
        );

        // Create main module folder
        $newModulePath = !$this->storeInfo->getMagentoCloud() ?
            $this->app.'/code/'.$this->storeInfo->getCompanyName().'/'.$name.'/' :
            $this->extensions.'/'.$this->storeInfo->getCompanyName().'/'.$name.'/';
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
        if($setupFiles) {
            // Create /Setup
            $this->io->checkAndCreateFolder($newModulePath.'Setup', 0775);

            // Create /Setup files
            foreach($setupFiles as $setupFile) {
                $this->createNewFile(
                    $this->whDrafts.$draftSubFolder.strtolower($setupFile).'.txt',
                    $newModulePath.'Setup/'.$setupFile.'.php',
                    $variables
                );
            }
        }

        // Create the selected feature, if any
        switch($feature['selected']) :
            // None
            case '0' :
                break;
            // Extend Block/Model class with di.xml
            case '1' :
                // Create /etc/di.xml for Block or Model
                $variables['{EXTENDFROM}'] = $feature['class'];

                // Get folder & file
                $f = explode('\\', $feature['class']);
                $folder = $f[2];
                $file = explode('\\'.$folder.'\\', $feature['class']);

                $variables['{EXTENDTO}'] = $this->storeInfo->getCompanyName() . '\\' .
                    $name . '\\'.$folder.'\\' .
                    end($file);

                $this->createNewFile(
                    $this->whDrafts . $draftSubFolder . 'di.txt',
                    $newModulePath . 'etc/di.xml',
                    $variables
                );

                // Create path to the new class
                $folders = explode('\\', end($file));
                array_unshift($folders, $folder);
                array_pop($folders); // remove Wishlist
                $finalPath = implode('/', $folders);
                $this->io->checkAndCreateFolder($newModulePath . $finalPath, 0775);

                // Create the class in the new path
                $namespace = explode('\\', $variables['{EXTENDTO}']);
                array_pop($namespace); // remove Wishlist
                $variables['{NAMESPACENAME}'] = implode('\\', $namespace);

                $newClass = explode('\\', $variables['{EXTENDTO}']);
                $className = array_pop($newClass);
                $variables['{CLASSNAME}'] = $className;

                $this->createNewFile(
                    $this->whDrafts . $draftSubFolder . 'extend.txt',
                    $newModulePath . $finalPath . '/' . $className . '.php',
                    $variables
                );
                break;

            // Create Plugin for a method with di.xml
            case '2' :
                // Create /etc/di.xml for Block or Model
                $variables['{EXTENDFROM}'] = $feature['class'];

                // hammer_format_currency_plugin
                $pluginName = $this->storeInfo->getCompanyName().'_'.$feature['method'].'_plugin';
                $variables['{PLUGIN_NAME}'] = strtolower($pluginName);

                // Hammer\PriceFormat\Plugin\FormatPricePlugin
                $ucwordsMethod = ucwords($feature['method']);
                $pluginType = $this->storeInfo->getCompanyName().'\\'.$name.'\\Plugin\\'.$ucwordsMethod.'Plugin';

                switch($feature['when']) :
                    case '0' :
                        $when = 'before';
                        break;
                    case '1' :
                        $when = 'after';
                        break;
                    case '2' :
                        $when = 'around';
                        break;
                endswitch;

                $variables['{PLUGIN_NAME_UCWORDS}'] = $ucwordsMethod;
                $variables['{PLUGIN_TYPE}'] = $pluginType;
                $variables['{PLUGIN_WHEN}'] = $when;

                $this->createNewFile(
                    $this->whDrafts . $draftSubFolder . 'di_plugin.txt',
                    $newModulePath . 'etc/di.xml',
                    $variables
                );

                // Create /Plugin
                $this->io->checkAndCreateFolder($newModulePath.'Plugin', 0775);

                // Create /Plugin/{Method}Plugin.php
                $this->createNewFile(
                    $this->whDrafts . $draftSubFolder . 'plugin.txt',
                    $newModulePath . '/Plugin/' . $ucwordsMethod . 'Plugin.php',
                    $variables
                );

                break;


            // Create frontend page with Controller to display template
            case '3' :
                // Create /etc/frontend
                $this->io->checkAndCreateFolder($newModulePath.'etc/frontend', 0775);

                // Create /etc/InstallData.php
                $variables['{IDNAME}'] = $feature['newpage'];
                $variables['{FRONTNAME}'] = $feature['newpage'];
                $this->createNewFile(
                    $this->whDrafts.$draftSubFolder.'routes.txt',
                    $newModulePath.'etc/frontend/routes.xml',
                    $variables
                );

                // Create /Controller/Index/Index.php
                $this->io->checkAndCreateFolder($newModulePath.'Controller/Index', 0775);

                // Create /etc/frontend/routers.xml
                $this->createNewFile(
                    $this->whDrafts.$draftSubFolder.'controller_template.txt',
                    $newModulePath.'Controller/Index/Index.php',
                    $variables
                );

                // Create /view/frontend/layout
                $this->io->checkAndCreateFolder($newModulePath.'view/frontend/layout', 0775);

                // Create /view/frontend/layout/{frontname}_index_index.xml
                $this->createNewFile(
                    $this->whDrafts.$draftSubFolder.'controller_layout.txt',
                    $newModulePath.'view/frontend/layout/'.$feature['newpage'].'_index_index.xml',
                    $variables
                );

                // Create /view/frontend/templates
                $this->io->checkAndCreateFolder($newModulePath.'view/frontend/templates', 0775);

                // Create /view/frontend/templates/{frontname}.phtml
                $this->createNewFile(
                    $this->whDrafts.$draftSubFolder.'template.txt',
                    $newModulePath.'view/frontend/templates/'.$feature['newpage'].'.phtml',
                    $variables
                );

                // Create /Block
                $this->io->checkAndCreateFolder($newModulePath.'Block', 0775);

                // Create /Block/Index.php
                $this->createNewFile(
                    $this->whDrafts.$draftSubFolder.'block.txt',
                    $newModulePath.'Block/Index.php',
                    $variables
                );

                break;

            // Create frontend page with Controller to display template using view-model
            case '4' :
                // Create /etc/frontend
                $this->io->checkAndCreateFolder($newModulePath.'etc/frontend', 0775);

                // Create /etc/frontend/routes.xml
                $variables['{IDNAME}'] = $feature['newpage'];
                $variables['{FRONTNAME}'] = $feature['newpage'];
                $this->createNewFile(
                    $this->whDrafts.$draftSubFolder.'routes.txt',
                    $newModulePath.'etc/frontend/routes.xml',
                    $variables
                );

                // Create /Controller/Index/Index.php
                $this->io->checkAndCreateFolder($newModulePath.'Controller/Index', 0775);

                // Create /etc/frontend/routers.xml
                $this->createNewFile(
                    $this->whDrafts.$draftSubFolder.'controller_template.txt',
                    $newModulePath.'Controller/Index/Index.php',
                    $variables
                );

                // Create /view/frontend/layout
                $this->io->checkAndCreateFolder($newModulePath.'view/frontend/layout', 0775);

                // Create /view/frontend/layout/{frontname}_index_index.xml
                $this->createNewFile(
                    $this->whDrafts.$draftSubFolder.'viewmodel_layout.txt',
                    $newModulePath.'view/frontend/layout/'.$feature['newpage'].'_index_index.xml',
                    $variables
                );

                // Create /view/frontend/templates
                $this->io->checkAndCreateFolder($newModulePath.'view/frontend/templates', 0775);

                // Create /view/frontend/templates/{frontname}.phtml
                $this->createNewFile(
                    $this->whDrafts.$draftSubFolder.'viewmodel_template.txt',
                    $newModulePath.'view/frontend/templates/'.$feature['newpage'].'.phtml',
                    $variables
                );

                // Create /Block
                $this->io->checkAndCreateFolder($newModulePath.'Block', 0775);

                // Create /Block/Index.php
                $this->createNewFile(
                    $this->whDrafts.$draftSubFolder.'viewmodel_block.txt',
                    $newModulePath.'Block/Index.php',
                    $variables
                );

                break;

            // Create frontend page with Controller to return JSON
            case '5' :
                // Create /etc/frontend
                $this->io->checkAndCreateFolder($newModulePath.'etc/frontend', 0775);

                // Create /etc/frontend/routes.xml
                $variables['{IDNAME}'] = $feature['newpage'];
                $variables['{FRONTNAME}'] = $feature['newpage'];
                $this->createNewFile(
                    $this->whDrafts.$draftSubFolder.'routes.txt',
                    $newModulePath.'etc/frontend/routes.xml',
                    $variables
                );

                // Create /Controller
                $this->io->checkAndCreateFolder($newModulePath.'Controller/Index', 0775);

                // Create /etc/frontend/routers.xml
                $this->createNewFile(
                    $this->whDrafts.$draftSubFolder.'controller_json.txt',
                    $newModulePath.'Controller/Index/Index.php',
                    $variables
                );
                break;

            // Attach Observer to Event
            case '6' :
                $variables['{EVENT}'] = $feature['event'];
                $variables['{OBSERVERLOWER}'] = strtolower($feature['observer']);
                $variables['{OBSERVER}'] = $feature['observer'];

                // Create /etc/events.xml
                $this->createNewFile(
                    $this->whDrafts . $draftSubFolder . 'events.txt',
                    $newModulePath . 'etc/events.xml',
                    $variables
                );

                // Create /Observer
                $this->io->checkAndCreateFolder($newModulePath.'Observer', 0775);

                // Create /Observer/{Observer}.php
                $this->createNewFile(
                    $this->whDrafts . $draftSubFolder . 'observer.txt',
                    $newModulePath . 'Observer/'.$feature['observer'].'.php',
                    $variables
                );
                break;

            // Replace constructor argument
            case '7' :
                $variables['{OLDCLASS}'] = $feature['class'];
                $variables['{VARIABLE}'] = str_replace('$', '', $feature['variable']);

                // Get folder & file
                $f = explode('\\', $feature['class']);
                $folder = $f[2];
                $file = explode('\\'.$folder.'\\', $feature['class']);

                $variables['{NEWCLASS}'] = $this->storeInfo->getCompanyName() . '\\' .
                    $name . '\\'.$folder.'\\' .
                    end($file);

                // Create /etc/di.xml
                $this->createNewFile(
                    $this->whDrafts . $draftSubFolder . 'di_constructor_argument.txt',
                    $newModulePath . 'etc/di.xml',
                    $variables
                );

                // Create path to the new class
                $folders = explode('\\', end($file));
                array_unshift($folders, $folder);
                array_pop($folders);
                $finalPath = implode('/', $folders);
                $this->io->checkAndCreateFolder($newModulePath . $finalPath, 0775);

                // Create class file in the new path
                $namespace = explode('\\', $variables['{NEWCLASS}']);
                array_pop($namespace);
                $variables['{NAMESPACENAME}'] = implode('\\', $namespace);

                $newClass = explode('\\', $variables['{NEWCLASS}']);
                $className = array_pop($newClass);
                $variables['{CLASSNAME}'] = $className;

                // Create /Block|Model/NewClass.php
                $this->createNewFile(
                    $this->whDrafts . $draftSubFolder . 'block_constructor_argument.txt',
                    $newModulePath . $finalPath . '/' . $className . '.php',
                    $variables
                );
                break;

            // Create new Command line
            case '8' :
                // Set variables
                $variables['{COMMAND}'] = $feature['command'];
                $variables['{COMMAND_UCWORDS}'] = ucwords($feature['command']);

                // Create /etc/di.xml
                $this->createNewFile(
                    $this->whDrafts . $draftSubFolder . 'di_command.txt',
                    $newModulePath . 'etc/di.xml',
                    $variables
                );

                // Create /Console
                $this->io->checkAndCreateFolder($newModulePath.'Console', 0775);

                // Create /Console/{Command}.php
                $this->createNewFile(
                    $this->whDrafts . $draftSubFolder . 'command_line.txt',
                    $newModulePath . 'Console/'.ucwords($feature['command']).'.php',
                    $variables
                );
                break;

            // Create REST API with ACL
            case '9' :
                // Set variables
                $variables['{KEY}'] = $feature['key'];
                $variables['{KEY_UCWORDS}'] = ucwords($feature['key']);

                // Create /etc/webapi.xml
                $this->createNewFile(
                    $this->whDrafts . $draftSubFolder . 'webapi.txt',
                    $newModulePath . 'etc/webapi.xml',
                    $variables
                );

                // Create /etc/acl.xml
                $this->createNewFile(
                    $this->whDrafts . $draftSubFolder . 'acl.txt',
                    $newModulePath . 'etc/acl.xml',
                    $variables
                );

                // Create /etc/di.xml
                $this->createNewFile(
                    $this->whDrafts . $draftSubFolder . 'di_rest.txt',
                    $newModulePath . 'etc/di.xml',
                    $variables
                );

                // Create /Api
                $this->io->checkAndCreateFolder($newModulePath.'Api', 0775);

                // Create /Api/{Key}Interface.php
                $this->createNewFile(
                    $this->whDrafts . $draftSubFolder . 'rest_interface.txt',
                    $newModulePath . 'Api/'.ucwords($feature['key']).'Interface.php',
                    $variables
                );

                // Create /Model
                $this->io->checkAndCreateFolder($newModulePath.'Model', 0775);

                // Create /Model/{Key}.php
                $this->createNewFile(
                    $this->whDrafts . $draftSubFolder . 'rest_model.txt',
                    $newModulePath . 'Model/'.ucwords($feature['key']).'.php',
                    $variables
                );
                break;

            default:
                break;
        endswitch;

        // Create /composer.json
        if($this->storeInfo->getComposerFile() || $this->storeInfo->getMagentoCloud()) {
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
            '{COMPANYNAME}' => $this->storeInfo->getCompanyName(),
            '{THEMENAMELOWER}' => strtolower($name),
            '{THEMENAME}' => $name,
            '{EXTENDFROM}' => $extend
        );

        // Create main theme folder
        $newThemePath = !$this->storeInfo->getMagentoCloud() ?
            $this->app.'/design/frontend/'.$this->storeInfo->getCompanyName().'/'.$name.'/' :
            $this->extensions.'/'.$this->storeInfo->getCompanyName().'/'.$name.'/';
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
        // ie1: vendor/magento/module-checkout/view/frontend/templates/cart.phtml
        // ie2: vendor/magento/module-catalog-search/view/frontend/templates/advanced/result.phtml

        // Get module name
        $m = explode('/', $vFile);
        $module = explode('module-', $m[2]);
        $module = end($module);
        $module = str_replace('-', ' ', $module);
        $module = ucwords($module);
        $module = str_replace(' ', '', $module); // ie: Checkout
        $module = 'Magento_'.$module; // ie: Magento_Checkout or Magento_CatalogSearch

        // Get template path
        $t = explode('/templates/', $vFile);
        $templateFolder = explode('/', $t[1]);
        $file = end($templateFolder); // ie: cart.phtml or result.phtml
        $templateFolder = str_replace($file, '', $t[1]); // ie: '' or advanced/

        $friendlyPath = !$this->storeInfo->getMagentoCloud() ?
            'app/design/frontend' :
            'extensions';

        // Get full path
        $newThemePath = !$this->storeInfo->getMagentoCloud() ?
            $this->app.'/design/frontend' :
            $this->extensions;

        // Prepare paths
        $fullPath = $newThemePath.'/'.$theme.'/'.$module.'/templates/'.$templateFolder;
        $newTemplatePath = $fullPath . $file;

        if(file_exists($newTemplatePath)) {
            return;
        }

        // Create folders
        $check = $this->io->checkAndCreateFolder($fullPath, 0775);

        // Copy the template file
        $this->copyFile(
            $vFile,
            $newTemplatePath
        );

        // Return friendly path
        $fullFriendlyPath = $friendlyPath.'/'.$theme.'/'.$module.'/templates/'.$templateFolder.$file;
        return $fullFriendlyPath;
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

    protected function copyFile($fromFile, $toFile)
    {
        $fromContent = file_get_contents($fromFile);

        $this->io->write(
            $toFile,
            $fromContent,
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