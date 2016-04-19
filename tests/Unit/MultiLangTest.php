<?php

namespace Tests\Unit;

use Illuminate\Cache\CacheManager as Cache;
use Illuminate\Config\Repository as Config;
use Longman\LaravelMultiLang\MultiLang;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class MultiLangTest extends AbstractTestCase
{
    protected $inited;

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
    public function check_get_config()
    {
        $multilang = $this->getMultilang('testing', ['cache' => false]);
        $multilang->setLocale('ka');

        $this->assertEquals(false, $multilang->getConfig('cache'));
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

        $this->assertEquals('value3', $multilang->get('te.x-t/3'));

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

        $this->assertEquals('value5', $multilang->get('value5'));
    }

    protected function getMultilang($env = 'testing', $config = [])
    {
        $cache       = $this->app->cache;
        $database    = $this->app->db;

        $this->createTable();

        $multilang = new MultiLang($env, $config, $cache, $database);

        return $multilang;
    }

    protected function createTable()
    {
        if ($this->inited) {
            return true;
        }


        $schema = $this->app->db->getSchemaBuilder();

        $schema->create('texts', function (Blueprint $table) {
            $table->char('key');
            $table->char('lang', 2);
            $table->text('value')->default('');
            $table->enum('scope', ['admin', 'site', 'global'])->default('global');
            $table->timestamps();
            $table->primary(['key', 'lang', 'scope']);
        });
        $this->inited = true;
    }

}
