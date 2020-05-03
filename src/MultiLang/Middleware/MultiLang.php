<?php

declare(strict_types=1);

namespace Longman\LaravelMultiLang\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Longman\LaravelMultiLang\MultiLang as MultiLangLib;

use function setlocale;

use const E_ALL;
use const LC_ALL;

class MultiLang
{
    /**
     * Application.
     *
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * Redirector.
     *
     * @var \Illuminate\Routing\Redirector
     */
    protected $redirector;

    /**
     * MultiLang constructor.
     *
     * @param \Illuminate\Foundation\Application $app
     * @param \Illuminate\Routing\Redirector $redirector
     */
    public function __construct(Application $app, Redirector $redirector)
    {
        $this->app = $app;
        $this->redirector = $redirector;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (! $this->app->bound(MultiLangLib::class)) {
            return $next($request);
        }
        $multilang = $this->app->make(MultiLangLib::class);

        $url = $multilang->getRedirectUrl($request);

        if (! empty($url)) {
            if ($request->expectsJson()) {
                return response('Not found', 404);
            } else {
                return $this->redirector->to($url);
            }
        }

        $locale = $multilang->detectLocale($request);

        $this->app->setLocale($locale);

        if ($multilang->getConfig()->get('set_carbon_locale')) {
            Carbon::setLocale($locale);
        }

        if ($multilang->getConfig()->get('set_system_locale')) {
            $locales = $multilang->getLocales();
            if (! empty($locales[$locale]['full_locale'])) {
                $lc = $multilang->getConfig()->get('system_locale_lc', E_ALL);

                setlocale($lc, $locales[$locale]['full_locale']);
            }
        }

        return $next($request);
    }
}
