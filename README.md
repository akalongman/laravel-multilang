# Laravel 5.x MultiLanguage

[![Build Status](https://img.shields.io/travis/akalongman/laravel-multilang/master.svg?style=flat-square)](https://travis-ci.org/akalongman/laravel-multilang)
[![Code Coverage](https://img.shields.io/scrutinizer/coverage/g/akalongman/laravel-multilang.svg?style=flat-square)](https://scrutinizer-ci.com/g/akalongman/laravel-multilang/?branch=master)
[![Code Quality](https://img.shields.io/scrutinizer/g/akalongman/laravel-multilang.svg?style=flat-square)](https://scrutinizer-ci.com/g/akalongman/laravel-multilang/?branch=master)
[![Latest Stable Version](https://img.shields.io/github/release/akalongman/laravel-multilang.svg?style=flat-square)](https://github.com/akalongman/laravel-multilang/releases)
[![Total Downloads](https://img.shields.io/packagist/dt/Longman/laravel-multilang.svg)](https://packagist.org/packages/longman/laravel-multilang)
[![Downloads Month](https://img.shields.io/packagist/dm/Longman/laravel-multilang.svg)](https://packagist.org/packages/longman/laravel-multilang)
[![License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)

This is a very useful package to integrate multi language (multi locale) functionality in Laravel 5.x.
It includes a ServiceProvider to register the multilang and Middleware for automatic modification routes like `http://site.com/en/your-routes`.

This package uses database for storing translations (it caches data on production environment for improving performance)
Also package automatically adds in database missing keys (on the local environment only).

## Table of Contents
- [Installation](#installation)
- [Usage](#usage)
    - [Translating](#translating)
    - [Blade Templates](#blade-templates)
    - [URL Generation](#url-generation)
    - [Text Scopes](#text-scopes)
    - [Import/Export Texts](#importexport-texts)
- [TODO](#todo)
- [Troubleshooting](#troubleshooting)
- [Contributing](#contributing)
- [License](#license)
- [Credits](#credits)


## Installation

Install this package through [Composer](https://getcomposer.org/).

Edit your project's `composer.json` file to require `longman/laravel-multilang`

Create *composer.json* file:
```json
{
    "name": "yourproject/yourproject",
    "type": "project",
    "require": {
        "longman/laravel-multilang": "~1.2"
    }
}
```
And run composer update

**Or** run a command in your command line:

    composer require longman/laravel-multilang

In Laravel 5.5 the service provider will automatically get registered. 
In older versions of the framework just add the service provider in `config/app.php` file:

```php
Longman\LaravelMultiLang\MultiLangServiceProvider::class,
```

Also you can add facade to the alias array in the `config/app.php`
```php
'MultiLang' => Longman\LaravelMultiLang\Facades\MultiLang::class,
```

Copy the package config to your local config with the publish command:

    php artisan vendor:publish --provider="Longman\LaravelMultiLang\MultiLangServiceProvider"


After run multilang migration command

    php artisan multilang:migration

Its creates multilang migration file in your database/migrations folder. After you can run

    php artisan migrate


Also if you want automatically change locale depending on url (like `http://site.com/en/your-routes`)
you must add middleware in app/Http/Kernel.php

I suggest add multilang after CheckForMaintenanceMode middleware
```php
protected $middleware = [
    \Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode::class,
    \Longman\LaravelMultiLang\Middleware\MultiLang::class,
];
```

In your RoutesServiceProvider modify that:
```php
MultiLang::routeGroup(function($router) {
    require app_path('Http/routes.php');
});
```

or directly in app/Http/routes.php file add multilang group:
```php
MultiLang::routeGroup(function($router) {
    // your routes and route groups here
});
```

Or if you want only translate strings without automatic resolving locale and redirect, 
you can manually set locale in your application like:
```php
App::setLocale('en');
```


## Usage

### Translating
Everywhere in application you can use `t()` helper function like:

```php
$string = t('Your translatable string');
```

You can use markers for dynamic texts and pass any data like
```php
$string = t('The :attribute must be a date after :date.', ['attribute' => 'Start Date', 'date' => '7 April 1986']);
```
which will be return `The Start Date must be a date after 7 April 1986.`

### Blade Templates
In blade templates you can use just `@t()` notation like
```php
@t('Your translatable string')
```
which is equivalent to `{{ t('Your translatable string') }}`

### URL Generation
Also you can use `lang_url()` helper function for appending current lang marker in urls automatically.

```php
$url = lang_url('users'); // which returns /en/users depending on your language (locale)
```

You can force locale and get localized url for current url for example.

```php
$url = lang_url('users', [], null, 'ka'); // which returns /ka/users ignoring current locale
```
or
```php
$url = lang_url('en/users', [], null, 'ka'); // also returns /ka/users
```

Also you use named routes via `lang_route()` function

```php
$url = lang_route('users'); // which returns en.users depending on your language (locale)
```

Also `Request::locale()` always will return current locale.

*Note*: Texts will be selected after firing Laravel's `LocaleUpdated` event. Therefore you should use MultiLang middleware, or manually set locale in the application.

### Text Scopes
If you want group translations by some scope, in package available defining of scopes.
For example to define scope `admin` in application, you should call:

```php
app('multilang')->setScope('admin');
```

before setting the locale.

*Note*: Default scope is `global`

### Import/Export Texts
For versioning texts with source code (git/svn) and easy management, there is possible import texts from yml file and also export in the file.

yml file format is:

```yml
-
  key: 'authorization'
  texts:
    en: 'Authorization'
    ge: 'ავტორიზაცია'
-
  key: 'registration'
  texts:
    en: 'Registration'
    ge: 'რეგისტრაცია'
```

Run commands for possible options and more information:
    
    php artisan help multilang:import
    
    php artisan help multilang:export


## TODO

write more tests

## Troubleshooting

If you like living on the edge, please report any bugs you find on the
[laravel-multilang issues](https://github.com/akalongman/laravel-multilang/issues) page.

## Contributing

Pull requests are welcome.
See [CONTRIBUTING.md](CONTRIBUTING.md) for information.

## License

Please see the [LICENSE](LICENSE.md) included in this repository for a full copy of the MIT license,
which this project is licensed under.

## Credits

- [Avtandil Kikabidze aka LONGMAN](https://github.com/akalongman)

Full credit list in [CREDITS](CREDITS)
