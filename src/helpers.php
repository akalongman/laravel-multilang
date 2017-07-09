<?php
/*
 * This file is part of the Laravel MultiLang package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

if (! function_exists('lang_url')) {
    /**
     * Generate a url for the application.
     *
     * @param  string $path
     * @param  mixed $parameters
     * @param  bool $secure
     * @param  string $locale
     * @return Illuminate\Contracts\Routing\UrlGenerator|string
     */
    function lang_url($path = null, $parameters = [], $secure = null, $locale = null)
    {
        if (is_null($path)) {
            return app(Illuminate\Contracts\Routing\UrlGenerator::class);
        }

        $multilang = app('multilang');

        $path = $multilang->getUrl($path, $locale);

        return url($path, $parameters, $secure);
    }
}

if (! function_exists('lang_redirect')) {
    /**
     * Get an instance of the redirector.
     *
     * @param  string|null $to
     * @param  int $status
     * @param  array $headers
     * @param  bool $secure
     * @return \Illuminate\Routing\Redirector|\Illuminate\Http\RedirectResponse
     */
    function lang_redirect($to = null, $status = 302, $headers = [], $secure = null)
    {
        if (is_null($to)) {
            return app('redirect');
        }

        $multilang = app('multilang');

        $to = $multilang->getUrl($to);

        return app('redirect')->to($to, $status, $headers, $secure);
    }
}

if (! function_exists('lang_route')) {
    /**
     * Get route by name
     *
     * @param string $name
     * @param array $parameters
     * @param bool $absolute
     * @return string
     */
    function lang_route($name, array $parameters = [], $absolute = true)
    {
        $multilang = app('multilang');

        $name = $multilang->getRoute($name);

        return app('url')->route($name, $parameters, $absolute);
    }
}

if (! function_exists('t')) {
    /**
     * Get translated text
     *
     * @param  string $text
     * @param  array $replace
     * @return string
     */
    function t($text, array $replace = [])
    {
        $text = app('multilang')->get($text, $replace);

        return $text;
    }
}
