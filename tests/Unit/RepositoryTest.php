<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\DatabaseMigrations;

class RepositoryTest extends AbstractTestCase
{
    use DatabaseMigrations;

    public function setUp()
    {
        parent::setUp();

        $this->createTable();
    }

    /**
     * @test
     */
    public function check_set_get_cache_name()
    {
        $repository = $this->getRepository();

        $this->assertEquals('texts_ka', $repository->getCacheName('ka'));
    }

    /**
     * @test
     */
    public function check_set_get_cache_name_with_scope()
    {
        $repository = $this->getRepository();

        $this->assertEquals('texts_ka_scope_name', $repository->getCacheName('ka', 'scope_name'));
    }

    /**
     * @test
     */
    public function check_set_get_table_name()
    {
        $repository = $this->getRepository(['db' => ['texts_table' => 'mytable']]);

        $this->assertEquals('mytable', $repository->getTableName());
    }

    /**
     * @test
     */
    public function save_and_load_from_database()
    {
        $config     = [
            'locales' => [
                'en' => [
                    'name' => 'English',
                ],
                'az' => [
                    'name' => 'Azerbaijanian',
                ],
            ],
        ];
        $repository = $this->getRepository($config);

        $texts = [
            'text1' => 'value1',
            'text2' => 'value2',
            'text3' => 'value3',
        ];

        $textsScoped = [
            'text1' => 'value1 scoped',
            'text2' => 'value2 scoped',
            'text3' => 'value3 scoped',
        ];

        $repository->save($texts);
        $repository->save($textsScoped, 'scope_name');

        $this->assertFalse($repository->save([]));
        $this->assertEquals($texts, $repository->loadFromDatabase('en'));
        $this->assertEquals($texts, $repository->loadFromDatabase('az'));
        $this->assertEquals($textsScoped, $repository->loadFromDatabase('en', 'scope_name'));
        $this->assertEquals($textsScoped, $repository->loadFromDatabase('az', 'scope_name'));

    }

    /**
     * @test
     */
    public function save_and_load_from_cache()
    {
        $config     = [
            'locales' => [
                'en' => [
                    'name' => 'English',
                ],
                'az' => [
                    'name' => 'Azerbaijanian',
                ],
            ],
        ];
        $repository = $this->getRepository($config);

        $texts = [
            'text1' => 'value1',
            'text2' => 'value2',
            'text3' => 'value3',
        ];

        $repository->storeInCache('en', $texts);
        $repository->storeInCache('az', $texts);

        $this->assertTrue($repository->existsInCache('en'));
        $this->assertTrue($repository->existsInCache('az'));

        $this->assertEquals($texts, $repository->loadFromCache('en'));
        $this->assertEquals($texts, $repository->loadFromCache('az'));

    }

}
