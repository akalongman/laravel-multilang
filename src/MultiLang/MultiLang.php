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

use Closure;
use Illuminate\Cache\CacheManager as Cache;
use Illuminate\Database\DatabaseManager as Database;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use InvalidArgumentException;
use Longman\LaravelMultiLang\Config;
use Longman\LaravelMultiLang\Repository;

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
     * Config.
     *
     * @var \Longman\LaravelMultiLang\Config
     */
    protected $config;

    /**
     * Repository
     *
     * @var string
     */
    protected $repository;

    /**
     * Texts.
     *
     * @var array
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

        $this->setRepository(new Repository($this->config, $cache, $db));
    }

    public function setConfig(array $config)
    {
        $this->config = new Config($config);
        return $this;
    }

    public function setRepository(Repository $repository)
    {
        $this->repository = $repository;
        return $this;
    }

    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * Set locale and load texts
     *
     * @param  string $lang
     * @param  array  $texts
     * @return void
     */
    public function setLocale($lang, array $texts = null)
    {
        if (!$lang) {
            throw new InvalidArgumentException('Locale is empty');
        }
        $this->lang = $lang;

        if (!is_array($texts)) {
            $texts = $this->loadTexts($this->getLocale());
        }

        $this->texts = $texts;
    }

    /**
     * Load texts
     *
     * @param  string  $lang
     * @return array
     */
    public function loadTexts($lang)
    {
        if ($this->environment != 'production' || $this->config->get('cache.enabled', true) === false) {
            $texts = $this->repository->loadFromDatabase($lang);
            return $texts;
        }

        if ($this->repository->existsInCache($lang)) {
            $texts = $this->repository->loadFromCache($lang);
        } else {
            $texts = $this->repository->loadFromDatabase($lang);
            $this->repository->storeInCache($lang, $texts);
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

        if (!isset($this->texts[$key])) {
            $this->queueToSave($key);
            return $key;
        }

        $text = $this->texts[$key];

        return $text;
    }

    /**
     * Get texts
     *
     * @return array
     */
    public function getRedirectUrl(Request $request)
    {
        $locale           = $request->segment(1);
        $fallback_locale  = $this->config->get('default_locale', 'en');
        $exclude_segments = $this->config->get('exclude_segments', []);
        if (in_array($locale, $exclude_segments)) {
            return null;
        }

        if (strlen($locale) == 2) {
            $locales = $this->config->get('locales', []);

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
        $locales = $this->config->get('locales');

        if (isset($locales[$locale])) {
            return isset($locales[$locale]['locale']) ? $locales[$locale]['locale'] : $locale;
        }

        return $this->config->get('default_locale', 'en');
    }

    public function routeGroup(Closure $callback)
    {
        $router = app('router');

        $locales = $this->config->get('locales', []);

        foreach ($locales as $locale => $val) {
            $router->group([
                'prefix' => $locale,
                'as'     => $locale.'.',
            ], $callback);
        }

    }

    public function manageTextsRoutes()
    {
        $router = app('router');
        $route = $this->config->get('text-route.route', 'texts');
        $controller = $this->config->get('text-route.controller', '\Longman\LaravelMultiLang\Controllers\TextsController');

        $router->get(
            $route,
            ['uses' => $controller . '@index']
        );
        $router->post(
            $route,
            ['uses' => $controller . '@save']
        );
    }

    /**
     * Get texts
     *
     * @return array
     */
    public function getTexts()
    {

        return $this->texts;
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

        $this->texts = $texts;

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
        if ($this->environment == 'local' && $this->config->get('db.autosave', true)) {
            return true;
        }
        return false;
    }

    public function getLocale()
    {
        return $this->lang;
    }

    public function getLocales()
    {
        return $this->config->get('locales');
    }

    public function saveTexts()
    {
        if (empty($this->new_texts)) {
            return false;
        }

        $this->repository->save($this->new_texts);
        return true;
    }
}
