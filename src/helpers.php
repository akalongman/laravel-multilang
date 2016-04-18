<?php
/*
 * This file is part of the Laravel MultiLang package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

if (!function_exists('lang_url')) {
    /**
     * Generate a url for the application.
     *
     * @param  string                                             $path
     * @param  mixed                                              $parameters
     * @param  bool                                               $secure
     * @return Illuminate\Contracts\Routing\UrlGenerator|string
     */
    function lang_url($path = null, $parameters = [], $secure = null)
    {
        if (is_null($path)) {
            return app(UrlGenerator::class);
        }

        $multilang = app('multilang');

        $path = $multilang->getUrl($path);

        return url($path, $parameters, $secure);
    }
}

if (!function_exists('t')) {
    /**
     * Get translated text
     *
     * @param  mixed   $key
     * @param  string  $default
     * @return mixed
     */
    function t($key, $default = null)
    {
        $text = app('multilang')->get($key, $default);

        return $text;
    }
}
