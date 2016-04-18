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

use Illuminate\Contracts\Cache\Factory as CacheContract;
use Illuminate\Contracts\Config\Repository as ConfigContract;
use Illuminate\Contracts\Foundation\Application as ApplicationContract;
use Illuminate\Database\DatabaseManager as DatabaseContract;
use Illuminate\Support\Collection;

class MultiLang
{
    /**
     * Language/Locale.
     *
     * @var string
     */
    protected $lang;

    /**
     * The instance of the laravel app.
     *
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * The instance of the cache.
     *
     * @var \Illuminate\Cache\CacheManager
     */
    protected $cache;

    /**
     * The instance of the config.
     *
     * @var \Illuminate\Config\Repository
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
     * Texts collection.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $texts;

    /**
     * Missing texts.
     *
     * @var array
     */
    protected $new_texts;

    /**
     * Create a new MultiLang instance.
     *
     * @param  \Illuminate\Foundation\Application   $app
     * @param  \Illuminate\Config\Repository        $config
     * @param  \Illuminate\Cache\CacheManager       $cache
     * @param  \Illuminate\Database\DatabaseManager $db
     * @return void
     */
    public function __construct(ApplicationContract $app, ConfigContract $config, CacheContract $cache, DatabaseContract $db)
    {
        $this->app    = $app;
        $this->cache  = $cache;
        $this->config = $config;
        $this->db     = $db;
    }

    /**
     * Set locale and load texts
     *
     * @param  string                         $lang
     * @param  \Illuminate\Support\Collection $text
     * @return void
     */
    public function setLocale($lang, Collection $text = null)
    {
        if (!$lang) {
            $lang = $this->config->get('app.fallback_locale');
        }
        $this->lang = $lang;

        $this->setCacheName();

        $this->texts = $text ? $text : $this->loadTexts($this->getLocale());

        $this->setTerminateCallback();
    }

    /**
     * Load texts
     *
     * @param  string                           $lang
     * @return \Illuminate\Support\Collection
     */
    public function loadTexts($lang = null)
    {
        $cache = $this->config->get('multilang.cache');

        if (!$cache || $this->cache === null) {
            $texts = $this->loadTextsFromDatabase($lang);
            return $texts;
        }

        if ($this->mustLoadFromCache()) {
            $texts = $this->loadTextsFromCache();
        } else {
            $texts = $this->loadTextsFromDatabase($lang);
            if ($cache) {
                $this->storeTextsInCache($texts);
            }
        }

        $texts = new Collection($texts);

        return $texts;
    }

    /**
     * Get translated text
     *
     * @param  string   $key
     * @param  string   $default
     * @return string
     */
    public function get($key, $default = null)
    {

        if (empty($key)) {
            return $default;
        }

        if (!$this->lang) {
            return $default;
        }

        $key = $this->sanitizeKey($key);
        if (!$this->texts->has($key)) {
            $this->queueToRegister($key, $default);
            return $default;
        }

        $text = $this->getText($key);

        return $text;
    }

    /**
     * Get translated text
     *
     * @param  string                           $key
     * @return \Illuminate\Support\Collection
     */
    protected function getText($key)
    {
        $text = $this->texts->get($key);
        return $text;
    }

    /**
     * Get texts
     *
     * @param  string   $lang
     * @return string
     */
    public function getTexts($lang = null)
    {

        return $this->loadTexts($lang);
    }

    /**
     * Set texts manually
     *
     * @param  array                                 $texts_array
     * @return \Longman\LaravelMultiLang\MultiLang
     */
    public function setTexts(array $texts_array)
    {
        $texts = [];
        foreach ($texts_array as $key => $value) {
            $key         = $this->sanitizeKey($key);
            $texts[$key] = $value;
        }

        $this->texts = new Collection($texts);

        return $this;
    }

    /**
     * Queue missing texts
     *
     * @param  string $key
     * @param  string $default
     * @return void
     */
    protected function queueToRegister($key, $default)
    {
        $this->new_texts[$key] = !empty($default) ? $default : $key;
    }

    /**
     * Sanitize key
     *
     * @param  string   $key
     * @return string
     */
    protected function sanitizeKey($key)
    {
        $key = preg_replace('#[^a-z0-9_-]+#is', '', $key);
        $key = strtolower($key);
        return $key;
    }

    /**
     * Check if we must load texts from cache
     *
     * @return bool
     */
    protected function mustLoadFromCache()
    {
        if ($this->app->environment('local')) {
            return false;
        }
        return $this->cache->has($this->cache_name);
    }

    protected function storeTextsInCache(array $texts)
    {
        $cache_lifetime = $this->config->get('multilang.cache_lifetime', 1440);
        $status         = $this->cache->put($this->cache_name, $texts, $cache_lifetime);
        return $status;
    }

    protected function loadTextsFromDatabase($lang)
    {
        $texts = $lang ? $this->db->table($this->getTableName())
            ->where('lang', $lang)
            ->get(['key', 'value', 'lang', 'scope']) : $this->db->table($this->getTableName())->get(['key', 'value', 'lang', 'scope']);

        $array = [];
        foreach ($texts as $row) {
            $array[$row->key] = $row->value;
        }
        return $array;
    }

    protected function loadTextsFromCache()
    {
        $texts = $this->cache->get($this->cache_name);
        return $texts;
    }

    protected function setCacheName()
    {
        $this->cache_name = 'texts.' . $this->lang;
    }

    public function getUrl($path)
    {
        $locale = $this->getLocale();
        if ($locale) {
            $path = $locale . '/' . $path;
        }
        return $path;
    }

    protected function autoSaveIsEnabled()
    {
        if ($this->app->environment('local') && $this->config->get('multilang.autosave') && $this->db !== null) {
            return true;
        }
        return false;
    }

    public function getLocale()
    {
        return $this->lang;
    }

    protected function setTerminateCallback()
    {
        if (!$this->autoSaveIsEnabled()) {
            return false;
        }

        $this->app->terminating(function () {
            return $this->registerTexts();
        });
    }

    protected function registerTexts()
    {
        if (empty($this->new_texts)) {
            return null;
        }

        $ins          = [];
        $placeholders = [];
        $lang         = $this->app->getLocale();
        $i            = 1;
        foreach ($this->new_texts as $k => $v) {
            $key               = $this->sanitizeKey($k);
            $ins['key' . $i]   = $key;
            $ins['lang' . $i]  = $lang;
            $ins['value' . $i] = $v;

            $placeholders[] = '(:key' . $i . ', :lang' . $i . ', :value' . $i . ')';
            $i++;
        }

        $fields = ['key', 'lang', 'value'];

        $placeholders = implode(', ', $placeholders);

        $table = $this->getTableName(true);

        $query = 'INSERT IGNORE
            INTO `' . $table . '` (`' . implode('`, `', $fields) . '`)
            VALUES ' . $placeholders;

        $this->db->insert($query, $ins);
    }

    protected function getTableName($with_prefix = false)
    {
        $table = $this->config->get('multilang.texts_table');
        if ($with_prefix) {
            $prefix = $this->db->getTablePrefix();
            $table  = $prefix . $table;
        }
        return $table;
    }
}
