<?php

namespace Tests\Unit;

use GrahamCampbell\TestBench\AbstractPackageTestCase;
use Illuminate\Database\Schema\Blueprint;
use Longman\LaravelMultiLang\MultiLang;
use Longman\LaravelMultiLang\MultiLangServiceProvider;

/**
 * This is the abstract test case class.
 *
 */
abstract class AbstractTestCase extends AbstractPackageTestCase
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
                'key'   => 'text key ' . $i,
                'lang'  => 'ka',
                'value' => 'text value ' . $i,
            ]);
        }
    }

    protected function getMultilang($env = 'testing', $config = [])
    {
        $cache    = $this->app->cache;
        $database = $this->app->db;

        $multilang = new MultiLang($env, $config, $cache, $database);

        return $multilang;
    }
}
