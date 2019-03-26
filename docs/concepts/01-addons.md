# Addons
Every single reusable composer package in superV platform is called an `addon`. 

superV groups all your platform related addon packages under the `addons` directory that is located at your project root. While installing through composer, it detects platform specific packages and moves them here instead of the default vendor folder.  
  
Every superV addon has a unique slug combined of 3 parameters; `vendor.plural_addon_type.name`

### Addon Types
Different types of addons have different features. Valid addon types are:
- Module
- Drop
- Agent

For now we will use the type `module` which is most inclusive addon type.
 
### Creating an addon
Let's create a sampl addon of type `module` to demonstrate the key features mentioned above. We will be creating a CRM module for our company ACME, thus our addon slug will be `acme.modules.crm`. 

Let's do this using the command line tool:

```bash
php artisan make:addon acme.modules.crm
```

You can now find the created module files in `addons/acme/modules/crm` directory.

## Installing 
Before using your addon, you must install it first:

```bash
php artisan addon:install acme.modules.crm
```

This would run the migrations located in your addon's `database/migrations` folder if any.

While developing an addon, you can use `addon:reinstall` command to uninstall and install again. And also `addon:uninstall` to uninstall it. 

‼ Note that, uninstalling an addon rollbacks all it's migrations, thus would drop related database tables.
