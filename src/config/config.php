<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Available locales/languages
    |--------------------------------------------------------------------------
    |
    | Available locales for routing
    |
     */
    'locales'          => [
        'en' => [
            'name'        => 'English',
            'native_name' => 'English',
            'flag'        => 'gb.svg',
            'locale'      => 'en',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Fallback locale/language
    |--------------------------------------------------------------------------
    |
    | Fallback locale for routing
    |
     */
    'default_locale'   => 'en',

    /*
    |--------------------------------------------------------------------------
    | Exclude segments from redirect
    |--------------------------------------------------------------------------
    |
    | Exclude segments from redirects in middleware
    |
     */
    'exclude_segments' => [

    ],

    /*
    |--------------------------------------------------------------------------
    | Route Configuration
    |--------------------------------------------------------------------------
    |
    | Basic route
    |
     */
    'text-route'       => [
        'route'      => 'texts',
        'controller' => '\Longman\LaravelMultiLang\Controllers\TextsController',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Cache parameters
    |
     */
    'cache'            => [
        'enabled'  => true,
        'store'    => env('CACHE_DRIVER', 'default'),
        'lifetime' => 1440,
    ],

    /*
    |--------------------------------------------------------------------------
    | DB Configuration
    |--------------------------------------------------------------------------
    |
    | DB parameters
    |
     */
    'db'               => [
        'autosave'    => true, // Autosave missing texts in database. Only when environment is local
        'connection'  => env('DB_CONNECTION', 'default'),
        'texts_table' => 'texts',
    ],

];
