<?php

namespace Tests\Unit;

use Illuminate\Cache\CacheManager as Cache;
use Illuminate\Database\Schema\Blueprint;
use Longman\LaravelMultiLang\MultiLang;

class RepositoryTest extends AbstractTestCase
{

    public function setUp()
    {
        parent::setUp();

        $this->createTable();
    }

    /**
     * @test
     */
    public function get_from_database()
    {
        $repository = $this->getRepository();

        dump($repository);
        die;

    }




}
