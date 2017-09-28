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
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
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
     * Config.
     *
     * @var \Longman\LaravelMultiLang\Config
     */
    protected $config;

    /**
     * Repository
     *
     * @var \Longman\LaravelMultiLang\Repository
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
     * Application scope.
     *
     * @var string
     */
    protected $scope = 'global';

    /**
     * Create a new MultiLang instance.
     *
     * @param string $environment
     * @param array $config
     * @param \Illuminate\Cache\CacheManager $cache
     * @param \Illuminate\Database\DatabaseManager $db
     */
    public function __construct($environment, array $config, Cache $cache, Database $db)
    {
        $this->environment = $environment;
        $this->cache = $cache;
        $this->db = $db;

        $this->setConfig($config);

        $this->setRepository(new Repository($this->config, $cache, $db));
    }

    /**
     * Set multilang config
     *
     * @param array $config
     * @return $this
     */
    public function setConfig(array $config)
    {
        $this->config = new Config($config);

        return $this;
    }

    /**
     * Set repository object
     *
     * @param \Longman\LaravelMultiLang\Repository $repository
     * @return $this
     */
    public function setRepository(Repository $repository)
    {
        $this->repository = $repository;

        return $this;
    }

    /**
     * Get repository object
     *
     * @return \Longman\LaravelMultiLang\Repository
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * Set application scope
     *
     * @param $scope
     * @return $this
     */
    public function setScope($scope)
    {
        $this->scope = $scope;

        return $this;
    }

    /**
     * Get application scope
     *
     * @return string
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * Set locale and load texts
     *
     * @param  string $lang
     * @param  array $texts
     * @return void
     */
    public function setLocale($lang, array $texts = null)
    {
        if (! $lang) {
            throw new InvalidArgumentException('Locale is empty');
        }
        $this->lang = $lang;

        if (! is_array($texts)) {
            $texts = $this->loadTexts($this->getLocale(), $this->scope);
        }

        $this->texts = $texts;
    }

    /**
     * Load texts
     *
     * @param  string $lang
     * @param  string $scope
     * @return array
     */
    public function loadTexts($lang, $scope = null)
    {
        if ($this->environment != 'production' || $this->config->get('cache.enabled', true) === false) {
            $texts = $this->repository->loadFromDatabase($lang, $scope);

            return $texts;
        }

        if ($this->repository->existsInCache($lang, $scope)) {
            $texts = $this->repository->loadFromCache($lang, $scope);
        } else {
            $texts = $this->repository->loadFromDatabase($lang, $scope);
            $this->repository->storeInCache($lang, $texts, $scope);
        }

        return $texts;
    }

    /**
     * Get translated text
     *
     * @param  string $key
     * @param  array $replace
     * @return string
     */
    public function get($key, array $replace = [])
    {

        if (empty($key)) {
            throw new InvalidArgumentException('String key not provided');
        }

        if (! $this->lang) {
            return $key;
        }

        if (! isset($this->texts[$key])) {
            $this->queueToSave($key);

            return $this->replaceMarkers($key, $replace);
        }

        $text = $this->texts[$key];

        return $this->replaceMarkers($text, $replace);
    }

    /**
     * Replace markers in text
     *
     * @param  string $text
     * @param  array $replace
     * @return string
     */
    protected function replaceMarkers($text, array $replace = [])
    {
        if (empty($replace)) {
            return $text;
        }

        return $this->makeReplacements($text, $replace);
    }

    /**
     * Make the place-holder replacements on a line.
     *
     * @param  string $text
     * @param  array $replace
     * @return string
     */
    protected function makeReplacements($text, array $replace)
    {
        $replace = $this->sortReplacements($replace);

        foreach ($replace as $key => $value) {
            $text = str_replace(
                [':' . $key, ':' . Str::upper($key), ':' . Str::ucfirst($key)],
                [$value, Str::upper($value), Str::ucfirst($value)],
                $text
            );
        }

        return $text;
    }

    /**
     * Sort the replacements array.
     *
     * @param  array $replace
     * @return \Illuminate\Support\Collection
     */
    protected function sortReplacements(array $replace)
    {
        return (new Collection($replace))->sortBy(function ($value, $key) {
            return mb_strlen($key) * -1;
        });
    }

    /**
     * Get redirect url in middleware
     *
     * @param \Illuminate\Http\Request $request
     * @return null|string
     */
    public function getRedirectUrl(Request $request)
    {
        $locale = $request->segment(1);
        $fallback_locale = $this->config->get('default_locale', 'en');
        $exclude_segments = $this->config->get('exclude_segments', []);
        if (in_array($locale, $exclude_segments)) {
            return null;
        }

        if (strlen($locale) == 2) {
            $locales = $this->config->get('locales', []);

            if (! isset($locales[$locale])) {
                $segments = $request->segments();
                $segments[0] = $fallback_locale;
                $url = implode('/', $segments);
                if ($query_string = $request->server->get('QUERY_STRING')) {
                    $url .= '?' . $query_string;
                }

                return $url;
            }
        } else {
            $segments = $request->segments();
            $url = $fallback_locale . '/' . implode('/', $segments);
            if ($query_string = $request->server->get('QUERY_STRING')) {
                $url .= '?' . $query_string;
            }

            return $url;
        }

        return null;
    }

    /**
     * Detect locale based on url segment
     *
     * @param \Illuminate\Http\Request $request
     * @return string
     */
    public function detectLocale(Request $request)
    {
        $locale = $request->segment(1);
        $locales = $this->config->get('locales');

        if (isset($locales[$locale])) {
            return isset($locales[$locale]['locale']) ? $locales[$locale]['locale'] : $locale;
        }

        return $this->config->get('default_locale', 'en');
    }

    /**
     * Wrap routes to available languages group
     *
     * @param \Closure $callback
     */
    public function routeGroup(Closure $callback)
    {
        $router = app('router');

        $locales = $this->config->get('locales', []);

        foreach ($locales as $locale => $val) {
            $router->group([
                'prefix' => $locale,
                'as'     => $locale . '.',
            ], $callback);
        }
    }

    /**
     *  Manage texts
     */
    public function manageTextsRoutes()
    {
        $router = app('router');
        $route = $this->config->get('text-route.route', 'texts');
        $controller = $this->config->get(
            'text-route.controller',
            '\Longman\LaravelMultiLang\Controllers\TextsController'
        );

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
     * Get all texts
     *
     * @param string $lang
     * @param string $scope
     * @return array
     */
    public function getAllTexts($lang = null, $scope = null)
    {
        return $this->repository->loadAllFromDatabase($lang, $scope);
    }

    /**
     * Set texts manually
     *
     * @param  array $texts_array
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

    /**
     * Get language prefixed url
     *
     * @param string $path
     * @param string $lang
     * @return string
     */
    public function getUrl($path, $lang = null)
    {
        $locale = $lang ? $lang : $this->getLocale();
        if ($locale) {
            $path = $locale . '/' . $this->removeLocaleFromPath($path);
        }

        return $path;
    }

    /**
     * Remove locale from the path
     *
     * @param string $path
     * @return string
     */
    private function removeLocaleFromPath($path)
    {
        $locales = $this->config->get('locales');
        $locale = mb_substr($path, 0, 2);
        if (isset($locales[$locale])) {
            return mb_substr($path, 3);
        }

        return $path;
    }

    /**
     * Get language prefixed route
     *
     * @param string $name
     * @return string
     */
    public function getRoute($name)
    {
        $locale = $this->getLocale();
        if ($locale) {
            $name = $locale . '.' . $name;
        }

        return $name;
    }

    /**
     * Check if autosave allowed
     *
     * @return bool
     */
    public function autoSaveIsAllowed()
    {
        if ($this->environment == 'local' && $this->config->get('db.autosave', true)) {
            return true;
        }

        return false;
    }

    /**
     * Get locale
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->lang;
    }

    /**
     * Get available locales
     *
     * @return array
     */
    public function getLocales()
    {
        return $this->config->get('locales');
    }

    /**
     * Save missing texts
     *
     * @return bool
     */
    public function saveTexts()
    {
        if (empty($this->new_texts)) {
            return false;
        }

        $this->repository->save($this->new_texts, $this->scope);

        return true;
    }
}
