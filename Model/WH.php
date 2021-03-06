<?php
/**
 * WH ¯\_(ツ)_/¯
 * Creates a new shell command with a handful of M2 tools
 *
 * @todo
 * - Add try catch
 * - Add command to set env.php variables
 * - o:t -> copy template automatically
 *
 * Command resources:
 * https://symfony.com/doc/2.7/components/console/helpers/dialoghelper.html
 *
 * @copyright Copyright (c) 2018 - Sebastian De Cicco
 */
namespace Hammer\WH\Model;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

use Magento\Framework\App\State;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\DeploymentConfig;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\Module\ModuleList;
use Magento\Framework\Module\FullModuleList;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File;

use Magento\Framework\App\ResourceConnection;
use Magento\Customer\Model\Customer;
use Magento\User\Model\UserFactory;

use Hammer\WH\Settings\StoreInfo;
use Hammer\WH\Block\Cache;
use Hammer\WH\Block\Create;
use Hammer\WH\Block\Dummy;
use Hammer\WH\Block\Regenerate;

class WH extends Command
{
    const COMMAND = 'wh';

    protected $appState;
    protected $productMetadata;
    protected $deploymentConfig;
    protected $moduleList;
    protected $fullModuleList;
    protected $directoryList;
    protected $file;

    protected $resource;
    protected $config;
    protected $customer;
    protected $userFactory;

    protected $storeInfo;
    protected $cache;
    protected $create;
    protected $dummy;
    protected $regenerate;

    public function __construct(
        State $appState,
        ProductMetadataInterface $productMetadata,
        DeploymentConfig $deploymentConfig,
        ModuleList $moduleList,
        FullModuleList $fullModuleList,
        DirectoryList $directoryList,
        File $file,
        ResourceConnection $resource,
        Config $config,
        Customer $customer,
        UserFactory $userFactory,
        StoreInfo $storeInfo,
        Cache $cache,
        Create $create,
        Dummy $dummy,
        Regenerate $regenerate
    )
    {
        $this->appState = $appState;
        $this->productMetadata = $productMetadata;
        $this->deploymentConfig = $deploymentConfig;
        $this->moduleList = $moduleList;
        $this->fullModuleList = $fullModuleList;
        $this->directoryList = $directoryList;
        $this->file = $file;

        $this->resource = $resource;
        $this->config = $config;
        $this->customer = $customer;
        $this->userFactory = $userFactory;

        $this->storeInfo = $storeInfo;
        $this->cache = $cache;
        $this->create = $create;
        $this->dummy = $dummy;
        $this->regenerate = $regenerate;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName(self::COMMAND)
            ->setDefinition(
                array(
                    new InputArgument('action', InputArgument::OPTIONAL, 'The custom argument', null)
                )
            )
            ->setDescription('Creates a new shell command with a handful of M2 tools')
            ->setHelp(<<<EOF
<comment>Info</comment>
$ %command.full_name% <info>info:m2 (i:m2)</info> Shows information of the M2 instance
$ %command.full_name% <info>info:store (i:s)</info> Shows information of all of the stores
$ %command.full_name% <info>info:modules (i:m)</info> <question>[type of modules]</question> List modules (with its code version)
<comment>Cache</comment>
$ %command.full_name% <info>clean:templates (c:t)</info> Removes the specific cache to regenerate the templates
$ %command.full_name% <info>clean:layouts (c:l)</info> Removes the specific cache to regenerate the layouts
$ %command.full_name% <info>clean:config (c:c)</info> Removes the specific cache after changing admin configurations
$ %command.full_name% <info>clean:var (c:v)</info> Removes var/cache & var/page_cache
$ %command.full_name% <info>clean:generated (c:g)</info> Removes the specific cache to regenerate the DI
$ %command.full_name% <info>clean:styles (c:s)</info> <question>[name of theme]</question> Removes the specific cache to regenerate the CSS styles
$ %command.full_name% <info>clean:all (c:a)</info> Removes all cache (everything within /var and /pub/static)
$ %command.full_name% <info>clean:custom (c:cu)</info> Removes selected cache (separated by comma)
$ %command.full_name% <info>clean:admin (c:ad)</info> Removes the specific cache to regenerate the admin
<comment>Creation</comment>
$ %command.full_name% <info>create:module (cr:m)</info> <question>[module options]</question> Creates a new module
$ %command.full_name% <info>create:theme (cr:t)</info> <question>[theme options]</question> Creates a new theme
$ %command.full_name% <info>create:dummy (cr:d)</info> <question>[quantities]</question> Creates dummy categories and products
<comment>Customer</comment>
$ %command.full_name% <info>customer:create (c:cr)</info> <question>[customer data]</question> Creates a customer
$ %command.full_name% <info>customer:password (c:p)</info> <question>[email and new password]</question> Updates the password of an existing customer
<comment>Admin</comment>
$ %command.full_name% <info>admin:create (a:cr)</info> <question>[user data]</question> Creates an admin user
$ %command.full_name% <info>admin:password (a:p)</info> <question>[email and new password]</question> Updates the password of an existing admin user
<comment>Frontend Tools</comment>
$ %command.full_name% <info>tools:static (t:s)</info> <question>[name of theme]</question> Deploy static content
$ %command.full_name% <info>override:template (o:t)</info> <question>[name of theme, path to template]</question> Copy core template to theme to override it
$ %command.full_name% <info>hints:on (h:on)</info> Enables the Template Hints
$ %command.full_name% <info>hints:off (h:off)</info> Disables the Template Hints
<comment>Other Tools</comment>
$ %command.full_name% <info>cloud</info> List of Magento Cloud commands
$ %command.full_name% <info>dump</info> Creates dump of the database
$ %command.full_name% <info>module:downgrade (m:d)</info> <question>[name of module]</question> Downgrades version of the database module to the one on the code (useful after changing branches)
$ %command.full_name% <info>tools:regenerate (t:r)</info> <question>[store]</question> Regenerate URL rewrites of products/categories in all/specific store/s
$ %command.full_name% <info>deploy:mode (d:m)</info> <question>[mode name]</question> Deploy to given mode (show, developer or production) 
$ %command.full_name% <info>snippets (sn)</info> <question>[snippet]</question> Show M2 snippets

EOF
            );

        parent::configure();
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    )
    {
        try {
            $this->appState->setAreaCode('adminhtml');
        } catch (\Exception $exception) {
        }

        /* Load custom shell colors */
        $this->setCustomStyles($output);



        switch($input->getArgument('action')) :

            /**
             * INFO
             **********************************************************************************************************/

            /**
             * Shows information of Magento 2 instance
             */
            case 'info:m2' :
            case 'i:m2' :
                $output->writeln('
<title>Magento 2 Information</title>
<info>Name:</info> '.$this->productMetadata->getName().'
<info>Edition:</info> '.$this->productMetadata->getEdition().'
<info>Version:</info> '.$this->productMetadata->getVersion().'
<info>Mode:</info> '.$this->deploymentConfig->get(State::PARAM_MODE).'
<info>Session:</info> '.$this->deploymentConfig->get('session/save').'
<info>Crypt Key:</info> '.$this->deploymentConfig->get('crypt/key').'
<info>Install Date:</info> '.$this->deploymentConfig->get('install/date'));
                $output->writeln('');
                break;


            /**
             * Shows information of all of the Stores
             */
            case 'info:store' :
            case 'i:store' : case 'info:s' :
            case 'i:s' :
                $stores = $this->storeInfo->getAllStores();

                $output->writeln('
<title>Store Information</title>');

                if(count($stores) > 1) {
                    foreach ($stores as $store) {
                        if ($store->getName() !== 'Admin') { // admin
                            $output->writeln('
<info>Store ID:</info> '.$store->getId().'
<info>Store Title:</info> '.$store->getName().'
<info>Store Code:</info> '.$store->getCode());
                        }
                    }
                } else {
                    $output->writeln('The are no stores created yet.');
                }
                $output->writeln('');
                break;


            /**
             * List all of the company modules
             */
            case 'info:modules' :
            case 'i:modules' : case 'info:m' :
            case 'i:m' :
                // Show list of enabled company modules
                $output->writeln('<info>Enabled modules:</info>');
                $enabledQty = 0;

                // Module type
                $moduleType = array(
                    'All modules', // 0
                    'Magento modules', // 1
                    'All except Magento modules', // 2
                    'My company modules' // 3
                );
                $dialog = $this->getHelper('dialog');
                $selectedType = $dialog->select(
                    $output,
                    'Select the type of modules to list (<comment>Enter</comment> to show all):',
                    $moduleType,
                    0,
                    false,
                    'Value "%s" is invalid',
                    false // enable multiselect
                );
                $output->writeln('');

                foreach ($this->moduleList->getAll() as $m) {
                    switch($selectedType) :
                        case 0 :
                            $enabledQty++;
                            $output->writeln($m['name'] . ' -> ' . $m['setup_version']);
                            break;
                        case 1 :
                            if (strpos($m['name'], 'Magento_') !== false) {
                                $enabledQty++;
                                $output->writeln($m['name'] . ' -> ' . $m['setup_version']);
                            }
                            break;
                        case 2 :
                            if (strpos($m['name'], 'Magento_') === false) {
                                $enabledQty++;
                                $output->writeln($m['name'] . ' -> ' . $m['setup_version']);
                            }
                            break;
                        case 3 :
                            if (strpos($m['name'], $this->storeInfo->getCompanyName()) !== false) {
                                $enabledQty++;
                                $output->writeln($m['name'] . ' -> ' . $m['setup_version']);
                            }
                            break;
                    endswitch;
                }
                if(!$enabledQty) {
                    $output->writeln('Nothing found');
                    $output->writeln('');
                } else {
                    $output->writeln('<title>Total: '.$enabledQty.'</title>');
                    $output->writeln('');
                }

                // Show list of disabled company modules
                $output->writeln(
                    '<info>Disabled modules:</info>');
                $enabledModules = $this->moduleList->getNames();
                $disabledModules = array_diff($this->fullModuleList->getNames(), $enabledModules);
                $disabledQty = 0;
                foreach ($disabledModules as $dm) {
                    switch($selectedType) :
                        case 0 :
                            $disabledQty++;
                            $output->writeln($dm);
                            break;
                        case 1 :
                            if (strpos($dm, 'Magento_') !== false) {
                                $disabledQty++;
                                $output->writeln($dm);
                            }
                            break;
                        case 2 :
                            if (strpos($dm, 'Magento_') === false) {
                                $disabledQty++;
                                $output->writeln($dm);
                            }
                            break;
                        case 3 :
                            if (strpos($dm, $this->storeInfo->getCompanyName()) !== false) {
                                $disabledQty++;
                                $output->writeln($dm);
                            }
                            break;
                    endswitch;
                }
                if(!$disabledQty) {
                    $output->writeln('Nothing found');
                } else {
                    $output->writeln('<title>Total: '.$disabledQty.'</title>');
                }
                $output->writeln('');
                break;



            /**
             * CACHE
             **********************************************************************************************************/

            /**
             * Clean cache for Templates
             */
            case 'clean:templates' :
            case 'c:templates' :
            case 'clean:t' :
            case 'c:t' :
                $this->shellM2Command('c:c block_html');
                $output->writeln('<info>Templates cache cleared.</info>');
                break;


            /**
             * Clean cache for Layouts
             */
            case 'clean:layouts' :
            case 'c:layouts' :
            case 'clean:l' :
            case 'c:l' :
            $this->shellM2Command('c:c layout');
            $output->writeln('<info>Layouts cache cleared.</info>');
            break;


            /**
             * Clean cache for Config
             */
            case 'clean:config' :
            case 'c:config' :
            case 'clean:c' :
            case 'c:c' :
                $this->shellM2Command('c:c config full_page');
                $output->writeln('<info>Config/Full Page cache cleared.</info>');
                break;


            /**
             * Clean cache for Var
             */
            case 'clean:var' :
            case 'c:var' :
            case 'clean:v' :
            case 'c:v' :
                $this->cache->removeBasicCache();
                $output->writeln('<info>Var/Cache & Var/Page_Cache cleared.</info>');
                break;


            /**
             * Clean cache for Dependency Injection
             */
            case 'clean:generated' :
            case 'c:generated' : case 'clean:g' :
            case 'c:g' :
                $this->cache->removeGeneratedCache();
                $output->writeln('<info>Generated cache cleared.</info>');
                break;


            /**
             * Clean cache for Styles
             */
            case 'clean:styles' :
            case 'c:styles' : case 'clean:s' :
            case 'c:s' :
                $dftTheme = $this->storeInfo->getDefaultTheme();

                // If multistore, ask for theme name
                if($this->storeInfo->isMultistore() && $this->storeInfo->getAskIfMultistore()) {
                    $theme = $this->askQuestion(
                        'Name of the theme (<comment>Enter</comment> to use <info>' . $dftTheme . '</info>):',
                        $dftTheme,
                        $input, $output
                    );
                } else {
                    $theme = $dftTheme;
                }

                // Clear the theme styles
                if($this->cache->removeStyleCache($theme));
                $output->writeln('Cache cleared for the theme <info>' . $theme . '.</info>');
                break;


            /**
             * Clean all cache
             */
            case 'clean:all' :
            case 'c:all' : case 'clean:a' :
            case 'c:a' :
                $this->cache->removeAllCache();
                $output->writeln('<info>All cache cleared.</info>');
                break;


            /**
             * Clean custom cache
             */
            case 'clean:custom' :
            case 'c:custom' : case 'clean:cu' :
            case 'c:cu' :
                $dialog = $this->getHelper('dialog');
                $cacheFolders = array(
                    'cache',
                    'composer_home',
                    'di',
                    'generation',
                    'log',
                    'page_cache',
                    'tmp',
                    'view_preprocessed'
                );

                $selected = $dialog->select(
                    $output,
                    'Select one (or more, separated by comma) cache folders to clear:',
                    $cacheFolders,
                    0,
                    false,
                    'Value "%s" is invalid',
                    true // enable multiselect
                );

                $selectedCache = array_map(function ($c) use ($cacheFolders) {
                    return $cacheFolders[$c];
                }, $selected);

                // Clear custom cache
                $this->cache->removeCustomCache($selectedCache);
                $output->writeln('<info>Selected cache cleared successfully.</info> ('.implode(', ', $selectedCache).')');
                break;


            /**
             * Clean cache for Admin
             */
            case 'clean:admin' :
            case 'c:admin' : case 'clean:ad' :
            case 'c:ad' :
                // Clear the admin styles
                if($this->cache->removeAdminCache());
                $output->writeln('Cache cleared for the <info>admin</info>');
                break;




            /**
             * CREATION
             **********************************************************************************************************/

            /**
             * Create module
             */
            case 'create:module' :
            case 'cr:module' : case 'create:m' :
            case 'cr:m' :
                // Name of Module
                $moduleName = $this->askQuestion(
                    'Name of the module:',
                    NULL,
                    $input, $output
                );
                if(!$moduleName) {
                    $output->writeln('<error>You must enter a name for the module</error>');
                    break;
                }

                // Ask for setup files
                $setupOptions = array(
                    'None', // 0
                    'InstallData', // 1
                    'UpgradeData', // 2
                    'InstallSchema', // 3
                    'UpgradeSchema', // 4
                    'Uninstall', // 5
                    'Recurring' // 6
                );
                $dialog = $this->getHelper('dialog');
                $selectedSetup = $dialog->select(
                    $output,
                    'Select one (or more, separated by comma) setup files to create (<comment>Enter</comment> to skip):',
                    $setupOptions,
                    0,
                    false,
                    'Value "%s" is invalid',
                    true // enable multiselect
                );
                $selectedSetupFiles = array_map(function($f) use ($setupOptions) {
                    return $setupOptions[$f];
                }, $selectedSetup);

                $selectedSetupFiles = count($selectedSetupFiles) == 1 && $selectedSetupFiles[0] == 'None' ?
                    null : $selectedSetupFiles;


                // Module feature
                $moduleFeature = array(
                    'None', // 0
                    'Extend Block/Model class', // 1
                    'Create Plugin for a method', // 2
                    'Create frontend page to display template', // 3
                    'Create frontend page to display template using view_model', // 4
                    'Create frontend page to return JSON', // 5
                    'Attach Observer to Event', // 6
                    'Replace constructor argument', // 7
                    'Create new Command line', // 8
                    'Create REST API with ACL', // 9
                    'Create UiComponent (to-do)', // 10
                    'Create a new Entity (to-do)' // 11
                );
                $selectedFeature = $dialog->select(
                    $output,
                    'Select a feature for your module (<comment>Enter</comment> to skip):',
                    $moduleFeature,
                    0,
                    false,
                    'Value "%s" is invalid',
                    false // enable multiselect
                );

                $feature = [];
                $feature['selected'] = $selectedFeature;

                switch ($selectedFeature) :
                    case '1' :
                        $output->writeln('
<title>Extend Block/Model class with di.xml</title>');
                        $diClassName = $this->askQuestion(
                            'Class to extend from (example: <comment>Magento\Wishlist\Block\Customer\Wishlist</comment>):',
                            NULL,
                            $input, $output
                        );
                        if(!$diClassName) {
                            $output->writeln('<error>You must enter a class to extend from</error>');
                            break;
                        }
                        $feature['class'] = $diClassName;
                        break;

                    case '2' :
                        $output->writeln('
<title>Create Plugin for a method with di.xml</title>');
                        $className = $this->askQuestion(
                            'Class to extend (example: <comment>Magento\Framework\Pricing\Render\Amount</comment>):',
                            NULL,
                            $input, $output
                        );
                        if(!$className) {
                            $output->writeln('<error>You must enter a class to extend</error>');
                            break;
                        }
                        $feature['class'] = $className;

                        $methodName = $this->askQuestion(
                            'Public method to extend (example: <comment>formatCurrency</comment>):',
                            NULL,
                            $input, $output
                        );
                        if(!$methodName) {
                            $output->writeln('<error>You must enter a method to extend</error>');
                            break;
                        }
                        $feature['method'] = $methodName;

                        // Plugin type
                        $dialog = $this->getHelper('dialog');
                        $pluginType = array(
                            'Before',
                            'After',
                            'Around'
                        );
                        $selectedPluginType = $dialog->select(
                            $output,
                            'Select when to execute your plugin:',
                            $pluginType,
                            null,
                            false,
                            'Value "%s" is invalid',
                            false // enable multiselect
                        );
                        if($methodName === null) {
                            $output->writeln('<error>You must enter a method to extend</error>');
                            break;
                        }
                        $feature['when'] = $selectedPluginType;
                        break;

                    case '3' :
                        $output->writeln('
<title>Create frontend page with Controller to display template</title>');
                        $newPageName = $this->askQuestion(
                            'URL for the new page (example: <comment>helloworld</comment>):',
                            NULL,
                            $input, $output
                        );
                        if(!$newPageName) {
                            $output->writeln('<error>You must enter a URL for the new page</error>');
                            break;
                        }
                        $feature['newpage'] = $newPageName;
                        break;

                    case '4' :
                        $output->writeln('
<title>Create frontend page with Controller to display template using view-model</title>');
                        $newPageName = $this->askQuestion(
                            'URL for the new page (example: <comment>helloworld</comment>):',
                            NULL,
                            $input, $output
                        );
                        if(!$newPageName) {
                            $output->writeln('<error>You must enter a URL for the new page</error>');
                            break;
                        }
                        $feature['newpage'] = $newPageName;
                        break;

                    case '5' :
                        $output->writeln('
<title>Create frontend page with Controller to return JSON</title>');
                        $newPageName = $this->askQuestion(
                            'URL for the new page (example: <comment>helloworld</comment>):',
                            NULL,
                            $input, $output
                        );
                        if(!$newPageName) {
                            $output->writeln('<error>You must enter a URL for the new page</error>');
                            break;
                        }
                        $feature['newpage'] = $newPageName;
                        break;

                    case '6' :
                        $output->writeln('
<title>Attach Observer to Event</title>');
                        $eventName = $this->askQuestion(
                            'Event (example: <comment>catalog_controller_product_view</comment>):',
                            NULL,
                            $input, $output
                        );
                        if(!$eventName) {
                            $output->writeln('<error>You must enter an Event for the Observer</error>');
                            break;
                        }
                        $feature['event'] = $eventName;

                        $observerName = $this->askQuestion(
                            'Observer name (example: <comment>AddSomeFunctionality</comment>):',
                            NULL,
                            $input, $output
                        );
                        if(!$observerName) {
                            $output->writeln('<error>You must enter a name for the Observer</error>');
                            break;
                        }
                        $feature['observer'] = $observerName;
                        break;

                    case '7' :
                        $output->writeln('
<title>Replace constructor argument</title>');
                        $diClassName = $this->askQuestion(
                            'Block/Model class to extend (example: <comment>Magento\Braintree\Block\Form</comment>):',
                            NULL,
                            $input, $output
                        );
                        if(!$diClassName) {
                            $output->writeln('<error>You must enter a class to extend</error>');
                            break;
                        }
                        $feature['class'] = $diClassName;

                        $variableName = $this->askQuestion(
                            'Name of constructor variable to be replaced (example: <comment>$paymentConfig</comment>):',
                            NULL,
                            $input, $output
                        );
                        if(!$variableName) {
                            $output->writeln('<error>You must enter the variable to be replaced</error>');
                            break;
                        }
                        $feature['variable'] = $variableName;
                        break;

                    case '8' :
                        $output->writeln('
<title>Create new Command line</title>');
                        $commandName = $this->askQuestion(
                            'Name for the new command (example: <comment>helloworld</comment>):',
                            NULL,
                            $input, $output
                        );
                        if(!$commandName) {
                            $output->writeln('<error>You must enter a name for the command</error>');
                            break;
                        }
                        $feature['command'] = $commandName;
                        break;

                    case '9' :
                        $output->writeln('
<title>Create REST API with ACL</title>');
                        $keyName = $this->askQuestion(
                            'Name of the key (example: <comment>hello</comment>):',
                            NULL,
                            $input, $output
                        );
                        if(!$keyName) {
                            $output->writeln('<error>You must enter a name for the key</error>');
                            break;
                        }
                        $feature['key'] = $keyName;
                        break;
                endswitch;

                // Create module
                if($this->create->createModule($moduleName, $selectedSetupFiles, $feature)) {

                    // Enable it?
                    $enableOption = $this->askQuestion(
                        'Do you want to enable the module? (<comment>y/n</comment>; <comment>Enter</comment> to skip):',
                        'n',
                        $input, $output
                    );
                    if(strtolower($enableOption) == 'y') {
                        if($this->storeInfo->getMagentoCloud()) {
                            // If Cloud, use composer
                            $package = strtolower($this->storeInfo->getCompanyName()).'/'.strtolower($moduleName);
                            $this->shellCommand('composer require '.$package.' --no-update');
                            $this->shellCommand('composer update '.$package);
                        }
                        $this->shellM2Command('module:enable '.$this->storeInfo->getCompanyName().'_'.$moduleName);
                        $this->shellM2Command('s:up');
                    }

                    $output->writeln('');
                    $output->writeln('The module <info>' . $moduleName . '</info> was created successfully.');
                    if(strtolower($enableOption) !== 'y') {
                        $output->writeln('Remember to run <info>'.$this->storeInfo->getBinMagento().' module:enable ' . $this->storeInfo->getCompanyName() . '_' . $moduleName . '</info> to enable it');
                    }
                    $output->writeln('');
                } else {
                    $output->writeln('<error>There was an error creating the new module</error>');
                }
                break;


            /**
             * Create theme
             */
            case 'create:theme' :
            case 'cr:theme' : case 'create:t' :
            case 'cr:t' :
                // Ask for a theme name
                $themeName = $this->askQuestion(
                    'Name of the theme:',
                    NULL,
                    $input, $output
                );
                if(!$themeName) {
                    $output->writeln('<error>You must enter a name for the theme</error>');
                    break;
                }

                // Ask for a theme to extend from
                $dialog = $this->getHelper('dialog');
                $extendOptions = array(
                    'Magento/blank',
                    'Magento/Luma (beta)',
                    'Other'
                );
                $selected = $dialog->select(
                    $output,
                    'Extend from (default to '.$extendOptions[0].')',
                    $extendOptions,
                    0
                );
                $extendOption = $extendOptions[$selected];

                if($extendOption == 'Other') {
                    $extendOption = $this->askQuestion(
                        'Theme to extend from [format <info>Company/name</info>]:',
                        NULL,
                        $input, $output
                    );
                    if(!$extendOption) {
                        $output->writeln('<error>You must enter a name of the theme to extend from</error>');
                        break;
                    }
                }

                // Create the theme
                $this->create->createTheme($themeName, $extendOption);
                $output->writeln('The theme <info>'.$themeName.'</info> was created successfully.');
                break;


            /**
             * Create dummy content
             */
            case 'create:dummy' :
            case 'cr:dummy' : case 'create:d' :
            case 'cr:d' :
                // Ask for a categories qty
                $dftCatQty = $this->storeInfo->getDefaultDummyCategoriesQty();
                $categoriesQty = $this->askQuestion(
                    'Quantity of categories (<comment>Enter</comment> to create <info>'.$dftCatQty.'</info>):',
                    $dftCatQty,
                    $input, $output
                );
                $categoriesQty = (int)$categoriesQty;
                if(!($categoriesQty)) {
                    $output->writeln('<error>Please enter a valid number of categories</error>');
                    break;
                }

                // Ask for a products qty
                $dftProdQty = $this->storeInfo->getDefaultDummyProductsQty();
                $productsQty = $this->askQuestion(
                    'Quantity of products (<comment>Enter</comment> to create <info>'.$dftProdQty.'</info>):',
                    $dftCatQty,
                    $input, $output
                );
                $productsQty = (int)$productsQty;
                if(!($productsQty)) {
                    $output->writeln('<error>Please enter a valid number of products</error>');
                    break;
                }

                // Create the dummy categories & products
                $this->dummy->createDummyContent($categoriesQty, $productsQty);
                $output->writeln('The <info>dummy content</info> was created successfully.
<info>'.$categoriesQty.'</info> categories and <info>'.$productsQty.'</info> products on every category.
Don\'t forget to reindex (<info>'.$this->storeInfo->getBinMagento().' indexer:reindex</info>).');
                break;



            /**
             * CUSTOMER
             **********************************************************************************************************/

            /**
             * Create customer
             */
            case 'customer:create' :
            case 'c:cr' : case 'c:cr' :
            case 'c:cr' :
                // Ask for customer data
                $firstName = $this->askQuestion(
                    'First Name:',
                    NULL,
                    $input, $output
                );
                if(!$firstName) {
                    $output->writeln('<error>You must enter a first name for the customer</error>');
                    break;
                }

                $lastName = $this->askQuestion(
                    'Last Name:',
                    NULL,
                    $input, $output
                );
                if(!$lastName) {
                    $output->writeln('<error>You must enter a last name for the customer</error>');
                    break;
                }

                $email = $this->askQuestion(
                    'Email:',
                    NULL,
                    $input, $output
                );
                if(!$email) {
                    $output->writeln('<error>You must enter a email for the customer</error>');
                    break;
                }

                $password = $this->askQuestion(
                    'Password:',
                    NULL,
                    $input, $output
                );
                if(!$password) {
                    $output->writeln('<error>You must enter a password for the customer</error>');
                    break;
                }

                // Create the customer
                $this->dummy->createDummyCustomers($firstName, $lastName, $email, $password);
                $output->writeln('The customer <info>'.$firstName.' '.$lastName.'</info> was created successfully with the <info>'.$email.'</info> email.');
                break;


            /**
             * Update a customer password
             */
            case 'customer:password' :
            case 'c:password' : case 'customer:p' :
            case 'c:p' :
                // Ask for an email
                $dialog = $this->getHelper('dialog');
                $emails = array();
                //$customersCollection = $this->customer->getCollection();
                $connection = $this->resource->getConnection('default');
                $customers = $connection->fetchAll('SELECT `email` FROM `customer_entity`');
                foreach($customers as $c) {
                    $emails[] = $c['email'];
                }
                $email = $dialog->ask(
                    $output,
                    'Enter email of existing customer (<info>'.count($emails).' found</info>, use autocomplete): ',
                    NULL,
                    $emails
                );
                if(!$email) {
                    $output->writeln('<error>Please enter the email of an existing customer</error>');
                    break;
                }

                // Ask for a password
                $password = $this->askQuestion(
                    'New password:',
                    NULL,
                    $input, $output
                );
                if(!$password) {
                    $output->writeln('<error>You must enter a new password for the customer</error>');
                    break;
                }

                // Update the password
                $connection = $this->resource->getConnection('default');
                $newPassword = 'xxxxxxxx'.$password;
                $connection->query("
                    UPDATE `customer_entity` SET `password_hash` = CONCAT(SHA2('$newPassword', 256), ':xxxxxxxx:1')
                    WHERE `email` = '$email'
                ");

                $output->writeln('The password for the customer <info>'.$email.'</info> was changed successfully.');
                break;



            /**
             * ADMIN
             **********************************************************************************************************/

            /**
             * Create admin user
             */
            case 'admin:create' :
            case 'a:create' : case 'admin:cr' :
            case 'a:cr' :
                // Ask for admin user data
                $firstname = $this->askQuestion(
                    'First Name:',
                    NULL,
                    $input, $output
                );
                if(!$firstname) {
                    $output->writeln('<error>You must enter a First Name for the new admin user</error>');
                    break;
                }

                $lastname = $this->askQuestion(
                    'Last Name:',
                    NULL,
                    $input, $output
                );
                if(!$lastname) {
                    $output->writeln('<error>You must enter a Last Name for the new admin user</error>');
                    break;
                }

                $email = $this->askQuestion(
                    'Email:',
                    NULL,
                    $input, $output
                );
                if(!$email) {
                    $output->writeln('<error>You must enter an Email for the new admin user</error>');
                    break;
                }

                $username = $this->askQuestion(
                    'Username:',
                    NULL,
                    $input, $output
                );
                if(!$username) {
                    $output->writeln('<error>You must enter a Username for the new admin user</error>');
                    break;
                }

                $password = $this->askQuestion(
                    'Password:',
                    NULL,
                    $input, $output
                );
                if(!$password) {
                    $output->writeln('<error>You must enter a Password for the new admin user</error>');
                    break;
                }

                // Create command
                $command  = ' admin:user:create';
                $command .= ' --admin-user="'.$username.'"';
                $command .= ' --admin-password="'.$password.'"';
                $command .= ' --admin-email="'.$email.'"';
                $command .= ' --admin-firstname="'.$firstname.'"';
                $command .= ' --admin-lastname="'.$lastname.'"';

                $this->shellM2Command($command);
                break;


            /**
             * Update admin password
             */
            case 'admin:password' :
            case 'a:password' : case 'admin:p' :
            case 'a:p' :
                // Ask for an email
                $dialog = $this->getHelper('dialog');
                $emails = array();
                $connection = $this->resource->getConnection('default');
                $admins = $connection->fetchAll('SELECT `email` FROM `admin_user`');
                foreach($admins as $a) {
                    $emails[] = $a['email'];
                }
                $email = $dialog->ask(
                    $output,
                    'Enter email of existing admin (<info>'.count($emails).' found</info>, use autocomplete): ',
                    NULL,
                    $emails
                );
                if(!$email) {
                    $output->writeln('<error>Please enter the email of an existing admin</error>');
                    break;
                }

                // Ask for a password
                $password = $this->askQuestion(
                    'New password:',
                    NULL,
                    $input, $output
                );
                if(!$password) {
                    $output->writeln('<error>You must enter a new password for the admin</error>');
                    break;
                }

                // Update the password
                $newPassword = 'cc'.$password;
                $connection->query("
                    UPDATE `admin_user` SET `password` = CONCAT(MD5('$newPassword'),':cc') 
                    WHERE `email` = '$email' LIMIT 1
                ");

                $output->writeln('The password for the admin <info>'.$email.'</info> was changed successfully.');
                break;



            /**
             * FRONTEND TOOLS
             **********************************************************************************************************/

            /**
             * Deploy static content
             */
            case 'tools:static' :
            case 'tools:s' : case 't:static' :
            case 't:s' :
                $dftTheme = $this->storeInfo->getDefaultTheme();

                // If multistore, ask for theme name
                if($this->storeInfo->isMultistore() && $this->storeInfo->getAskIfMultistore()) {
                    $theme = $this->askQuestion(
                        'Name of the theme (<comment>Enter</comment> to use <info>'.$dftTheme.'</info>):',
                        $dftTheme,
                        $input, $output
                    );
                } else {
                    $theme = $dftTheme;
                }

                // Force it? (required in Magento 2.2.2)
                $forceOption = $this->askQuestion(
                    'Force it? (<comment>y/n</comment>; <comment>Enter</comment> to skip):',
                    'n',
                    $input, $output
                );

                $forceOption = strtolower($forceOption) == 'y' ? ' -f' : '';

                $output->writeln('Deploying static content for the theme <info>'.$dftTheme.'</info>, please wait');
                $this->shellM2Command('setup:static-content:deploy --area frontend --theme '.$theme . $forceOption);
                break;

            /**
             * Override template
             * Examples:
             * - vendor/magento/module-checkout/view/frontend/templates/cart.phtml
             */
            case 'override:template' :
            case 'o:template' : case 'override:t' :
            case 'o:t' :
                $dftTheme = $this->storeInfo->getDefaultTheme();

                // If multistore, ask for theme name
                if($this->storeInfo->isMultistore() && $this->storeInfo->getAskIfMultistore()) {
                    $theme = $this->askQuestion(
                        'Name of the theme (<comment>Enter</comment> to use <info>' . $dftTheme . '</info>):',
                        $dftTheme,
                        $input, $output
                    );
                } else {
                    $theme = $dftTheme;
                }

                // Ask for file to be overridden
                $file = $this->askQuestion(
                    'Path of the template to be overridden (example: <info>vendor/magento/module-checkout/view/frontend/templates/cart.phtml</info>):',
                    NULL,
                    $input, $output
                );
                if(!$file) {
                    $output->writeln('<error>Please enter a path of the file to override</error>');
                    break;
                }

                // Get path to override template
                $fullPath = $this->create->overrideTemplate($file, $theme);

                if(!$fullPath) {
                    $output->writeln('<error>The template already exists within the '.$theme.' theme</error>');
                    break;
                }

                $output->writeln('
Template copied to <info>'.$fullPath.'</info>
Remember to remove the Magento copyright from the top of the file.
');
                break;


            /**
             * Enable the template hints
             */
            case 'hints:on' :
            case 'h:on' :
                $this->shellM2Command('dev:template-hints:enable');
                $this->cache->removeBasicCache();
                break;


            /**
             * Disable the template hints
             */
            case 'hints:off' :
            case 'h:off' :
                $this->shellM2Command('dev:template-hints:disable');
                $this->cache->removeBasicCache();
                break;



            /**
             * OTHER TOOLS
             **********************************************************************************************************/

            /**
             * List of Magento Cloud commands
             */
            case 'cloud' : case 'mc' :
                $projectId = $this->storeInfo->getMagentoCloudProjectId();
                if(empty($projectId)) {
                    $output->writeln('');
                    $output->writeln('<error>Your project is not set as a Magento Cloud project.
    Please check the WH documentation: https://github.com/WeidenhammerCommerce/wh/blob/master/README.md</error>
    ');
                    break;
                }

                $this->shellCommand('magento-cloud');

                $output->writeln('');
                $dialog = $this->getHelper('dialog');
                $mcOptions = array(
                    '<info>[General Info]</info> Project', // 0
                    '<info>[General Info]</info> My Account', // 1
                    '<info>[General Info]</info> All users', // 2
                    '<info>[General Info]</info> All envs', // 3
                    '<info>[Environment Info]</info> <question>[env name]</question> Env data', // 4
                    '<info>[Environment Info]</info> <question>[env name]</question> Env URLs', // 5
                    '<info>[Environment Info]</info> <question>[env name]</question> Env logs', // 6
                    '<info>[Environment Info]</info> <question>[env name]</question> Env activity (last 10)', // 7
                    '<info>[Branch Action]</info> <question>[branch name, parent branch]</question> Create', // 8
                    '<info>[Branch Action]</info> <question>[branch name]</question> Push current (to server branch with the same name)', // 9
                    '<info>[Branch Action]</info> <question>[branch name]</question> Activate remote branch/env', // 10
                    '<info>[Other]</info> <question>[env name]</question> Download dump of env database', // 11
                    '<info>[Other]</info> <question>[env name]</question> Get command to connect to env through SSH' // 12
                );
                $selected = $dialog->select(
                    $output,
                    '<title>Select an option for the current project (ID: '.$projectId.'):</title>',
                    $mcOptions,
                    0,
                    false,
                    'Value "%s" is invalid',
                    false // enable multiselect
                );

                $requiredEnv = array(4,5,6,7,10,11,12);
                if(in_array($selected, $requiredEnv)) {
                    // Ask environment name
                    $envName = $this->askQuestion(
                        'Name of the env:',
                        NULL,
                        $input, $output
                    );
                    if(!$envName) {
                        $output->writeln('<error>You must enter a name for the env</error>');
                        break;
                    }
                }

                $requireBranch = array(8);
                if(in_array($selected, $requireBranch)) {
                    // Ask name of new branch
                    $branchName = $this->askQuestion(
                        'Name of new branch:',
                        NULL,
                        $input, $output
                    );
                    if(!$branchName) {
                        $output->writeln('<error>You must enter a name for the new branch</error>');
                        break;
                    }
                }
                if(in_array($selected, $requireBranch)) {
                    // Ask name of parent branch
                    $masterBranch = $this->askQuestion(
                        'Name of the parent branch:',
                        NULL,
                        $input, $output
                    );
                    if(!$masterBranch) {
                        $output->writeln('<error>You must enter a name for the parent branch</error>');
                        break;
                    }
                }

                switch($selected) :
                    case 0 :
                        // See project info
                        $this->shellCommand('magento-cloud project:info -p '.$projectId);
                        break;
                    case 1 :
                        // See your account info
                        $this->shellCommand('magento-cloud auth:info');
                        break;
                    case 2 :
                        // See all users
                        $this->shellCommand('magento-cloud user:list -p '.$projectId);
                        break;
                    case 3 :
                        // See all envs
                        $this->shellCommand('magento-cloud environments -p '.$projectId);
                        break;
                    case 4 :
                        // See env info
                        $this->shellCommand('magento-cloud environment:info -p '.$projectId.' -e '.$envName);
                        break;
                    case 5 :
                        // See envs URLs
                        $this->shellCommand('magento-cloud environment:url -p '.$projectId.' -e '.$envName);
                        break;
                    case 6 :
                        // See envs logs
                        $this->shellCommand('magento-cloud environment:logs -p '.$projectId.' -e '.$envName);
                        break;
                    case 7 :
                        // See envs activity
                        $this->shellCommand('magento-cloud activity:list -p '.$projectId.' -e '.$envName);
                        break;
                    case 8 :
                        // Create branch
                        $this->shellCommand('magento-cloud environment:branch -p '.$projectId.' '.$branchName.' '.$masterBranch);
                        break;
                    case 9 :
                        // Push current branch
                        $this->shellCommand('magento-cloud environment:push');
                        break;
                    case 10 :
                        // Activate env
                        $this->shellCommand('magento-cloud activate:environment -p '.$projectId.' -e '.$envName);
                        break;
                    case 11 :
                        // Download env dump
                        $this->shellCommand('magento-cloud db:dump -p '.$projectId.' -e '.$envName);
                        break;
                    case 12 :
                        // Connect through SSH
                        $command = 'ssh -p '.$projectId.' -e '.$envName;
                        $output->writeln('');
                        $output->writeln('Run: <info>magento-cloud '.$command.'</info>');
                        $output->writeln('');
                        break;
                endswitch;
                break;


            /**
             * List of Magento Cloud commands
             */
            case 'cloud-db' :
                $output->writeln('Connecting to Cloud database...');

                $dbInfo = $this->deploymentConfig->get('db')['connection']['default'];
                $user = $dbInfo['username'];
                $host = $dbInfo['host'];
                $pass = $dbInfo['password'];
                $dbname = $dbInfo['dbname'];

                shell_exec('mysql -u' . $user . ' -h' . $host . ' -p' . $pass . ' ' . $dbname);
                break;


            /**
             * Create dump of the database
             */
            case 'create:dump' :
            case 'cr:dump' :
            case 'dump' :
                $output->writeln('Starting the DB backup into the <info>'.$this->storeInfo->getSaveDatabaseFolder().'</info> folder, please wait...');

                $dbInfo = $this->deploymentConfig->get('db')['connection']['default'];

                $user = $dbInfo['username'];
                $host = $dbInfo['host'];
                $pass = $dbInfo['password'];
                $dbname = $dbInfo['dbname'];

                $filename = $dbname.'_'.date('Y-m-d_H-i-s').'.sql';
                $backupsDir = $this->storeInfo->getSaveDatabaseFolder();

                if (!$this->file->isExists($backupsDir)) {
                    $this->file->createDirectory($backupsDir);
                }

                $destination = $backupsDir . '/' . $filename;
                $command = 'mysqldump -u' . $user . ' -h' . $host . ' -p' . $pass . ' ' . $dbname . ' >>' . $destination;

                shell_exec($command);

                $output->writeln('Dump saved in <info>'.$destination.'</info>');
                break;


            /**
             * Downgrade database module to its code version
             */
            case 'module:downgrade' :
            case 'm:downgrade' : case 'module:d' :
            case 'm:d' :
                // Ask for file to override
                $module = $this->askQuestion(
                    'Name of the module:',
                    NULL,
                    $input, $output
                );
                if(!$module) {
                    $output->writeln('<error>Please enter a module to downgrade</error>');
                    break;
                }

                // Get current db version
                $v = null;
                foreach($this->moduleList->getAll() as $m) {
                    if($m['name'] == $module) {
                        $v = $m['setup_version'];
                    }
                }

                // Validate module exists
                if(null == $v) {
                    $output->writeln("We couldn't find any module with the name <info>$module</info>");
                    break;
                }

                // Get module version from db
                $connection = $this->resource->getConnection('default');
                $result = $connection->fetchRow(
                    "SELECT schema_version FROM setup_module WHERE module = '$module'
                ");
                $currentVersion = $result['schema_version'];

                // Downgrade the module version on the db
                if($v !== $currentVersion) {
                    $connection->query("
                      UPDATE `setup_module` SET `schema_version` = '$v', `data_version` = '$v' WHERE module = '$module' LIMIT 1
                    ");
                    $output->writeln("<info>$module</info>: database version downgraded to <info>$v</info>");
                } else {
                    $output->writeln("<info>$module</info>: the database version was the same as the code version, nothing to do here");
                }
                break;


            /**
             * Regenerate URL rewrites
             */
            case 'tools:regenerate' :
            case 'tools:r' : case 't:regenerate' :
            case 't:r' :
                set_time_limit(0);
                $allStores = $this->storeInfo->getAllStoresToRegenerate();
                $storesList = [];
                $output->writeln('Regenerating of Url rewrites:');

                $storesNames = $this->storeInfo->getAllStoresNames();
                array_unshift($storesNames, 'All');

                $dialog = $this->getHelper('dialog');
                $storeSelected = $dialog->select(
                    $output,
                    'Select a store (<comment>Enter</comment> to select <info>All</info>):',
                    $storesNames,
                    0,
                    false,
                    'Value "%s" is invalid',
                    false // multiselect
                );
                
                if($storeSelected == '0') {
                    $storesList = $allStores;
                } elseif (strlen($storeSelected) && ctype_digit($storeSelected)) {
                    if (isset($allStores[$storeSelected])) {
                        $storesList = array(
                            $storeSelected => $allStores[$storeSelected]
                        );
                    } else {
                        $output->writeln('<error>[ERROR] Store with this ID not exists.</error>');
                        return;
                    }
                } else {
                    $output->writeln('<error>[ERROR] Store ID should have a integer value.</error>');
                    return;
                }

                // Remove all current URL rewrites, from url_rewrite and catalog_url_rewrite_product_category
                if(count($storesList) > 0) {
                    $this->regenerate->removeAllUrlRewrites($storesList);
                }

                // Regenerate the URLs
                foreach ($storesList as $storeId => $storeCode) {
                    $output->writeln('');
                    $output->write("[Store ID: {$storeId}, Store View code: {$storeCode}]:");

                    // Get categories collection
                    $this->regenerate->createCategory($storeId, $output);
                }

                $output->writeln('');
                $output->writeln('Reindexation...');
                shell_exec($this->storeInfo->getBinMagento().' indexer:reindex');

                $output->writeln('Cache refreshing...');
                shell_exec($this->storeInfo->getBinMagento().' cache:flush');
                $output->writeln('The reindexation finished successfully.');
                break;


            /**
             * Deploy to given mode
             */
            case 'deploy:mode' :
            case 'd:mode' : case 'deploy:m' :
            case 'd:m' :
                $dialog = $this->getHelper('dialog');
                $dMode = array(
                    'Show current mode',
                    'Set to Developer',
                    'Set to Production'
                );
                $selected = $dialog->select(
                    $output,
                    'Select an option:',
                    $dMode,
                    0,
                    false,
                    'Value "%s" is invalid',
                    false // multiselect
                );

                switch($selected) :
                    case 0 :
                        // Show current mode
                        $this->shellM2Command('deploy:mode:show');
                        break;
                    case 1 :
                        // Set to Developer
                        $this->shellM2Command('deploy:mode:set developer');
                        break;
                    case 2 :
                        // Set to Production
                        $this->shellM2Command('deploy:mode:set production');
                        break;
                endswitch;
                break;


            /**
             * Show M2 snippets
             */
            case 'snippets' : case 'sn' :
                $dialog = $this->getHelper('dialog');
                $dMode = array(
                    '<info>[Layout]</info> Call <comment>template</comment> without custom <comment>Block</comment>',
                    '<info>[Layout]</info> Call <comment>template</comment> with custom <comment>Block</comment>',
                    '<info>[Layout]</info> Call <comment>template</comment> with custom <comment>view_model</comment>',
                    '<info>[Layout]</info> Change <comment>template</comment> of <comment>Block</comment>',
                    '<info>[Layout]</info> Call <comment>CMS Block</comment>',
                    '<info>[Layout]</info> Move <comment>Block</comment>',
                    '<info>[Layout]</info> Remove <comment>Block</comment>',

                    '<info>[Template]</info> Show <comment>theme\'s image</comment>',
                    '<info>[Template]</info> Show <comment>wysiwyg\'s image</comment>',
                    '<info>[Template]</info> Show <comment>store\'s link</comment>',
                    '<info>[Template]</info> Show <comment>CMS Block</comment>',

                    '<info>[CMS Page/Block]</info> Show <comment>Template</comment>',
                    '<info>[CMS Page/Block]</info> Show <comment>CMS Block</comment>',
                    '<info>[CMS Page/Block]</info> Show <comment>theme\'s image</comment>',
                    '<info>[CMS Page/Block]</info> Show <comment>store\'s link</comment>',
                    '<info>[CMS Page/Block]</info> Show <comment>store\'s information</comment>'
                );
                $selected = $dialog->select(
                    $output,
                    'Select a snippet:',
                    $dMode,
                    0,
                    false,
                    'Value "%s" is invalid',
                    false // multiselect
                );

                $companyName = $this->storeInfo->getCompanyName();

                $output->writeln('');
                switch($selected) :
                    case 0 :
                        $output->writeln('<title>[Layout] Call template without custom Block</title>
<referenceContainer name="some.container"> 
    <block name="my.block.name"
           before="-"
           template="Magento_Theme::new-template.phtml"/> 
</referenceContainer>');
                        break;

                    case 1 :
                        $output->writeln('<title>[Layout] Call template with custom Block</title>
<referenceContainer name="some.container"> 
    <block class="'.$companyName.'\MyModule\Block\MyBlock"
           name="my.block.name"
           template="'.$companyName.'_MyModule::new-template.phtml"/> 
</referenceContainer>');
                        break;

                    case 2 :
                        $output->writeln('<title>[Layout] Call template with custom view_model</title>
<referenceContainer name="content">
    <block name="'.$companyName.'.mymodule.myfrontname"
           template="'.$companyName.'_MyModule::mytemplate.phtml">
        <arguments>
            <argument name="view_model" xsi:type="object">
                '.$companyName.'\MyModule\Block\Index
            </argument>
        </arguments>
    </block>
</referenceContainer>');
                        break;

                    case 3 :
                        $output->writeln('<title>[Layout] Change template</title>
<referenceBlock name="some.block">
    <arguments>
        <argument name="template" xsi:type="string">Magento_Theme::new-template.phtml</argument>
    </arguments>
</referenceBlock>');
                        break;

                    case 4 :
                        $output->writeln('<title>[Layout] Call CMS Block</title>
<referenceContainer name="some.container"> 
    <block class="Magento\Cms\Block\Block" name="my.block.name" before="-">
        <arguments>
            <argument name="block_id" xsi:type="string">block_identifier</argument>
        </arguments>
    </block>
</referenceContainer>');
                        break;

                    case 5 :
                        $output->writeln('<title>[Layout] Move Block</title>
<move element="some.block" destination="some.other.block" before="-"/>');
                        break;

                    case 6 :
                        $output->writeln('<title>[Layout] Remove Block</title>
<referenceBlock name="some.block" remove="true"/>');
                        break;

                    case 7 :
                        $output->writeln('<title>[Template] Show theme\'s image</title>
<?php echo $block->getViewFileUrl(\'images/some_image.jpg\'); ?>');
                        break;

                    case 8 :
                        $output->writeln('<title>[Template] Show wysiwyg\'s image</title>
public function getWysiwygUrl($image)
{
    // \Magento\Store\Model\StoreManagerInterface $storeManager
    $currentStore = $this->storeManager->getStore();
    $media = $currentStore->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);

    return $media.\'wysiwyg/\'.$image;
}');
                        break;

                    case 9 :
                        $output->writeln('<title>[Template] Show link</title>
<?php echo $block->getUrl(\'checkout/cart\', [\'_secure\' => true]); ?>');
                        break;

                    case 10 :
                        $output->writeln('<title>[Template] Show CMS Block</title>
<?php echo $block->getLayout()->createBlock(\'Magento\Cms\Block\Block\')->setBlockId(\'block_identifier\')->toHtml(); ?>');
                        break;

                    case 11 :
                        $output->writeln('<title>[CMS Page/Block] Show Template</title>
{{block class="'.$companyName.'\MyModule\Block\MyBlock" template="'.$companyName.'_MyModule::template.phtml"}}');
                        break;

                    case 12 :
                        $output->writeln('<title>[CMS Page/Block] Show CMS Block</title>
{{block id="block_identifier"}} or {{block class="Magento\\Cms\\Block\\Block" block_id="block_identifier"}}');
                        break;

                    case 13 :
                        $output->writeln('<title>[CMS Page/Block] Show theme\'s image</title>
{{view url=\'images/some_image.jpg\'}}');
                        break;

                    case 14 :
                        $output->writeln('<title>[CMS Page/Block] Show store\'s link</title>
{{store url="checkout/cart"}}');
                        break;

                    case 15 :
                        $output->writeln('<title>[CMS Page/Block] Show store\'s information</title>
{{config path=\'general/store_information/phone\'}}');
                        break;
                endswitch;
                $output->writeln('');
                break;


            /**
             * Dialog box with all of the WH options
             */
            case 'options' : case 'op' :
                $output->writeln('');
                $dialog = $this->getHelper('dialog');
                $whOptions = array(
                    '<info>[Info]</info> About the M2 instance <comment>[i:m2]</comment>', // 0
                    '<info>[Info]</info> About of all of the stores <comment>[i:s]</comment>', // 1
                    '<info>[Info]</info> List modules (with its code version) <comment>[i:m]</comment>', // 2

                    '<info>[Cache]</info> Remove to regenerate the templates <comment>[c:t]</comment>', // 3
                    '<info>[Cache]</info> Remove to regenerate the layouts <comment>[c:l]</comment>', // 4
                    '<info>[Cache]</info> Remove cache after changing admin configuration <comment>[c:c]</comment>', // 5
                    '<info>[Cache]</info> Remove var/cache & var/page_cache <comment>[c:v]</comment>', // 6
                    '<info>[Cache]</info> Remove regenerate the CSS styles <comment>[c:s]</comment>', // 7
                    '<info>[Cache]</info> Remove all (everything within /var and /pub/static) <comment>[c:a]</comment>', // 8
                    '<info>[Cache]</info> Remove selected (one or more, separated by comma) <comment>[c:cu]</comment>', // 9
                    '<info>[Cache]</info> Remove regenerate the admin <comment>[c:ad]</comment>', // 10

                    '<info>[Creation]</info> New module <comment>[cr:m]</comment>', // 11
                    '<info>[Creation]</info> New theme <comment>[cr:t]</comment>', // 12
                    '<info>[Creation]</info> Dummy categories/products <comment>[cr:d]</comment>', // 13

                    '<info>[Customer]</info> Create <comment>[c:cr]</comment>', // 14
                    '<info>[Customer]</info> Update password <comment>[c:p]</comment>', // 15

                    '<info>[Admin]</info> Create user <comment>[a:cr]</comment>', // 16
                    '<info>[Admin]</info> Update password <comment>[a:p]</comment>', // 17

                    '<info>[Frontend]</info> Deploy static content <comment>[t:s]</comment>', // 18
                    '<info>[Frontend]</info> Copy core template to theme to override it <comment>[o:t]</comment>', // 19
                    '<info>[Frontend]</info> Enable the Template Hints <comment>[h:on]</comment>', // 20
                    '<info>[Frontend]</info> Disable the Template Hints <comment>[h:off]</comment>', // 21

                    '<info>[Tools]</info> List of Magento Cloud commands <comment>[cloud]</comment>', // 22
                    '<info>[Tools]</info> Dump of database in '.$this->storeInfo->getSaveDatabaseFolder().' folder <comment>[dump]</comment>', // 23
                    '<info>[Tools]</info> Downgrades version of the database module to the one on the code <comment>[m:d]</comment>', // 24
                    '<info>[Tools]</info> Regenerate URL rewrites of products/categories in all/specific store/s <comment>[t:r]</comment>', // 25
                    '<info>[Tools]</info> Set proper permissions to all files and folders <comment>[t:p]</comment>', // 26
                    '<info>[Tools]</info> Deploy to given mode (show, developer or production) <comment>[d:m]</comment>', // 27
                    '<info>[Tools]</info> Show snippets <comment>[sn]</comment>' // 28
                );
                $selected = $dialog->select(
                    $output,
                    '<title>Select an option:</title>',
                    $whOptions,
                    0,
                    false,
                    'Value "%s" is invalid',
                    false // enable multiselect
                );
                $output->writeln('');

                switch ($selected) :
                    case 0 :
                        $this->shellM2Command('wh i:m2');
                        break;
                    case 1 :
                        $this->shellM2Command('wh i:s');
                        break;
                    case 2 :
                        $this->shellM2Command('wh i:m');
                        break;

                    case 3 :
                        $this->shellM2Command('wh c:t');
                        break;
                    case 4 :
                        $this->shellM2Command('wh c:l');
                        break;
                    case 5 :
                        $this->shellM2Command('wh c:c');
                        break;
                    case 6 :
                        $this->shellM2Command('wh c:v');
                        break;
                    case 7 :
                        $this->shellM2Command('wh c:s');
                        break;
                    case 8 :
                        $this->shellM2Command('wh c:a');
                        break;
                    case 9 :
                        $this->shellM2Command('wh c:cu');
                        break;
                    case 10 :
                        $this->shellM2Command('wh c:ad');
                        break;

                    case 11 :
                        $this->shellM2Command('wh cr:m');
                        break;
                    case 12 :
                        $this->shellM2Command('wh cr:t');
                        break;
                    case 13 :
                        $this->shellM2Command('wh cr:d');
                        break;

                    case 14 :
                        $this->shellM2Command('wh c:cr');
                        break;
                    case 15 :
                        $this->shellM2Command('wh c:p');
                        break;

                    case 16 :
                        $this->shellM2Command('wh a:cr');
                        break;
                    case 17 :
                        $this->shellM2Command('wh a:p');
                        break;

                    case 18 :
                        $this->shellM2Command('wh t:s');
                        break;
                    case 19 :
                        $this->shellM2Command('wh o:t');
                        break;
                    case 20 :
                        $this->shellM2Command('wh h:on');
                        break;
                    case 21 :
                        $this->shellM2Command('wh h:off');
                        break;

                    case 22 :
                        $this->shellM2Command('wh cloud');
                        break;
                    case 23 :
                        $this->shellM2Command('wh dump');
                        break;
                    case 24 :
                        $this->shellM2Command('wh m:d');
                        break;
                    case 25 :
                        $this->shellM2Command('wh t:r');
                        break;
                    case 26 :
                        $this->shellM2Command('wh t:p');
                        break;
                    case 27 :
                        $this->shellM2Command('wh d:m');
                        break;
                    case 28 :
                        $this->shellM2Command('wh sn');
                        break;
                endswitch;
                $output->writeln('');
                break;





            /**
             * Default behaviour
             */
            default :
                $output->writeln('');
                if(null !== $input->getArgument('action')) {
                    $output->writeln('<error>['.$input->getArgument('action').'] is not defined</error> <info>¯\_(ツ)_/¯</info>');
                } else {
                    $output->writeln('<info>¯\_(ツ)_/¯</info>');
                }

                $output->writeln('');

                $companyName = $this->storeInfo->getCompanyName();
                $defaultTheme = $this->storeInfo->getDefaultTheme();

                if($companyName == NULL || $defaultTheme == NULL) {
                    $output->writeln('<error>Your company name and/or default theme is missing in the app/etc/env.php file. 
Please check the WH documentation: https://github.com/WeidenhammerCommerce/wh/blob/master/README.md</error>
');
                } else {
                    $output->writeln(
                        'The module <info>WH</info> is installed and working correctly.
- Your company name is <info>' . $companyName . '</info>
- Your default theme name is <info>' . $defaultTheme . '</info>');
                    $output->writeln('');
                    $output->writeln('Check all the available actions with <info>' . $this->storeInfo->getBinMagento() . ' ' . self::COMMAND . ' --help</info>');
                    $output->writeln('Show full list to select an option with <info>' . $this->storeInfo->getBinMagento() . ' ' . self::COMMAND . ' options (op)</info>');
                }
        endswitch;
    }


    /**
     * Execute regular shell command
     * @param $command
     */
    protected function shellCommand($command)
    {
        echo shell_exec($command);
    }

    /**
     * Execute M2 shell command
     * @param $command
     */
    protected function shellM2Command($command)
    {
        echo shell_exec($this->storeInfo->getBinMagento() . ' ' . $command);
    }

    /**
     * @param $question
     * @param $defaultValue
     * @param $input
     * @param $output
     * @return mixed
     */
    protected function askQuestion($question, $defaultValue, $input, $output)
    {
        $helper = $this->getHelper('question');
        $questionObj = new Question($question.' ', $defaultValue);
        return $helper->ask($input, $output, $questionObj);
    }

    /**
     * Set new styles
     * Options: symfony/console/Symfony/Component/Console/Formatter/OutputFormatterStyle.php
     * @param $output
     */
    protected function setCustomStyles($output)
    {
        $style = new OutputFormatterStyle('blue', 'black', array('bold', 'underscore'));
        $output->getFormatter()->setStyle('title', $style);
    }
}
