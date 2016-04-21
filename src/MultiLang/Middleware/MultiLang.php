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
use Longman\LaravelMultiLang\Multilang as MultilangLib;

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
     * Multilang.
     *
     * @var \Longman\LaravelMultiLang\Multilang
     */
    protected $multilang;


    public function __construct(Application $app, Redirector $redirector, MultilangLib $multilang)
    {
        $this->app        = $app;
        $this->redirector = $redirector;
        $this->multilang  = $multilang;
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
        $url = $this->multilang->getRedirectUrl($request);
        if ($url !== null) {
            return $this->redirector->to($url);
        }

        $this->app->setLocale($this->multilang->getLocale());

        return $next($request);
    }
}
