<?php
declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Cache\CacheManager as Cache;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager as Database;

/**
 * This is the service provider test class.
 *
 */
class ServiceProviderTest extends AbstractTestCase
{
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
    public function events_is_injectable()
    {
        $this->assertIsInjectable(Dispatcher::class);
    }

    /**
     * @test
     */
    public function provides()
    {
        $this->testProvides();
    }
}
