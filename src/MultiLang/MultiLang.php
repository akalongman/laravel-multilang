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
     * @param string                               $environment
     * @param array                                $config
     * @param \Illuminate\Cache\CacheManager       $cache
     * @param \Illuminate\Database\DatabaseManager $db
     */
    public function __construct($environment, array $config, Cache $cache, Database $db)
    {
        $this->environment = $environment;
        $this->cache       = $cache;
        $this->db          = $db;

        $this->setConfig($config);
    }

    public function setConfig(array $config)
    {
        $this->config = $this->getDefaultConfig();

        foreach ($config as $k => $v) {
            $this->config[$k] = $v;
        }
    }

    public function getDefaultConfig()
    {
        $config = [
            'locales'        => [
                'en' => [
                    'name'        => 'English',
                    'native_name' => 'English',
                    'flag'        => 'gb.svg',
                    'locale'      => 'en',
                ],
            ],
            'default_locale' => 'en',
            'autosave'       => true,
            'cache'          => true,
            'cache_lifetime' => 1440,
            'texts_table'    => 'texts',
        ];
        return $config;
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
     * @param  string $lang
     * @param  string $default_lang
     * @param  array  $texts
     * @return void
     */
    public function setLocale($lang, $texts = null)
    {
        if (!$lang) {
            throw new InvalidArgumentException('Locale is empty');
        }
        $this->lang = $lang;

        $this->setCacheName($lang);

        if (is_array($texts)) {
            $texts = new Collection($texts);
        } else {
            $texts = $this->loadTexts($this->getLocale());
        }

        $this->texts = new Collection($texts);
    }

    /**
     * Load texts
     *
     * @param  string  $lang
     * @return array
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

        return $texts;
    }

    /**
     * Get translated text
     *
     * @param  string   $key
     * @return string
     */
    public function get($key)
    {

        if (empty($key)) {
            throw new InvalidArgumentException('String key not provided');
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
     * @return array
     */
    public function getRedirectUrl(Request $request)
    {
        $locale          = $request->segment(1);
        $fallback_locale = $this->getConfig('default_locale');

        if (strlen($locale) == 2) {
            $locales = $this->getConfig('locales');

            if (!isset($locales[$locale])) {
                $segments    = $request->segments();
                $segments[0] = $fallback_locale;
                $url         = implode('/', $segments);
                if ($query_string = $request->server->get('QUERY_STRING')) {
                    $url .= '?' . $query_string;
                }

                return $url;
            }
        } else {
            $segments = $request->segments();
            $url      = $fallback_locale . '/' . implode('/', $segments);
            if ($query_string = $request->server->get('QUERY_STRING')) {
                $url .= '?' . $query_string;
            }
            return $url;
        }

        return null;
    }

    public function detectLocale(Request $request)
    {
        $locale  = $request->segment(1);
        $locales = $this->getConfig('locales');

        if (isset($locales[$locale])) {
            $this->setLocale($locale);
            return isset($locales[$locale]['locale']) ? $locales[$locale]['locale'] : $locale;
        }

        $fallback_locale = $this->getConfig('default_locale');
        $this->setLocale($fallback_locale);
        return $locale;
    }

    /**
     * Get texts
     *
     * @return array
     */
    public function getTexts()
    {

        return $this->texts->toArray();
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
    public function mustLoadFromCache()
    {
        return $this->cache->has($this->getCacheName());
    }

    protected function storeTextsInCache(array $texts)
    {
        $cache_lifetime = $this->getConfig('cache_lifetime');
        $this->cache->put($this->getCacheName(), $texts, $cache_lifetime);
        return $this;
    }

    public function loadTextsFromDatabase($lang)
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

    public function loadTextsFromCache()
    {
        $texts = $this->cache->get($this->getCacheName());

        return $texts;
    }

    public function setCacheName($lang)
    {
        $this->cache_name = $this->getConfig('texts_table') . '_' . $lang;
    }

    public function getCacheName()
    {
        return $this->cache_name;
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
            return false;
        }

        $table   = $this->getTableName();
        $locales = $this->getConfig('locales');
        foreach ($this->new_texts as $k => $v) {
            foreach ($locales as $lang => $locale_data) {
                $exists = $this->db->table($table)->where([
                    'key'  => $k,
                    'lang' => $lang,
                ])->first();

                if ($exists) {
                    continue;
                }

                $this->db->table($table)->insert([
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
        $table = $this->getConfig('texts_table');
        return $table;
    }
}
