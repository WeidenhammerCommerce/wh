## ¯\ \_(ツ)_/¯
## WH creates a new shell command {wh} with a handful of M2 tools

## v.0.1.3

- Added config option to display all layout handles at the top of the page

## v.0.1.1

- Added the {tools:regenerate} command

## v.0.1.0

- Refactored code and added several options to the {create:module} command

## v.0.0.7

- Added the {cloud} command

## :eye: Installation

- Go to your Magento root folder and run:
```
$ composer require hammer/wh:dev-master
```
- Enter your Company name, theme, store and other optional parameters in the following array, and add it to the app/etc/env.php file:
```
'wh' =>
  array (
      'company_name' => 'CompanyName', // required, where you place your modules (app/code/[CompanyName] or extensions/[CompanyName])
      'default_theme' => 'CompanyName/ThemeName', // required, theme you're currently working on
      'default_store' => 'Default Store View', // required, store you're currently working on
      'localization' => 'en_US', // default localization code
      'composer_files' => 1, // 1 or 0, 1 creates a composer.json on new modules
      'module_version' => '0.0.1', // default version of new modules 
      'dummy_categories' => 1, // default qty of dummy categories
      'dummy_products' => 1, // default qty of dummy products
      'view_product_link' => 1, // 1 or 0, 1 shows 'View Product' link on Admin
      'ask_if_multistore' => 0, // 1 or 0, 1 asks for desired theme/store if multistore
      'save_db_folder' => 'var/dump', // folder to save the database dump
      'display_handles' => 0, // display all layout handles at the top of all pages
      'admin_credentials' => 'username::password', // display credentials on the admin login page 
      'magento_cloud' => 0, // 1 or 0, 1 if using Magento Cloud	
      'magento_cloud_project_id' => '', // magento cloud project ID, if any
      'magento_command' => 'bin/magento' // magento command line
  )  
```
Notes for faster development
1) We recommend creating a system alias for "bin/magento". Example: "bm"
2) If multistore, we recommend setting the "default_theme" config along with "ask_if_multistore=0"



- Enable the WH module:
```
$ bin/magento module:enable Hammer_WH
$ bin/magento s:up
$ bin/magento c:f
```
- You are good to go! You can check it was installed correctly by running:
```
$ bin/magento wh
```

### Update the WH module to its latest version

- Go to your Magento root folder and run:
```
$ composer update hammer/wh
```

Note: remember to always develop in *Developer* mode



## :eye: INFO commands

### Show all available commands 

```
$ bin/magento wh --help
```

### Show all available commands with an option to select it

```
$ bin/magento wh options (alias op)
```

### Show information of your Magento instance (Edition, Version, Mode, Session, Crypt Key and Install Date)

```
$ bin/magento wh info:m2 (alias i:m2)
```

### Show information of all of your Stores (ID, Title and Code)

```
$ bin/magento wh info:store (alias i:s)
```

### List modules (with its code version)
* (multiselect) Type of modules

```
$ bin/magento wh info:modules (alias i:m)
```



## :eye: CACHE commands

### Removes the specific cache to regenerate the templates

```
$ bin/magento wh cache:templates (alias c:t)
```

### Removes the specific cache to regenerate the layouts

```
$ bin/magento wh cache:layouts (alias c:l)
```

### Removes the specific cache after changing admin configurations

```
$ bin/magento wh cache:config (alias c:c)
```

### Removes var/cache & var/page_cache

```
$ bin/magento wh cache:layouts (alias c:v)
```

### Removes the specific cache to regenerate the DI

```
$ bin/magento wh cache:generated (alias c:g)
```

### Removes the specific cache to regenerate the styles
* (string) Name of the theme

```
$ bin/magento wh cache:styles (alias c:s)
```

### Removes all cache (everything within /var and /pub/static, and /generated if Cloud) 

```
$ bin/magento wh cache:all (alias c:a)
```

### Removes selected cache (separated by comma) 
* (multiselect) Folders to remove (example: 1,3,5)
```
$ bin/magento wh cache:custom (alias c:cu)
```

### Removes the specific cache to regenerate the admin

```
$ bin/magento wh cache:admin (alias c:ad)
```



## :eye: CREATION commands

### Create new module
* (string) Name of the module
* (multiselect) Setup files (example: 1,3 to create InstallData.php and InstallSchema.php)
* (select) Feature
  - Extend Block/Model class
  - Create Plugin for a method
  - Create frontend page to display template
  - Create frontend page to display template using view_model
  - Create frontend page to return JSON
  - Attach Observer to Event
  - Replace constructor argument
  - Create new Command line
  - Create REST API with ACL
* (other options) Based on the Feature selected
```
$ bin/magento wh create:module (alias cr:m)
```

### Create new theme
* (string) Name of the theme
* (int) Theme to extend from: blank, Luma (beta) or custom
```
$ bin/magento wh create:theme (alias cr:t)
```

### Create dummy data
* (int) Quantity of categories
* (int) Quantity of products
```
$ bin/magento wh create:dummy (alias cr:d)
```



## :eye: CUSTOMER commands

### Create new customer
* (string) First name
* (string) Last name
* (string) Email
* (string) Password
```
$ bin/magento wh customer:create (alias c:cr)
```

### Update password of existing customer
* (string) Email of existing customer (autocomplete)
* (string) New password
```
$ bin/magento wh customer:password (alias c:p)
```



## :eye: ADMIN commands

### Create new admin user
* (string) Email
* (string) Username
* (string) Password
```
$ bin/magento wh admin:create (alias a:cr)
```

### Update password of existing admin user
* (string) Email of existing admin user (autocomplete)
* (string) New password
```
$ bin/magento wh admin:password (alias a:p)
```



## :eye: FRONTEND TOOLS commands

### Deploy frontend static content for given theme
* (string) Name of the theme
```
$ bin/magento wh tools:static (alias t:s)
```

### Copy core template to theme to override it
* (string) Name of the theme
* (string) Path to the existing template (example: vendor/magento/module-checkout/view/frontend/templates/cart.phtml)
```
$ bin/magento wh override:template (alias o:t)
```

### Enable the Template Hints
```
$ bin/magento wh hints:on (alias h:on)
```

### Disable the Template Hints
```
$ bin/magento wh hints:off (alias h:off)
```



## :eye: OTHER TOOLS commands

### List of Magento Cloud commands
```
$ bin/magento wh cloud
Select a Magento Cloud command for the project:
  [0 ] See project info
  [1 ] See your account info
  [2 ] See all users
  [3 ] See all envs
  [4 ] See env info
  [5 ] See env URLs
  [6 ] See env logs
  [7 ] See env activity (last 10)
  [8 ] Create branch
  [9 ] Activate env
  [10] Download dump of env database
  [11] Get command to connect to env through SSH
```

### Creates dump of the database in given folder
```
$ bin/magento wh dump
```

### Downgrades version of the database module to the one on the code
(useful after changing branches)
* (string) Name of the existing module
```
$ bin/magento wh module:downgrade (alias m:d)
```

### Regenerate URL rewrites of products/categories in all/specific store/s
* (multiselect) Store
```
$ bin/magento wh tools:regenerate (alias t:r)
```

### Deploy to given mode
* (select) Name of the mode (show, developer or production)
```
$ bin/magento wh deploy:mode (alias d:m)
```

### Show M2 snippets
* (select) Snippet to show
```
$ bin/magento wh snippets (alias sn)
```


# Send us your feedback!
:email: wh@magento.com.ar


# ¯\\\_(ツ)\_/¯

## Badges

![](https://img.shields.io/badge/license-MIT-blue.svg)
![](https://img.shields.io/badge/status-stable-green.svg)

