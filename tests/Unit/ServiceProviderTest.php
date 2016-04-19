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

    public function test_cache_is_injectable()
    {
        $this->assertIsInjectable(Cache::class);
    }


    public function test_database_is_injectable()
    {
        $this->assertIsInjectable(Database::class);
    }

    public function test_provides()
    {
        $this->testProvides();
    }



}
