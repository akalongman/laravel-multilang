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

    /**
     * @test
     */
    public function set_get_texts()
    {
        $multilang = $this->getMultilang();
        $multilang->setLocale('ka');

        $texts = [
            'text1'    => 'value1',
            'text2'    => 'value2',
            'te.x-t/3 dsasad sadadas' => 'value3',
        ];

        $multilang->setTexts($texts);

        $this->assertEquals($texts, $multilang->getTexts());
    }



    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function set_empty_locale()
    {
        $multilang = $this->getMultilang();
        $multilang->setLocale(null);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function get_string_without_key()
    {
        $multilang = $this->getMultilang();
        $multilang->setLocale('ka');

        $multilang->get(null);

    }



    /**
     * @test
     */
    public function check_must_load_from_cache()
    {
        $multilang = $this->getMultilang('production');
        $multilang->setLocale('ka');
        $multilang->setCacheName('somestring');

        $texts = [
            'text1'    => 'value1',
            'text2'    => 'value2',
        ];

        $this->app->cache->put($multilang->getCacheName(), $texts, 1440);

        $this->assertTrue($multilang->mustLoadFromCache());

        $this->app->cache->forget($multilang->getCacheName());

    }

    /**
     * @test
     */
    public function store_load_from_cache()
    {
        $multilang = $this->getMultilang('production');
        $multilang->setLocale('ka');

        $this->assertTrue($multilang->mustLoadFromCache());

        $texts = [];
        for ($i = 0; $i <= 10; $i++) {
            $texts['text key '.$i] = 'text value '.$i;
        }

        $this->assertEquals($texts, $multilang->loadTextsFromCache());
    }


    /**
     * @test
     */
    public function set_get_cache_name()
    {
        $multilang = $this->getMultilang('production');
        $multilang->setLocale('ka');
        $multilang->setCacheName('somestring');

        $this->assertEquals($multilang->getConfig('texts_table').'_somestring', $multilang->getCacheName());
    }


    /**
     * @test
     */
    public function check_autosave_allowed()
    {
        $multilang = $this->getMultilang('local');
        $multilang->setLocale('ka');

        $this->assertTrue($multilang->autoSaveIsAllowed());

        $multilang = $this->getMultilang('production');
        $multilang->setLocale('ka');

        $this->assertFalse($multilang->autoSaveIsAllowed());
    }


    /**
     * @test
     */
    public function check_autosave()
    {
        $multilang = $this->getMultilang('local');
        $multilang->setLocale('en');

        $this->assertFalse($multilang->saveTexts());


        $strings = [
            'aaaaa1' => 'aaaaa1',
            'aaaaa2' => 'aaaaa2',
            'aaaaa3' => 'aaaaa3',
        ];
        foreach($strings as $string) {
            $multilang->get($string);
        }

        $this->assertTrue($multilang->saveTexts());

        $multilang = $this->getMultilang('local');
        $multilang->setLocale('en');

        $this->assertEquals($strings, $multilang->getTexts());
    }


    /**
     * @1test
     */
    public function check_table_name()
    {
        $schema = $this->app->db->getSchemaBuilder();

        $table_name = 'texts2';

        $schema->create($table_name, function (Blueprint $table) {
            $table->char('key');
            $table->char('lang', 2);
            $table->text('value')->default('');
            $table->enum('scope', ['admin', 'site', 'global'])->default('global');
            $table->timestamps();
            $table->primary(['key', 'lang', 'scope']);
        });

        $multilang = $this->getMultilang('local', ['texts_table' => $table_name]);
        $multilang->setLocale('ka');

        $this->assertEquals($table_name, $multilang->getTableName(false));

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

        for ($i = 0; $i <= 10; $i++) {
            $this->app->db->table('texts')->insert([
                'key' => 'text key '.$i,
                'lang' => 'ka',
                'value' => 'text value '.$i,
            ]);
        }


        $this->inited = true;
    }

}
