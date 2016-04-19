<?php

namespace Tests\Unit;

use GrahamCampbell\TestBenchCore\ServiceProviderTrait;
use Illuminate\Cache\CacheManager as Cache;
use Illuminate\Database\DatabaseManager as Database;

/**
 * This is the service provider test class.
 *
 */
class ServiceProviderTest extends AbstractTestCase
{
    use ServiceProviderTrait;

    /**
     * @test
     */
    public function cache_is_injectable()
    {
        $this->assertIsInjectable(Cache::class);
    }

    /**
     * @test
     */
    public function database_is_injectable()
    {
        $this->assertIsInjectable(Database::class);
    }

    /**
     * @test
     */
    public function provides()
    {
        $this->testProvides();
    }



}
