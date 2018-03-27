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
        Dummy $dummy
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
            ->setDescription('Company specific command')
            ->setHelp(<<<EOF
<comment>Info</comment>
$ %command.full_name% <info>info:m2 (i:m2)</info> Shows information of the M2 instance
$ %command.full_name% <info>info:store (i:s)</info> Shows information of all of the stores
$ %command.full_name% <info>info:modules (i:m)</info> List all the modules of your company (with its code version)
<comment>Cache</comment>
$ %command.full_name% <info>clean:templates (c:t)</info> Removes the specific cache to regenerate the templates
$ %command.full_name% <info>clean:layouts (c:l)</info> Removes the specific cache to regenerate the layouts
$ %command.full_name% <info>clean:styles (c:s)</info> <question>[name of theme]</question> Removes the specific cache to regenerate the CSS styles
$ %command.full_name% <info>clean:all (c:a)</info> Removes all cache (everything within /var and /pub/static)
$ %command.full_name% <info>clean:custom (c:c)</info> Removes selected cache (separated by comma)
$ %command.full_name% <info>clean:admin (c:ad)</info> Removes the specific cache to regenerate the admin
<comment>Creation</comment>
$ %command.full_name% <info>create:module (cr:m)</info> <question>[name, install file and class file to extend]</question> Creates a new module
$ %command.full_name% <info>create:theme (cr:t)</info> <question>[name and where to extend from]</question> Creates a new theme
$ %command.full_name% <info>create:dummy (cr:d)</info> <question>[qty of categories and products]</question> Creates dummy categories and products
$ %command.full_name% <info>create:dump (cr:dump)</info> Creates dump of the database in the var/dump folder 
<comment>Customer</comment>
$ %command.full_name% <info>customer:create (c:cr)</info> <question>[data of customer]</question> Creates a customer
$ %command.full_name% <info>customer:password (c:p)</info> <question>[email and new password]</question> Updates the password of an existing customer
<comment>Admin</comment>
$ %command.full_name% <info>admin:create (a:cr)</info> <question>[email, username and password]</question> Creates an admin user
$ %command.full_name% <info>admin:password (a:p)</info> <question>[email and new password]</question> Updates the password of an existing admin user
<comment>Shell</comment>
$ %command.full_name% <info>shell:permissions (s:p)</info> Set proper permissions to all files and folders
$ %command.full_name% <info>shell:static (s:s)</info> <question>[name of theme]</question> Deploy static content for given theme 
<comment>Others</comment>
$ %command.full_name% <info>cloud (mc)</info> List of Magento Cloud commands
$ %command.full_name% <info>module:downgrade (m:d)</info> <question>[name of module]</question> Downgrades the version of the database module to the one on the code (useful after changing branches)
$ %command.full_name% <info>override:template (o:t)</info> <question>[name of theme, path to template]</question> Returns the path to your theme in order to override a core template
$ %command.full_name% <info>deploy:mode (d:m)</info> <question>[mode name]</question> Deploy to given mode (developer, production) 
$ %command.full_name% <info>hints:on (h:on)</info> <question>[name of store]</question> Enables the Template Hints
$ %command.full_name% <info>hints:off (h:off)</info> <question>[name of store]</question> Disables the Template Hints


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
             * Shows information of default Theme
             */
            /*case 'info:theme' :
            case 'i:theme' : case 'info:t' :
            case 'i:t' :
                $output->writeln('
<title>Theme Information</title>
<info>Theme ID:</info> '.$this->storeInfo->getDefaultThemeId().'
<info>Theme Company:</info> '.$this->storeInfo->getDefaultThemeCompany().'
<info>Theme Title:</info> '.$this->storeInfo->getDefaultThemeName().'
<info>Theme Path:</info> '.$this->storeInfo->getDefaultThemePath());
                $output->writeln('');
                break;*/


            /**
             * List all of the company modules
             */
            case 'info:modules' :
            case 'i:modules' : case 'info:m' :
            case 'i:m' :
                // Show list of enabled company modules
                $output->writeln('<info>Enabled modules:</info>');
                $enabledQty = 0;
                foreach ($this->moduleList->getAll() as $m) {
                    if (strpos($m['name'], $this->storeInfo->getCompanyName()) !== false) {
                        $enabledQty++;
                        $output->writeln($m['name'] . ' -> ' . $m['setup_version']);
                    }
                }
                if(!$enabledQty) {
                    $output->writeln('Nothing found');
                }

                // Show list of disabled company modules
                $output->writeln(
                    '<info>Disabled modules:</info>');
                $enabledModules = $this->moduleList->getNames();
                $disabledModules = array_diff($this->fullModuleList->getNames(), $enabledModules);
                $disabledQty = 0;
                foreach ($disabledModules as $dm) {
                    if (strpos($dm, $this->storeInfo->getCompanyName()) !== false) {
                        $disabledQty++;
                        $output->writeln($dm);
                    }
                }
                if(!$disabledQty) {
                    $output->writeln('Nothing found');
                }
                break;



            /**
             * CACHE
             **********************************************************************************************************/

            /**
             * Clean cache for Templates & Layouts
             */
            case 'clean:templates' : case 'clean:layouts' :
            case 'c:templates' : case 'c:layouts' :
            case 'clean:t' : case 'clean:l' :
            case 'c:t' : case 'c:l' :
            $this->cache->removeBasicCache();
            $output->writeln('<info>Cache cleared.</info>');
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
                        'Name of the theme (Hit <comment>Enter</comment> to use <info>' . $dftTheme . '</info>):',
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
            case 'c:custom' : case 'clean:c' :
            case 'c:c' :
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
                    'Please select one (or more, separated by comma) cache folders to clear:',
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
                // Ask for a module name
                $moduleName = $this->askQuestion(
                    'Name of the module:',
                    NULL,
                    $input, $output
                );
                if(!$moduleName) {
                    $output->writeln('<error>You must enter a name for the module</error>');
                    break;
                }


                // Ask for an install file
                $installOption = $this->askQuestion(
                    'Create an InstallData file (<comment>y/n</comment>; Hit <comment>Enter</comment> to skip):',
                    'n',
                    $input, $output
                );
                $installOption = strtolower($installOption) == 'y' ? true : false;

                // Ask for a di file
                // Examples:
                // - Magento\Wishlist\Block\Customer\Wishlist
                // - Magento\Catalog\Model\Category\Attribute
                $diClassName = $this->askQuestion(
                    'Class to extend using di.xml (Hit <comment>Enter</comment> to skip):',
                    NULL,
                    $input, $output
                );

                // Create module
                if($this->create->createModule($moduleName, $diClassName, $installOption)) {
                    $output->writeln('The module <info>' . $moduleName . '</info> was created successfully.');
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
                    'Magento/Luma',
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
                    'Quantity of categories (Hit <comment>Enter</comment> to create <info>'.$dftCatQty.'</info>):',
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
                    'Quantity of products (Hit <comment>Enter</comment> to create <info>'.$dftProdQty.'</info>):',
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
Don\'t forget to reindex (<info>bin/magento indexer:reindex</info>).');
                break;


            /**
             * Create dump of the database
             */
            case 'create:dump' :
            case 'cr:dump' :
                $output->writeln('Starting the DB backup, please wait');

                $dbInfo = $this->deploymentConfig->get('db')['connection']['default'];

                $today = getdate();
                $user = $dbInfo['username'];
                $host = $dbInfo['host'];
                $pass = $dbInfo['password'];
                $dbname = $dbInfo['dbname'];

                $filename = $dbname . '-' .
                    $today['mon'] . '-' .
                    $today['mday'] . '-' .
                    $today['hours'] . '-' .
                    $today['minutes'] .
                    '.sql';

                $backupsDir = $this->directoryList->getPath(DirectoryList::VAR_DIR) . '/backups';

                if (!$this->file->isExists($backupsDir)) {
                    $this->file->createDirectory($backupsDir);
                }

                $destination = $backupsDir . '/' . $filename;
                $commmand = 'mysqldump -u' . $user . ' -h' . $host . ' -p' . $pass . ' ' . $dbname . ' >>' . $destination;

                shell_exec($commmand);

                $output->writeln('Dump saved in <info>'.$destination.'</info>');
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
             * Update an admin password
             */
            case 'admin:create' :
            case 'a:create' : case 'admin:cr' :
            case 'a:cr' :
                // Ask for admin user data
                $email = $this->askQuestion(
                    'Email:',
                    NULL,
                    $input, $output
                );
                if(!$email) {
                    $output->writeln('<error>You must enter an email for the new admin user</error>');
                    break;
                }

                $username = $this->askQuestion(
                    'Username:',
                    NULL,
                    $input, $output
                );
                if(!$username) {
                    $output->writeln('<error>You must enter a username for the new admin user</error>');
                    break;
                }

                $password = $this->askQuestion(
                    'Password:',
                    NULL,
                    $input, $output
                );
                if(!$password) {
                    $output->writeln('<error>You must enter a password for the new admin user</error>');
                    break;
                }

                $adminInfo = [
                    'username'  => $username,
                    'firstname' => $username,
                    'lastname'    => $username,
                    'email'     => $email,
                    'password'  => $password,
                    'interface_locale' => 'en_US',
                    'is_active' => 1
                ];

                $userModel = $this->userFactory->create();
                $userModel->setData($adminInfo);
                $userModel->setRoleId(1);

                $userModel->save();
                $output->writeln('The admin user <info>'.$username.'</info> was created successfully with the <info>'.$email.'</info> email.');

                /*try{
                    $userModel->save();
                } catch (\Exception $ex) {
                    $ex->getMessage();
                }*/
                break;


            /**
             * Update an admin password
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
             * SHELL
             **********************************************************************************************************/

            /**
             * Set write permissions
             */
            case 'shell:permissions' :
            case 's:p' :
                $output->writeln('Setting proper permissions for <info>M2</info> files and folders, please wait');
                shell_exec('sudo find . -type f -exec chmod 660 {} ";" && sudo find . -type d -exec chmod 770 {} ";"');
                shell_exec('sudo chmod -R 777 app/etc pub/media pub/static generated var');
                shell_exec('chmod u+x bin/magento');
                $output->writeln('Proper permissions given to all <info>M2</info> files and folders');
                break;

            /**
             * Deploy static content
             */
            case 'shell:static' :
            case 's:s' :
                $dftTheme = $this->storeInfo->getDefaultTheme();

                // If multistore, ask for theme name
                if($this->storeInfo->isMultistore() && $this->storeInfo->getAskIfMultistore()) {
                    $theme = $this->askQuestion(
                        'Name of the theme (Hit <comment>Enter</comment> to use <info>'.$dftTheme.'</info>):',
                        $dftTheme,
                        $input, $output
                    );
                } else {
                    $theme = $dftTheme;
                }

                // Force it? (new in Magento 2.2.2)
                $forceOption = $this->askQuestion(
                    'Force it? (<comment>y/n</comment>; Hit <comment>Enter</comment> to skip):',
                    'n',
                    $input, $output
                );

                $forceOption = strtolower($forceOption) == 'y' ? ' -f' : '';

                $output->writeln('Deploying static content for the theme <info>'.$dftTheme.'</info>, please wait');
                echo shell_exec('bin/magento setup:static-content:deploy --area frontend --no-fonts --theme '.$theme . $forceOption);
                break;



            /**
             * OTHERS
             **********************************************************************************************************/

            /**
             * List of Magento Cloud commands
             */
            case 'cloud' : case 'mc' :
            $dialog = $this->getHelper('dialog');
            $mcOptions = array(
                'See project info', // 0
                'See your account info', // 1
                'See all users', // 2
                'See all envs', // 3
                'See env info', // 4
                'See env URLs', // 5
                'See env logs', // 6
                'See env activity (last 10)', // 7
                'Create branch', // 8
                'Push current branch (to server branch with the same name)', // 9
                'Activate env', // 10
                'Download dump of env database', // 11
                'Get command to connect to env through SSH' // 12
            );
            $selected = $dialog->select(
                $output,
                'Select a Magento Cloud command for the current project:',
                $mcOptions,
                0,
                false,
                'Value "%s" is invalid',
                false // enable multiselect
            );

            $projectId = $this->storeInfo->getMagentoCloudProjectId();

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
                    echo shell_exec('magento-cloud project:info -p '.$projectId);
                    break;
                case 1 :
                    // See your account info
                    echo shell_exec('magento-cloud auth:info');
                    break;
                case 2 :
                    // See all users
                    echo shell_exec('magento-cloud user:list -p '.$projectId);
                    break;
                case 3 :
                    // See all envs
                    echo shell_exec('magento-cloud environments -p '.$projectId);
                    break;
                case 4 :
                    // See env info
                    echo shell_exec('magento-cloud environment:info -p '.$projectId.' -e '.$envName);
                    break;
                case 5 :
                    // See envs URLs
                    echo shell_exec('magento-cloud environment:url -p '.$projectId.' -e '.$envName);
                    break;
                case 6 :
                    // See envs logs
                    echo shell_exec('magento-cloud environment:logs -p '.$projectId.' -e '.$envName);
                    break;
                case 7 :
                    // See envs activity
                    echo shell_exec('magento-cloud activity:list -p '.$projectId.' -e '.$envName);
                    break;
                case 8 :
                    // Create branch
                    echo shell_exec('magento-cloud environment:branch -p '.$projectId.' '.$branchName.' '.$masterBranch);
                    break;
                case 9 :
                    // Push current branch
                    echo shell_exec('magento-cloud environment:push');
                    break;
                case 10 :
                    // Activate env
                    echo shell_exec('magento-cloud activate:environment -p '.$projectId.' -e '.$envName);
                    break;
                case 11 :
                    // Download env dump
                    echo shell_exec('magento-cloud db:dump -p '.$projectId.' -e '.$envName);
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
             * Override template
             * Examples:
             * - vendor/magento/module-checkout/view/frontend/templates/cart.phtml
             * @todo: copy the file automatically
             */
            case 'override:template' :
            case 'o:template' : case 'override:t' :
            case 'o:t' :
                $dftTheme = $this->storeInfo->getDefaultTheme();

                // If multistore, ask for theme name
                if($this->storeInfo->isMultistore() && $this->storeInfo->getAskIfMultistore()) {
                    $theme = $this->askQuestion(
                        'Name of the theme (Hit <comment>Enter</comment> to use <info>' . $dftTheme . '</info>):',
                        $dftTheme,
                        $input, $output
                    );
                } else {
                    $theme = $dftTheme;
                }

                // Ask for file to override
                $file = $this->askQuestion(
                    'Path of the template to be overridden (starting with <info>vendor/...</info>):',
                    NULL,
                    $input, $output
                );
                if(!$file) {
                    $output->writeln('<error>Please enter a path of the file to override</error>');
                    break;
                }

                // Get path to override template
                $fullPath = $this->create->overrideTemplate($file, $theme);

                $output->writeln('
Override the template by copying it to <info>'.$fullPath.'</info>
Please remember to remove the Magento copyright once you copied it.
');
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
                        echo shell_exec('bin/magento deploy:mode:show');
                        break;
                    case 1 :
                        // Set to Developer
                        echo shell_exec('bin/magento deploy:mode:set developer');
                        break;
                    case 2 :
                        // Set to Production
                        echo shell_exec('bin/magento deploy:mode:set production');
                        break;
                endswitch;
                break;


            /**
             * Enable the template hints
             */
            case 'hints:on' :
            case 'h:on' :
                // Prepare connection
                $connection = $this->resource->getConnection('default');

                // Ask for store name
                $defaultStoreName = $this->storeInfo->getDefaultStoreName();

                // If multistore, ask for store name
                if($this->storeInfo->isMultistore() && $this->storeInfo->getAskIfMultistore()) {
                    $storeName = $this->askQuestion(
                        'Name of the store (Hit <comment>Enter</comment> to use <info>'.$defaultStoreName.'</info>):',
                        $defaultStoreName,
                        $input, $output
                    );
                } else {
                    $storeName = $defaultStoreName;
                }

                // Get store id
                $storeId = $this->storeInfo->getDefaultStoreId();
                if($storeName !== $defaultStoreName) {
                    $result = $connection->fetchRow("SELECT store_id FROM store WHERE name LIKE '%$storeName%'");
                    $storeId = $result['store_id'];
                }

                // Check if they are already enabled
                $result = $connection->fetchRow("
                    SELECT config_id FROM core_config_data
                    WHERE path = 'dev/debug/template_hints_storefront'
                      AND scope_id = $storeId
                      AND value = 1
                ");
                if(null !== $result['config_id']) {
                    $output->writeln("Templates Hints were already <info>enabled</info> for the <info>".$storeName."</info> store");
                } else {
                    // Enable the Template Hints
                    $this->config->saveConfig('dev/debug/template_hints_storefront', 1, 'stores', $storeId);

                    // Remove required cache
                    $this->cache->removeBasicCache();

                    $output->writeln("Templates Hints are now <info>enabled</info> for the <info>".$storeName."</info> store");
                }
                break;


            /**
             * Disable the template hints
             */
            case 'hints:off' :
            case 'h:off' :
                // Ask for store name
                $defaultStoreName = $this->storeInfo->getDefaultStoreName();

                // If multistore, ask for store name
                if($this->storeInfo->isMultistore() && $this->storeInfo->getAskIfMultistore()) {
                    $storeName = $this->askQuestion(
                        'Name of the store (Hit <comment>Enter</comment> to use <info>'.$defaultStoreName.'</info>):',
                        $defaultStoreName,
                        $input, $output
                    );
                } else {
                    $storeName = $defaultStoreName;
                }

                $connection = $this->resource->getConnection('default');
                $result = $connection->fetchRow("SELECT store_id FROM store WHERE name LIKE '%$storeName%'");
                $storeId = $result['store_id'];

                // Validate
                if(null == $storeId) {
                    $output->writeln('<error>We couldn\'t find any storeId for the <info>'.$storeName.'</info> store</error>');
                }

                $connection = $this->resource->getConnection('default');
                $result = $connection->fetchRow("
                    SELECT config_id FROM core_config_data
                    WHERE path = 'dev/debug/template_hints_storefront'
                      AND scope_id = $storeId
                      AND value = 1
                ");
                if(null !== $result['config_id']) {
                    // Disable the Template Hints
                    $this->config->deleteConfig('dev/debug/template_hints_storefront', 'stores', $storeId);

                    // Remove required cache
                    $this->cache->removeBasicCache();

                    $output->writeln("Templates Hints <info>disabled</info> for the <info>".$storeName."</info> store");
                } else {
                    $output->writeln("Templates Hints were already <info>disabled</info>");
                }
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
Please check the WH documentation: https://github.com/WeidenhammerCommerce/wh/blob/master/README.md</error>');
                } else {
                    $output->writeln(
                        'The module <info>WH</info> is installed and working correctly.
- Your company name is <info>'.$companyName.'</info>.
- Your default theme name is <info>'.$defaultTheme.'</info>.                    
Check all the available actions with <info>bin/magento '.self::COMMAND.' --help</info>');
                }

                $output->writeln('');
        endswitch;
    }

    /**
     * @param $question
     * @param $defaultValue
     * @param $input
     * @param $output
     * @return mixed
     */
    private function askQuestion($question, $defaultValue, $input, $output)
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
    private function setCustomStyles($output)
    {
        $style = new OutputFormatterStyle('blue', 'black', array('bold', 'underscore'));
        $output->getFormatter()->setStyle('title', $style);
    }
}
