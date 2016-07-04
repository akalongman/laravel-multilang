# Laravel 5.x MultiLanguage

[![Build Status](https://img.shields.io/travis/akalongman/laravel-multilang/master.svg?style=flat-square)](https://travis-ci.org/akalongman/laravel-multilang)
[![Code Coverage](https://img.shields.io/scrutinizer/coverage/g/akalongman/laravel-multilang.svg?style=flat-square)](https://scrutinizer-ci.com/g/akalongman/laravel-multilang/?branch=master)
[![Code Quality](https://img.shields.io/scrutinizer/g/akalongman/laravel-multilang.svg?style=flat-square)](https://scrutinizer-ci.com/g/akalongman/laravel-multilang/?branch=master)
[![Latest Stable Version](https://img.shields.io/github/release/akalongman/laravel-multilang.svg?style=flat-square)](https://github.com/akalongman/laravel-multilang/releases)
[![Total Downloads](https://img.shields.io/packagist/dt/Longman/laravel-multilang.svg)](https://packagist.org/packages/longman/laravel-multilang)
[![Downloads Month](https://img.shields.io/packagist/dm/Longman/laravel-multilang.svg)](https://packagist.org/packages/longman/laravel-multilang)
[![License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)

This is a very useful package to integrate multi language (multi locale) functionality in Laravel 5.x.
It includes a ServiceProvider to register the multilang and Middleware for modification routes.

This package uses database for storing translations (also it caches data on production environment for improving performance)
Also package automatically adds in database missing keys (on the local environment only).

## Table of Contents
- [Installation](#installation)
- [Usage](#usage)
- [TODO](#todo)
- [Troubleshooting](#troubleshooting)
- [Contributing](#contributing)
- [License](#license)
- [Credits](#credits)


## Installation

Install this package through [Composer](https://getcomposer.org/).

Edit your project's `composer.json` file to require `longman/laravel-multilang`

Create *composer.json* file:
```js
{
    "name": "yourproject/yourproject",
    "type": "project",
    "require": {
        "longman/laravel-multilang": "dev_master"
    }
}
```
And run composer update

**Or** run a command in your command line:

```
composer require longman/laravel-multilang
```

After updating composer, add the MultiLangServiceProvider to the providers array in config/app.php

```php
Longman\LaravelMultiLang\MultiLangServiceProvider::class,
```

And add facade to the alias array in config/app.php
```php
'MultiLang' => Longman\LaravelMultiLang\Facades\MultiLang::class,
```

Copy the package config to your local config with the publish command:

```
php artisan vendor:publish --provider="Longman\LaravelMultiLang\MultiLangServiceProvider"
```

After run multilang migration command

```
php artisan multilang:migration
```

Its creates multilang migration file in your database/migrations folder. After you can run

```
php artisan migrate
```

Also if you want automatically change locale depending on url (like site.com/en/your-routes)
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

If you want managing texts, add in routes file:
```php
MultiLang::manageTextsRoutes();
```


Or if you want only translating strings without modification urls and routes, you must manually set locale in your app like:
```php
App::setLocale('en');
```


## Usage

In application you can use t() function like:

```php
$string = t('Your translatable string');
```
or simple use
```php
@t('Your translatable string')
```
in blade templates, which is equivalent to ```{{ t('Your translatable string') }}```

Also you can use lang_url() helper function for appending language in urls automatically.

```php
$url = lang_url('users'); // which returns /en/users depending on your language (locale)
```

Note: Texts will be selected after firing Laravel's RouteMatched event. Therefore texts unavailable on artisan commands



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
