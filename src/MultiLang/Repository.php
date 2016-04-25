<?php
/*
 * This file is part of the Laravel MultiLang package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Longman\LaravelMultiLang;

use Illuminate\Cache\CacheManager as Cache;
use Illuminate\Database\DatabaseManager as Database;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Longman\LaravelMultiLang\Config;

class Repository
{
    /**
     * Language/Locale.
     *
     * @var string
     */
    protected $lang;

    /**
     * The instance of the cache.
     *
     * @var \Illuminate\Cache\CacheManager
     */
    protected $cache;

    /**
     * Config.
     *
     * @var array
     */
    protected $config;

    /**
     * The instance of the database.
     *
     * @var \Illuminate\Database\DatabaseManager
     */
    protected $db;

    /**
     * Name of the cache.
     *
     * @var string
     */
    protected $cache_name;



    /**
     * Create a new MultiLang instance.
     *
     * @param string                               $environment
     * @param array                                $config
     * @param \Illuminate\Cache\CacheManager       $cache
     * @param \Illuminate\Database\DatabaseManager $db
     */
    public function __construct(Config $config, Cache $cache, Database $db)
    {
        $this->config       = $config;
        $this->cache       = $cache;
        $this->db          = $db;

    }


    public function loadFromDatabase($lang)
    {
        $texts = $lang ? $this->getDb()->table($this->getTableName())
            ->where('lang', $lang)
            ->get(['key', 'value', 'lang', 'scope']) : $this->getDb()->table($this->getTableName())->get(['key', 'value', 'lang', 'scope']);

        $array = [];
        foreach ($texts as $row) {
            $array[$row->key] = $row->value;
        }
        return $array;
    }

    /**
     * Get a database connection instance.
     *
     * @return \Illuminate\Database\Connection
     */
    public function getDb()
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
    public function getCache()
    {
        if ($this->config->get('cache.enabled', true) === false) {
            return null;
        }
        $store = $this->config->get('cache.store', 'default');
        if ($store == 'default') {
            return $this->cache->store();
        }
        return $this->cache->store($store);
    }


    public function save($texts)
    {
        if (empty($texts)) {
            return false;
        }

        $table   = $this->getTableName();
        $locales = $this->getLocales();

        foreach ($texts as $k => $v) {
            foreach ($locales as $lang => $locale_data) {
                $exists = $this->getDb()->table($table)->where([
                    'key'  => $k,
                    'lang' => $lang,
                ])->first();

                if ($exists) {
                    continue;
                }

                $this->getDb()->table($table)->insert([
                    'key'   => $k,
                    'lang'  => $lang,
                    'value' => $v,
                ]);
            }
        }
        return true;
    }


    protected function getTableName()
    {
        $table = $this->config->get('db.texts_table', 'texts');
        return $table;
    }


}
