# Laravel 5.x MultiLanguage

[![Build Status](https://travis-ci.org/akalongman/laravel-multilang.svg?branch=master)](https://travis-ci.org/akalongman/laravel-multilang)
[![Latest Stable Version](https://img.shields.io/packagist/v/Longman/laravel-multilang.svg)](https://packagist.org/packages/longman/laravel-multilang)
[![Total Downloads](https://img.shields.io/packagist/dt/Longman/laravel-multilang.svg)](https://packagist.org/packages/longman/laravel-multilang)
[![Downloads Month](https://img.shields.io/packagist/dm/Longman/laravel-multilang.svg)](https://packagist.org/packages/longman/laravel-multilang)
[![License](https://img.shields.io/packagist/l/Longman/laravel-multilang.svg)](LICENSE.md)

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

Copy the package config to your local config with the publish command:

```
php artisan vendor:publish --provider="Longman\LaravelMultiLang\MultiLangServiceProvider"
```

After run multilang migration command

```
php artisan multilang:migrate
```

Its creates multilang migration file in your database/migrations folder. After you can run

```
php artisan migrate
```

Also if you want change locale depending on url (like site.com/en/your-routes)
you must add middleware in app/Http/Kernel.php

I suggest add multilang after CheckForMaintenanceMode middleware

```php
protected $middleware = [
    \Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode::class,
    \Longman\LaravelMultiLang\Middleware\MultiLang::class,
];
```

Or if you want only translating strings without modification urls, you must manually set locale in your app like:
```php
App::setLocale('en');
```


## Usage

In application you can use t() function like:

```php
$string = t('string_key', 'default_value');
```
or simple use
```php
@t('string_key', 'default_value')
```
in blade templates, which is equivalent to ```{{ t('string_key', 'default_value') }}```

* default value is optional argument.


Also you can use lang_url() helper function for appending language in urls automatically.

```php
$url = lang_url('users'); // which returns /en/users depending on your language (locale)
```

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
