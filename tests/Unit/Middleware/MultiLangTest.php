<?php

namespace Tests\Unit\Middleware;

use Illuminate\Http\Request;
use Longman\LaravelMultiLang\Middleware\MultiLang as MultiLangMiddleware;
use Longman\LaravelMultiLang\MultiLang;
use Tests\Unit\AbstractTestCase;

class MultiLangTest extends AbstractTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->createTable();
    }

    /**
     * @test
     */
    public function handle_no_redirect()
    {
        $multilang = $this->getMultilang();
        $multilang->setLocale('en');
        $middleware = new MultiLangMiddleware($this->app, $this->app->redirect, $multilang);

        $request = new Request(
            $query = [],
            $request = [],
            $attributes = [],
            $cookies = [],
            $files = [],
            $server = ['REQUEST_URI' => '/en/auth/login'],
            $content = null
        );

        $result = $middleware->handle($request, function () {
            return 'no_redirect';
        });

        $this->assertEquals('no_redirect', $result);
    }

    /**
     * @test
     */
    public function handle_non_exists_language_must_redirect()
    {
        $multilang = $this->getMultilang();
        $multilang->setLocale('en');
        $middleware = new MultiLangMiddleware($this->app, $this->app->redirect, $multilang);

        $request = new Request(
            $query = [],
            $request = [],
            $attributes = [],
            $cookies = [],
            $files = [],
            $server = ['REQUEST_URI' => '/ka/auth/login'],
            $content = null
        );

        $result = $middleware->handle($request, function () {
            return 'no_redirect';
        });

        $location = $result->headers->get('location');

        $this->assertEquals('http://localhost/en/auth/login', $location);
    }

    /**
     * @test
     */
    public function handle_no_language_must_redirect()
    {
        $multilang = $this->getMultilang();
        $multilang->setLocale('en');
        $middleware = new MultiLangMiddleware($this->app, $this->app->redirect, $multilang);

        $request = new Request(
            $query = [],
            $request = [],
            $attributes = [],
            $cookies = [],
            $files = [],
            $server = ['REQUEST_URI' => '/auth/login'],
            $content = null
        );

        $result = $middleware->handle($request, function () {
            return 'no_redirect';
        });

        $location = $result->headers->get('location');

        $this->assertEquals('http://localhost/en/auth/login', $location);
    }

    /**
     * @test
     */
    public function handle_query_string()
    {
        $multilang = $this->getMultilang();
        $multilang->setLocale('en');
        $middleware = new MultiLangMiddleware($this->app, $this->app->redirect, $multilang);

        $QUERY_STRING = 'param1=value1&param2=value2';
        $REQUEST_URI  = '/ka/auth/login?' . $QUERY_STRING;

        $request = new Request(
            $query = [],
            $request = [],
            $attributes = [],
            $cookies = [],
            $files = [],
            $server = ['REQUEST_URI' => $REQUEST_URI, 'QUERY_STRING' => $QUERY_STRING],
            $content = null
        );

        $result = $middleware->handle($request, function () {
            return 'no_redirect';
        });

        $location = $result->headers->get('location');

        $this->assertEquals('http://localhost/en/auth/login?' . $QUERY_STRING, $location);
    }

}
