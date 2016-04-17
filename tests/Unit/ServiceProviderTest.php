<?php

namespace Tests\Unit;

use GrahamCampbell\TestBenchCore\ServiceProviderTrait;
use Illuminate\Foundation\Application;
use Illuminate\Cache\CacheManager as Cache;
use Illuminate\Database\DatabaseManager as Database;
use Illuminate\Config\Repository as Config;

/**
 * This is the service provider test class.
 *
 */
class ServiceProviderTest extends AbstractTestCase
{
    use ServiceProviderTrait;

    public function testApplicationIsInjectable()
    {
        $this->assertIsInjectable(Application::class);
    }

    public function testCacheIsInjectable()
    {
        $this->assertIsInjectable(Cache::class);
    }

    public function testConfigIsInjectable()
    {
        $this->assertIsInjectable(Config::class);
    }

    public function testDatabaseIsInjectable()
    {
        $this->assertIsInjectable(Database::class);
    }



}
