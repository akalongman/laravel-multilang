<?php
/*
 * This file is part of the Laravel MultiLang package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Longman\LaravelMultiLang\Middleware;

use Closure;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;

class MultiLang
{

    public function __construct(Application $app, Redirector $redirector, Request $request)
    {
        $this->app        = $app;
        $this->redirector = $redirector;
        $this->request    = $request;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure                 $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Make sure current locale exists.
        $locale          = $request->segment(1);
        $fallback_locale = $this->app->config->get('app.fallback_locale');

        if (strlen($locale) == 2) {
            $locales = $this->app->config->get('multilang.locales');

            if (!isset($locales[$locale])) {
                $segments    = $request->segments();
                $segments[0] = $fallback_locale;
                $url         = implode('/', $segments);
                if ($query_string = $request->server->get('QUERY_STRING')) {
                    $url .= '?' . $query_string;
                }

                return $this->redirector->to($url);
            }
        } else {
            $segments = $request->segments();
            $url      = $fallback_locale . '/' . implode('/', $segments);
            if ($query_string = $request->server->get('QUERY_STRING')) {
                $url .= '?' . $query_string;
            }

            return $this->redirector->to($url);
        }

        $this->app->setLocale($locale);

        return $next($request);
    }
}
