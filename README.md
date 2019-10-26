Customizable Installer for PyroCMS
==================================

This package allows you to:

- Exclude certain modules or extensions from being installed
- Exclude certain modules or extensions from being seeded
- Skip certain installer steps
- Start installation at step
- Thrown exceptions during installation will ask you if you want to continue to the next step or
- Add PyroCMS add-ons as composer dependency without automatically installing them
- Ignore trivial errors
- etc...


Installation
------------

### Provider
```
\Pyradic\CustomInstall\CustomInstallServiceProvider
```

### Configuration
Use the provided config. Some examples:

```php
return [
    // call artisan commands before running install
    'call_before'          => [
        ['db:seed', ['--class' => 'Name\\Space\\Class']]
    ],
    
    // dispatch bus commands before running install
    'dispatch_before'      => [
        function($app){
            return new \Name\Space\PreInstallActions();
        }
    ],
    
    // call artisan commands after running install
    'call_after'           => [
        ['db:seed', ['--class' => 'Namespace\\To\\Class']]
    ],
    
    // dispatch bus commands after running install
    'dispatch_after'       => [
        function($app){
            return new \Name\Space\PostInstallActions();
        }
    ],
    
    // --skip_steps=11,22,46
    'skip_steps'           => [
        // 11, 22, 46
    ],
    
    // --start_from_step=1
    'start_from_step'      => 1,
    
    // --ignore_exceptions
    // if true, asks to continue 
    // if true + no-interaction, continues with warning 
    'ignore_exceptions'    => false, 
    
    // skip seeds of certain modules 
    'skip_seed'            => [
        'anomaly.module.users',
    ],
    
    // `include` takes precedence over `exclude`
    'include'              => [
        'pyro.module.*',
        'anomaly.module.preferences',
        'anomaly.module.configuration',
        'anomaly.module.dashboard',
        'anomaly.module.repeaters',
        'anomaly.module.search',
        'anomaly.module.settings',
        'anomaly.module.users',
        'anomaly.extension.default_authenticator',
        'anomaly.extension.html_block',
        'anomaly.extension.html_widget',
        'anomaly.extension.private_storage_adapter',
        'anomaly.extension.robots',
        'anomaly.extension.sitemap',
        'anomaly.extension.throttle_security_check',
        'anomaly.extension.user_security_check',
        'anomaly.extension.wysiwyg_block',
        'anomaly.extension.xml_feed_widget',    
    ],
    'exclude'              => [
        '*' // excluding all, which makes it only install addons defined in `include` 
    ],
    'skip_base_migrations' => false, // skip database/migrations/*
    'skip_base_seeds'      => false, // skip database/seeds/*
];

```


## `install` Command

You might prefer to check this in the console using the `--help` option.  
Basically all `custom_install.php` configuration values can be overridden using the options.

### Usage

* `install [--ready] [--call_before [CALL_BEFORE]] [--dispatch_before [DISPATCH_BEFORE]] [--call_after [CALL_AFTER]] [--dispatch_after [DISPATCH_AFTER]] [--skip_steps [SKIP_STEPS]] [--start_from_step [START_FROM_STEP]] [--ignore_exceptions] [--skip_install [SKIP_INSTALL]] [--skip_seed [SKIP_SEED]] [--include [INCLUDE]] [--exclude [EXCLUDE]] [--skip_base_migrations] [--skip_base_seeds] [--] [<method>]`
* `install list`


### Arguments

#### `method`

method name

* Is required: no
* Is array: no
* Default: `'install'`

### Options

#### `--ready`

Indicates that the installer should use an existing .env file.

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--call_before`

* Accept value: yes
* Is value required: no
* Is multiple: no
* Default: The value of this option in the `custom_install.php` configuration file

#### `--dispatch_before`

* Accept value: yes
* Is value required: no
* Is multiple: no
* Default: The value of this option in the `custom_install.php` configuration file

#### `--call_after`

* Accept value: yes
* Is value required: no
* Is multiple: no
* Default: The value of this option in the `custom_install.php` configuration file

#### `--dispatch_after`

* Accept value: yes
* Is value required: no
* Is multiple: no
* Default: The value of this option in the `custom_install.php` configuration file

#### `--skip_steps`

* Accept value: yes
* Is value required: no
* Is multiple: no
* Default: The value of this option in the `custom_install.php` configuration file

#### `--start_from_step`

* Accept value: yes
* Is value required: no
* Is multiple: no
* Default: The value of this option in the `custom_install.php` configuration file

#### `--ignore_exceptions`

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: The value of this option in the `custom_install.php` configuration file

#### `--skip_install`

* Accept value: yes
* Is value required: no
* Is multiple: no
* Default: The value of this option in the `custom_install.php` configuration file

#### `--skip_seed`

* Accept value: yes
* Is value required: no
* Is multiple: no
* Default: The value of this option in the `custom_install.php` configuration file

#### `--include`

* Accept value: yes
* Is value required: no
* Is multiple: no
* Default: The value of this option in the `custom_install.php` configuration file

#### `--exclude`

* Accept value: yes
* Is value required: no
* Is multiple: no
* Default: The value of this option in the `custom_install.php` configuration file

#### `--skip_base_migrations`

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: The value of this option in the `custom_install.php` configuration file

#### `--skip_base_seeds`

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: The value of this option in the `custom_install.php` configuration file

#### `--help|-h`

Display this help message

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: The value of this option in the `custom_install.php` configuration file

#### `--quiet|-q`

Do not output any message

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: The value of this option in the `custom_install.php` configuration file

#### `--verbose|-v|-vv|-vvv`

Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: The value of this option in the `custom_install.php` configuration file

#### `--version|-V`

Display this application version

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: The value of this option in the `custom_install.php` configuration file

#### `--ansi`

Force ANSI output

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: The value of this option in the `custom_install.php` configuration file

#### `--no-ansi`

Disable ANSI output

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: The value of this option in the `custom_install.php` configuration file

#### `--no-interaction|-n`

Do not ask any interactive question

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: The value of this option in the `custom_install.php` configuration file

#### `--env`

The environment the command should run under

* Accept value: yes
* Is value required: no
* Is multiple: no
* Default: The value of this option in the `custom_install.php` configuration file

#### `--app`

The application this command should run under.

* Accept value: yes
* Is value required: no
* Is multiple: no
* Default: The value of this option in the `custom_install.php` configuration file