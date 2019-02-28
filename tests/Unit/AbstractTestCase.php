<?php
declare(strict_types=1);

namespace Tests\Unit;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Illuminate\Database\Schema\Blueprint;
use Longman\LaravelMultiLang\MultiLang;
use Longman\LaravelMultiLang\MultiLangServiceProvider;
use Longman\LaravelMultiLang\Repository;
use Longman\LaravelMultiLang\Config;

/**
 * This is the abstract test case class.
 *
 */
abstract class AbstractTestCase extends BaseTestCase
{
    /**
     * Get the service provider class.
     *
     * @param  \Illuminate\Contracts\Foundation\Application $app
     * @return string
     */
    protected function getServiceProviderClass($app)
    {
        return MultiLangServiceProvider::class;
    }

    protected function createTable()
    {
        /** @var \Illuminate\Database\Schema\MySqlBuilder $schema */
        $schema = $this->app['db']->getSchemaBuilder();

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
                'key'   => 'text key ' . $i,
                'lang'  => 'ka',
                'value' => 'text value ' . $i,
            ]);
        }
    }

    protected function getMultilang(string $env = 'testing', array $config = []): MultiLang
    {
        $cache    = $this->app->cache;
        $database = $this->app->db;

        $default_config = include(__DIR__ . '/../../src/config/config.php');
        $config = array_replace_recursive($default_config, $config);

        $multilang = new MultiLang($env, $config, $cache, $database);

        return $multilang;
    }

    protected function getRepository(array $config = []): Repository
    {
        $cache    = $this->app->cache;
        $database = $this->app->db;

        $default_config = include(__DIR__ . '/../../src/config/config.php');
        $config = array_replace_recursive($default_config, $config);

        $config = $this->getConfig($config);

        $repository = new Repository($config, $cache, $database);

        return $repository;
    }

    protected function getConfig(array $config): Config
    {
        $configObject = new Config($config);
        return $configObject;
    }
}
