<?php

namespace Tests\Unit;

use Illuminate\Cache\CacheManager as Cache;
use Illuminate\Config\Repository as Config;
use Longman\LaravelMultiLang\MultiLang;

class MultiLangTest extends AbstractTestCase
{
    /**
     * @test
     */
    public function get_locale()
    {
        $multilang = $this->getMultilang();
        $multilang->setLocale('ka');

        $this->assertEquals('ka', $multilang->getLocale());

        $multilang = $this->getMultilang();
        $multilang->setLocale('en');

        $this->assertEquals('en', $multilang->getLocale());
    }

    /**
     * @test
     */
    public function get_url()
    {
        $multilang = $this->getMultilang();
        $multilang->setLocale('ka');


        $this->assertEquals('ka/users', $multilang->getUrl('users'));
    }

    /**
     * @test
     */
    public function get_text_value()
    {
        $multilang = $this->getMultilang();
        $multilang->setLocale('ka');

        $multilang->setTexts([
            'text1'    => 'value1',
            'text2'    => 'value2',
            'te.x-t/3' => 'value3',
        ]);

        $this->assertEquals('value1', $multilang->get('text1'));

        $this->assertEquals('value3', $multilang->get('tex-t3'));

    }

    /**
     * @test
     */
    public function should_return_default_value()
    {
        $multilang = $this->getMultilang();
        $multilang->setLocale('ka');


        $multilang->setTexts([
            'text1'    => 'value1',
            'text2'    => 'value2',
            'te.x-t/3' => 'value3',
        ]);

        $this->assertEquals('value5', $multilang->get('text5', 'value5'));
    }

    protected function getMultilang()
    {
        $application = $this->app;
        $config      = $this->app->config;
        $cache       = $this->app->cache;
        $database    = $this->app->db;

        return new MultiLang($this->app, $this->app->config, $cache, $database);
    }

}
