## ¯\ \_(ツ)_/¯
## WH creates a new shell command with a handful of M2 tools

## v.0.0.7

- Added cloud command

## v.0.0.6

- Started using the env.php file to stored user variables
- Added create:dump
- Added shell commands (permissions, static)

## v.0.0.5

- Added info:m2
- Added check for custom theme
- Improved the Cache Block

## v.0.0.4

- Added create:module
- Added create:theme
- Added create:dummy
- Improved clean:styles

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
      'localization' => 'en_US', // default localization code
      'composer_files' => 1, // 1 or 0, 1 creates a composer.json on new modules
      'module_version' => '0.0.1', // default version of new modules 
      'dummy_categories' => 1, // default qty of dummy categories
      'dummy_products' => 1, // default qty of dummy products
      'ask_if_multistore' => 0, // 1 or 0, 1 asks for desired theme/store if multistore
      'magento_cloud_project_id' => '' // magento cloud project ID,  if any
  )  
```
Note: if multistore, we recommend setting the default_theme along with ask_if_multistore=0 to work faster

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

### List all the modules of your company (with its code version)

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

### Removes all cache (everything within /var and /pub/static) 

```
$ bin/magento wh cache:all (alias c:a)
```

### Removes selected cache (separated by comma) 
* (string) Folders to remove (example: 1,3,5)
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
* (y/n) Create InstallData file?
* (string) Class to extend using di.xml (example: Magento\Wishlist\Block\Customer\Wishlist or Magento\Catalog\Model\Category\Attribute)
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



## :eye: SHELL commands

### Set proper permissions to all files and folders
```
$ bin/magento wh shell:permissions (alias s:p)
```

### Deploy frontend static content for given theme (no fonts)
* (string) Name of the theme (if multistore)
```
$ bin/magento wh shell:static (alias s:s)
```



## :eye: OTHER commands

### List of Magento Cloud commands
```
$ bin/magento wh cloud (alias mc)
Select a Magento Cloud command for the project:
  [0 ] See project info
  [1 ] See your account info
  [2 ] See all users
  [3 ] See all envs
  [4 ] See env info
  [5 ] See env URLs
  [6 ] See env logs
  [7 ] See env activity (last 10)
  [8 ] Activate env
  [9 ] Download dump of env database
  [10] Get command to connect to env through SSH
```

### Downgrades the version of the database module to the one on the code
* (string) Name of the existing module
```
$ bin/magento wh module:downgrade (alias m:d)
```

### Return path to your Theme in order to override a core template
* (string) Name of the theme
* (string) Path to the existing template (example: vendor/magento/module-checkout/view/frontend/templates/cart.phtml)
```
$ bin/magento wh override:template (alias o:t)
```

### Enables the Template Hints

```
$ bin/magento wh hints:on (alias h:on)
```

### Disable the Template Hints

```
$ bin/magento wh hints:off (alias h:off)
```




# ¯\\\_(ツ)\_/¯

## Badges

![](https://img.shields.io/badge/license-MIT-blue.svg)
![](https://img.shields.io/badge/status-stable-green.svg)

