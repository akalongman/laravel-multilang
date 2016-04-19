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
use Illuminate\Database\DatabaseManager as DatabaseContract;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class MultiLang
{
    /**
     * Language/Locale.
     *
     * @var string
     */
    protected $lang;

    /**
     * System environment
     *
     * @var string
     */
    protected $environment;

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
     * @param  string                               $environment
     * @param  array                                $config
     * @param  \Illuminate\Cache\CacheManager       $cache
     * @param  \Illuminate\Database\DatabaseManager $db
     * @return void
     */
    public function __construct($environment, array $config, CacheContract $cache, DatabaseContract $db)
    {
        $this->environment = $environment;
        $this->cache       = $cache;
        $this->db          = $db;

        $this->setConfig($config);
    }

    public function setConfig(array $config)
    {
        $this->config = [
            'enabled'        => true,
            'locales'        => [
                'en' => [
                    'name'        => 'English',
                    'native_name' => 'English',
                    'default'     => true,
                ],
            ],
            'autosave'       => true,
            'cache'          => true,
            'cache_lifetime' => 1440,
            'texts_table'    => 'texts',
        ];

        foreach ($config as $k => $v) {
            $this->config[$k] = $v;
        }
    }

    public function getConfig($key = null)
    {
        if ($key === null) {
            return $this->config;
        }

        return isset($this->config[$key]) ? $this->config[$key] : null;
    }

    /**
     * Set locale and load texts
     *
     * @param  string                               $lang
     * @param  \Illuminate\Support\Collection|array $text
     * @return void
     */
    public function setLocale($lang, $texts = null)
    {
        if (!$lang) {
            throw new InvalidArgumentException('Locale is empty!');
        }
        $this->lang = $lang;

        $this->setCacheName();

        if (is_array($texts)) {
            $texts = new Collection($texts);
        }

        $this->texts = $texts ? $texts : $this->loadTexts($this->getLocale());
    }

    /**
     * Load texts
     *
     * @param  string                           $lang
     * @return \Illuminate\Support\Collection
     */
    public function loadTexts($lang = null)
    {
        $cache = $this->getConfig('cache');

        if (!$cache || $this->cache === null || $this->environment != 'production') {
            $texts = $this->loadTextsFromDatabase($lang);
            return $texts;
        }

        if ($this->mustLoadFromCache()) {
            $texts = $this->loadTextsFromCache();
        } else {
            $texts = $this->loadTextsFromDatabase($lang);
            $this->storeTextsInCache($texts);
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
    public function get($key)
    {

        if (empty($key)) {
            return null;
        }

        if (!$this->lang) {
            return $key;
        }

        if (!$this->texts->has($key)) {
            $this->queueToSave($key);
            return $key;
        }

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
            $texts[$key] = $value;
        }

        $this->texts = new Collection($texts);

        return $this;
    }

    /**
     * Queue missing texts
     *
     * @param  string $key
     * @return void
     */
    protected function queueToSave($key)
    {
        $this->new_texts[$key] = $key;
    }

    /**
     * Check if we must load texts from cache
     *
     * @return bool
     */
    protected function mustLoadFromCache()
    {
        return $this->cache->has($this->cache_name);
    }

    protected function storeTextsInCache(array $texts)
    {
        $cache_lifetime = $this->getConfig('cache_lifetime', 1440);
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

    public function autoSaveIsAllowed()
    {
        if ($this->environment == 'local' && $this->getConfig('autosave') && $this->db !== null) {
            return true;
        }
        return false;
    }

    public function getLocale()
    {
        return $this->lang;
    }

    public function saveTexts()
    {
        if (empty($this->new_texts)) {
            return null;
        }

        $ins          = [];
        $placeholders = [];
        $lang         = $this->lang;
        $i            = 1;
        foreach ($this->new_texts as $k => $v) {
            $ins['key' . $i]   = $k;
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
        $table = $this->getConfig('texts_table');
        if ($with_prefix) {
            $prefix = $this->db->getTablePrefix();
            $table  = $prefix . $table;
        }
        return $table;
    }
}
