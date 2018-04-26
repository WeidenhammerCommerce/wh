## ¯\ \_(ツ)_/¯
## WH creates a new shell command with a handful of M2 tools

## v.0.1.1

- Added the {tools:regenerate} command

## v.0.1.0

- Added several options to the {create:module} command
```
[1] Extend Block/Model class
[2] Create Plugin for a method
[3] Create frontend page to display template
[4] Create frontend page to display template using view-model
[5] Create frontend page to return JSON
[6] Attach Observer to Event
[7] Replace constructor argument
[8] Create new Command line
[9] Create REST API with ACL
```

## v.0.0.7

- Added the {cloud} command

## v.0.0.6

- Started using the env.php file to stored user variables
- Added the {create:dump} command
- Added shell commands (permissions, static)

## :eye: Installation

- Go to your Magento root folder and run:
```
$ composer require hammer/wh:dev-master
```
- Enter your Company name and other optional parameters in the following array, and add it to the app/etc/env.php file:
```
'wh' =>
  array (
      'company_name' => 'CompanyName', // required, where you place your modules (app/code/[CompanyName])
      'default_theme' => 'CompanyName/ThemeName', // required, theme you're currently working on
      'default_store' => 'CompanyName/StoreName', // required, store you're currently working on
      'localization' => 'en_US', // default localization code
      'composer_files' => 1, // 1 or 0, 1 creates a composer.json on new modules
      'module_version' => '0.0.1', // default version of new modules 
      'dummy_categories' => 1, // default qty of dummy categories
      'dummy_products' => 1, // default qty of dummy products
      'view_product_link' => 1, // 1 or 0, 1 shows 'View Product' link on Admin
      'ask_if_multistore' => 0, // 1 or 0, 1 asks for desired theme/store if multistore
      'magento_cloud' => 0, // 1 or 0, 1 if using Magento Cloud	
      'magento_cloud_project_id' => '' // magento cloud project ID,  if any
  )  
```
Notes for faster development
1) We recommend creating an system alias for "bin/magento". Example: "bm"
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
$ bin/magento wh cache:custom (alias c:c)
```

### Removes the specific cache to regenerate the admin

```
$ bin/magento wh cache:admin (alias c:ad)
```



## :eye: CREATION commands

### Create new module
* (string) Name of the module
* (multiselect) Setup files (example: 1,3 to create InstallData.php and InstallSchema.php)
* (select) Feature (example: 2 to create a plugin)
* (other options) Based on the Feature selected
```
$ bin/magento wh create:module (alias cr:m)
```

### Create new theme
* (string) Name of the theme
* (int) Theme to extend from (blank, Luma or custom)
```
$ bin/magento wh create:theme (alias cr:t)
```

### Create dummy data
* (int) Quantity of categories
* (int) Quantity of products
```
$ bin/magento wh create:dummy (alias cr:d)
```

### Creates dump of the database in the var/dump folder
```
$ bin/magento wh create:dump (alias cr:dump)
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

### Update the password of an existing customer
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
$ bin/magento wh customer:create (alias a:cr)
```

### Update the password of an existing admin user
* (string) Email of existing admin user (autocomplete)
* (string) New password
```
$ bin/magento wh customer:password (alias a:p)
```



## :eye: FRONTEND TOOLS commands

### Deploy frontend static content for given theme
* (string) Name of the theme
```
$ bin/magento wh tools:static (alias t:s)
```

### Copy template from the core to your theme, in order to override it
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
$ bin/magento wh cloud (alias mc)
Select a Magento Cloud command for the project:
  [0 ] [General Info] Project
  [1 ] [General Info] My Account
  [2 ] [General Info] All users
  [3 ] [General Info] All envs
  [4 ] [Environment Info] [env name] Env data
  [5 ] [Environment Info] [env name] Env URLs
  [6 ] [Environment Info] [env name] Env logs
  [7 ] [Environment Info] [env name] Env activity (last 10)
  [8 ] [Branch Action] [branch name, parent branch] Create
  [9 ] [Branch Action] [branch name] Push current (to server branch with the same name)
  [10] [Branch Action] [branch name] Activate remote branch/env
  [11] [Other] [env name] Download dump of env database
  [12] [Other] [env name] Get command to connect to env through SSH
```

### Downgrades the version of the database module to the one on the code
* (string) Name of the existing module
```
$ bin/magento wh module:downgrade (alias m:d)
```

### Regenerate a URL rewrites of products/categories in all/specific store/s
* (multiselect) Store
```
$ bin/magento wh tools:regenerate (alias t:r)
```

### Set proper permissions to all files and folders
```
$ bin/magento wh tools:permissions (alias t:p)
```

### Deploy to given mode
* (multistore) Name of the mode (show, developer or production)
```
$ bin/magento wh deploy:mode (alias d:m)
```




# ¯\\\_(ツ)\_/¯

## Badges

![](https://img.shields.io/badge/license-MIT-blue.svg)
![](https://img.shields.io/badge/status-stable-green.svg)

