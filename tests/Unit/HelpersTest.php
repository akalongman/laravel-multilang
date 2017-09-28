<?php

namespace Tests\Unit;

use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use InvalidArgumentException;
use Longman\LaravelMultiLang\MultiLang;

/**
 * This is the service provider test class.
 */
class HelpersTest extends AbstractTestCase
{

    public function setUp()
    {
        parent::setUp();

        $this->createTable();
    }

    /**
     * @test
     */
    public function t_should_return_valid_translation()
    {
        $multilang = app('multilang');

        $texts = [
            'text1' => 'value1',
            'text2' => 'value2',
            'te.x-t/3' => 'value3',
        ];

        $multilang->setLocale('ka', $texts);

        $this->assertEquals('value1', t('text1'));
    }

    /**
     * @test
     */
    public function lang_url_should_return_valid_url()
    {
        /** @var MultiLang $multilang */
        $multilang = app('multilang');

        $texts = [
            'text1' => 'value1',
            'text2' => 'value2',
            'te.x-t/3' => 'value3',
        ];

        $multilang->setLocale('ka', $texts);

        $this->assertEquals('http://localhost/ka/users/list', lang_url('users/list'));
    }

    /**
     * @test
     */
    public function lang_url_should_return_url_generator_if_path_is_null()
    {
        $this->assertInstanceOf(UrlGenerator::class, lang_url());
    }

    /** @test */
    public function lang_redirect_should_return_redirector_instance_if_path_is_null()
    {
        $this->assertInstanceOf(Redirector::class, lang_redirect());
    }

    /** @test */
    public function lang_redirect_should_return_redirect_response()
    {
        /** @var MultiLang $multilang */
        $multilang = app('multilang');
        $multilang->setLocale('ka');

        $redirect = lang_redirect('path', 302, ['X-header' => 'value']);

        $this->assertInstanceOf(RedirectResponse::class, $redirect);
        $this->assertEquals('http://localhost/ka/path', $redirect->getTargetUrl());
        $this->assertEquals($redirect->headers->get('X-header'), 'value');
        $this->assertEquals($redirect->getStatusCode(), 302);
    }

    /**
     * @test
     * @expectedException InvalidArgumentException
     */
    public function lang_route_should_throw_exception_if_route_is_not_defined()
    {
        lang_route('missing-route');
    }
}
