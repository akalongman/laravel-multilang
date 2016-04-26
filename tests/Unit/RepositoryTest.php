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
        $config = [
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

        $repository->save($texts);

        $this->assertFalse($repository->save(null));
        $this->assertEquals($texts, $repository->loadFromDatabase('en'));
        $this->assertEquals($texts, $repository->loadFromDatabase('az'));

    }

    /**
     * @test
     */
    public function save_and_load_from_cache()
    {
        $config = [
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
