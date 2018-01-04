<?php
/*
 * This file is part of the Laravel MultiLang package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Longman\LaravelMultiLang;

use Carbon\Carbon;
use Illuminate\Cache\CacheManager as Cache;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager as Database;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

class Repository
{

    /**
     * The instance of the config.
     *
     * @var \Longman\LaravelMultiLang\Config
     */
    protected $config;

    /**
     * The instance of the cache.
     *
     * @var \Illuminate\Cache\CacheManager
     */
    protected $cache;

    /**
     * The instance of the database.
     *
     * @var \Illuminate\Database\DatabaseManager
     */
    protected $db;

    /**
     * Create a new MultiLang instance.
     *
     * @param \Longman\LaravelMultiLang\Config $config
     * @param \Illuminate\Cache\CacheManager $cache
     * @param \Illuminate\Database\DatabaseManager $db
     */
    public function __construct(Config $config, Cache $cache, Database $db)
    {
        $this->config = $config;
        $this->cache = $cache;
        $this->db = $db;
    }

    /**
     * Get cache key name based on lang and scope
     *
     * @param string $lang
     * @param string $scope
     * @return string
     */
    public function getCacheName(string $lang, string $scope = null): string
    {
        $key = $this->config->get('db.texts_table', 'texts') . '_' . $lang;
        if (! is_null($scope)) {
            $key .= '_' . $scope;
        }

        return $key;
    }

    /**
     * Load texts from database storage
     *
     * @param string $lang
     * @param string $scope
     * @return array
     */
    public function loadFromDatabase(string $lang, string $scope = null): array
    {
        $query = $this->getDb()->table($this->getTableName())
            ->where('lang', $lang);

        if (! is_null($scope) && $scope !== 'global') {
            $query = $query->whereNested(function ($query) use ($scope) {
                $query->where('scope', 'global');
                $query->orWhere('scope', $scope);
            });
        } else {
            $query = $query->where('scope', 'global');
        }

        $texts = $query->get(['key', 'value', 'lang', 'scope']);

        $array = [];
        foreach ($texts as $row) {
            $array[$row->key] = $row->value;
        }

        return $array;
    }

    /**
     * Load all texts from database storage
     *
     * @param string $lang
     * @param string $scope
     * @return array
     */
    public function loadAllFromDatabase(string $lang = null, string $scope = null): array
    {
        $query = $this->getDb()->table($this->getTableName());

        if (! is_null($lang)) {
            $query = $query->where('lang', $lang);
        }

        if (! is_null($scope)) {
            $query = $query->whereNested(function ($query) use ($scope) {
                $query->where('scope', 'global');
                $query->orWhere('scope', $scope);
            });
        }

        $texts = $query->get();

        $array = [];
        foreach ($texts as $row) {
            $array[$row->lang][$row->key] = $row;
        }

        return $array;
    }

    /**
     * Load texts from cache storage
     *
     * @param string $lang
     * @param string $scope
     * @return array
     */
    public function loadFromCache(string $lang, string $scope = null): array
    {
        $texts = $this->getCache()->get($this->getCacheName($lang, $scope), []);

        return $texts;
    }

    /**
     * Store texts in cache
     *
     * @param string $lang
     * @param array $texts
     * @param string $scope
     * @return $this
     */
    public function storeInCache(string $lang, array $texts, string $scope = null): Repository
    {
        $this->getCache()->put($this->getCacheName($lang, $scope), $texts, $this->config->get('cache.lifetime', 1440));

        return $this;
    }

    /**
     * Check if we must load texts from cache
     *
     * @param string $lang
     * @param string $scope
     * @return bool
     */
    public function existsInCache(string $lang, string $scope = null): bool
    {
        return $this->getCache()->has($this->getCacheName($lang, $scope));
    }

    /**
     * Get a database connection instance.
     *
     * @return \Illuminate\Database\Connection
     */
    protected function getDb(): Connection
    {
        $connection = $this->config->get('db.connection');
        if ($connection == 'default') {
            return $this->db->connection();
        }

        return $this->db->connection($connection);
    }

    /**
     * Get a cache driver instance.
     *
     * @return \Illuminate\Contracts\Cache\Repository
     */
    protected function getCache(): CacheRepository
    {
        $store = $this->config->get('cache.store', 'default');
        if ($store == 'default') {
            return $this->cache->store();
        }

        return $this->cache->store($store);
    }

    /**
     * Save missing texts in database
     *
     * @param array $texts
     * @param string $scope
     * @return bool
     */
    public function save(array $texts, string $scope = null): bool
    {
        if (empty($texts)) {
            return false;
        }

        $table = $this->getTableName();
        $locales = $this->config->get('locales', []);
        if (is_null($scope)) {
            $scope = 'global';
        }

        $now = Carbon::now()->toDateTimeString();
        foreach ($texts as $k => $v) {
            foreach ($locales as $lang => $locale_data) {
                $exists = $this->getDb()
                    ->table($table)
                    ->where([
                        'key'   => $k,
                        'lang'  => $lang,
                        'scope' => $scope,
                    ])->first();

                if ($exists) {
                    continue;
                }

                $this->getDb()
                    ->table($table)
                    ->insert([
                        'key'        => $k,
                        'lang'       => $lang,
                        'scope'      => $scope,
                        'value'      => $v,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
            }
        }

        return true;
    }

    /**
     * Get texts table name
     *
     * @return string
     */
    public function getTableName(): string
    {
        $table = $this->config->get('db.texts_table', 'texts');

        return (string) $table;
    }
}
