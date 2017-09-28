<?php

namespace Tests\Unit;

use Illuminate\Http\Request;
use Longman\LaravelMultiLang\MultiLang;

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
    public function get_url_with_forced_locale()
    {
        $config = [
            'locales' => [
                'en' => [
                    'name' => 'English',
                    'native_name' => 'English',
                    'default' => true,
                ],
                'ka' => [
                    'name' => 'Georgian',
                    'native_name' => 'ქართული',
                    'default' => false,
                ],
            ],
        ];
        $multilang = $this->getMultilang('local', $config);
        $multilang->setLocale('ka');

        $this->assertEquals('en/users', $multilang->getUrl('users', 'en'));
        $this->assertEquals('en/users', $multilang->getUrl('ka/users', 'en'));
        // With locale which not exists
        $this->assertEquals('en/ss/users', $multilang->getUrl('ss/users', 'en'));
    }

    /**
     * @test
     */
    public function get_route()
    {
        $multilang = $this->getMultilang();
        $multilang->setLocale('ka');

        $this->assertEquals('ka.users', $multilang->getRoute('users'));
    }

    /**
     * @test
     */
    public function check_get_texts()
    {
        $multilang = $this->getMultilang('testing');
        $texts = [
            'text1' => 'value1',
            'text2' => 'value2',
            'te.x-t/3' => 'value3',
        ];

        $multilang->setLocale('ka', $texts);

        $this->assertEquals($texts, $multilang->getTexts());
    }

    /**
     * @test
     */
    public function get_text_value()
    {
        $multilang = $this->getMultilang();
        $multilang->setLocale('ka');

        $multilang->setTexts([
            'text1' => 'value1',
            'text2' => 'value2',
            'te.x-t/3' => 'value3',
        ]);

        $this->assertEquals('value1', $multilang->get('text1'));

        $this->assertEquals('value3', $multilang->get('te.x-t/3'));
    }

    /**
     * @test
     */
    public function should_return_key_when_no_lang()
    {
        $multilang = $this->getMultilang();

        $this->assertEquals('value5', $multilang->get('value5'));
    }

    /**
     * @test
     */
    public function should_return_key()
    {
        $multilang = $this->getMultilang();
        $multilang->setLocale('ka');

        $multilang->setTexts([
            'text1' => 'value1',
            'text2' => 'value2',
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
            'text1' => 'value1',
            'text2' => 'value2',
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

        $texts = [
            'text1' => 'value1',
            'text2' => 'value2',
        ];

        $this->app->cache->put($multilang->getRepository()->getCacheName('ka'), $texts, 1440);

        $this->assertTrue($multilang->getRepository()->existsInCache('ka'));

        $this->app->cache->forget($multilang->getRepository()->getCacheName('ka'));
    }

    /**
     * @test
     */
    public function store_load_from_cache()
    {
        $multilang = $this->getMultilang('production');
        $multilang->setLocale('ka');

        $this->assertTrue($multilang->getRepository()->existsInCache('ka', 'global'));

        $texts = [];
        for ($i = 0; $i <= 10; $i++) {
            $texts['text key ' . $i] = 'text value ' . $i;
        }

        $this->assertEquals($texts, $multilang->getRepository()->loadFromCache('ka', 'global'));
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
        foreach ($strings as $string) {
            $multilang->get($string);
        }

        $this->assertTrue($multilang->saveTexts());

        $multilang = $this->getMultilang('local');
        $multilang->setLocale('en');

        $this->assertEquals($strings, $multilang->getTexts());
    }

    /**
     * @test
     */
    public function check_autosave_for_all_langs()
    {
        $config = [
            'locales' => [
                'en' => [
                    'name' => 'English',
                    'native_name' => 'English',
                    'default' => true,
                ],
                'ka' => [
                    'name' => 'Georgian',
                    'native_name' => 'ქართული',
                    'default' => false,
                ],
            ],
        ];

        $multilang = $this->getMultilang('local', $config);
        $multilang->setLocale('en');

        $this->assertFalse($multilang->saveTexts());

        $strings = [
            'keyyy1',
            'keyyy2',
            'keyyy3',
        ];
        foreach ($strings as $string) {
            $multilang->get($string);
        }

        $this->assertTrue($multilang->saveTexts());

        $multilang = $this->getMultilang('local');
        $multilang->setLocale('ka');

        $this->assertEquals('ka', $multilang->getLocale('ka'));

        $texts = $multilang->getTexts();

        foreach ($strings as $string) {
            $this->assertArrayHasKey($string, $texts);
        }
    }

    /**
     * @test
     */
    public function check_autosave_if_exists()
    {
        $multilang = $this->getMultilang('local');
        $multilang->setLocale('en');

        $this->assertFalse($multilang->saveTexts());

        $strings = [
            'aaaaa1' => 'aaaaa1',
            'aaaaa2' => 'aaaaa2',
            'aaaaa3' => 'aaaaa3',
        ];
        foreach ($strings as $string) {
            $multilang->get($string);
        }

        $this->assertTrue($multilang->saveTexts());

        $this->assertTrue($multilang->saveTexts());
    }

    /**
     * @test
     */
    public function get_locales()
    {
        $config = [
            'locales' => [
                'en' => [
                    'name' => 'English',
                ],
                'ka' => [
                    'name' => 'Georgian',
                ],
                'az' => [
                    'name' => 'Azerbaijanian',
                ],
            ],
        ];

        $multilang = $this->getMultilang('local', $config);
        $multilang->setLocale('en');

        $this->assertEquals(3, count($multilang->getLocales()));
    }

    /**
     * @test
     */
    public function should_replace_markers()
    {
        $multilang = $this->getMultilang('local');
        $multilang->setLocale('en');

        $texts = [
            'text1' => 'The :attribute must be a date after :date.',
        ];

        $multilang->setTexts($texts);

        $this->assertEquals(
            $multilang->get('text1', ['attribute' => 'Start Date', 'date' => '7 April 1986']),
            'The Start Date must be a date after 7 April 1986.'
        );

    }

    /**
     * @TODO
     */
    public function check_redirect_url()
    {
        $multilang = $this->getMultilang('local');
        $multilang->setLocale('en');

        $this->assertFalse($multilang->saveTexts());

        $strings = [
            'aaaaa1' => 'aaaaa1',
            'aaaaa2' => 'aaaaa2',
            'aaaaa3' => 'aaaaa3',
        ];
        foreach ($strings as $string) {
            $multilang->get($string);
        }

        $this->assertTrue($multilang->saveTexts());

        $this->assertTrue($multilang->saveTexts());
    }

    /** @test */
    public function scope_setter_and_getter()
    {
        $instance = $this->getMultilang();
        $instance->setScope('test-scope');

        $this->assertInstanceOf(MultiLang::class, $instance);
        $this->assertEquals('test-scope', $instance->getScope());
    }

    /** @test */
    public function load_texts_from_cache_repository()
    {
        $multilang = $this->getMultilang('production');
        $multilang->setLocale('ka');
        $texts = $multilang->getRepository()->loadFromDatabase('ka', 'global');
        $multilang->getRepository()->storeInCache('ka', $texts, 'global');

        $this->assertTrue($multilang->getRepository()->existsInCache('ka', 'global'));
        $cacheTexts = $multilang->loadTexts('ka', 'global');
        $this->assertCount(count($texts), $cacheTexts);
    }

    /** @test */
    public function detect_locale_should_return_default_locale_if_non_set()
    {
        $multilang = $this->getMultilang();
        $fallback = $multilang->detectLocale(new Request());

        $this->assertEquals('en', $fallback);
    }

    /** @test */
    public function get_all_texts_should_return_all_from_database()
    {
        $multilang = $this->getMultilang();
        $this->assertCount(11, $multilang->getAllTexts('ka', 'global')['ka']);
    }
}
