<?php

declare(strict_types=1);

namespace Longman\LaravelMultiLang;

use Closure;
use Illuminate\Cache\CacheManager as Cache;
use Illuminate\Database\DatabaseManager as Database;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Translator;

use function array_combine;
use function array_keys;
use function array_map;
use function call_user_func_array;
use function implode;
use function is_null;
use function ltrim;
use function mb_strlen;
use function mb_substr;
use function str_replace;
use function strlen;

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
    protected $newTexts;

    /**
     * Application scope.
     *
     * @var string
     */
    protected $scope = 'global';

    /**
     * Translator instance
     *
     * @var \Symfony\Component\Translation\Translator
     */
    protected $translator;

    /**
     * Create a new MultiLang instance.
     *
     * @param string $environment
     * @param array $config
     * @param \Illuminate\Cache\CacheManager $cache
     * @param \Illuminate\Database\DatabaseManager $db
     */
    public function __construct(string $environment, array $config, Cache $cache, Database $db)
    {
        $this->environment = $environment;

        $this->setConfig($config);

        $this->setRepository(new Repository($this->config, $cache, $db));
    }

    /**
     * Set multilang config
     *
     * @param array $config
     * @return $this
     */
    public function setConfig(array $config): MultiLang
    {
        $this->config = new Config($config);

        return $this;
    }

    /**
     * Get multilang config
     *
     * @return \Longman\LaravelMultiLang\Config
     */
    public function getConfig(): Config
    {

        return $this->config;
    }

    /**
     * Set repository object
     *
     * @param \Longman\LaravelMultiLang\Repository $repository
     * @return $this
     */
    public function setRepository(Repository $repository): MultiLang
    {
        $this->repository = $repository;

        return $this;
    }

    /**
     * Get repository object
     *
     * @return \Longman\LaravelMultiLang\Repository
     */
    public function getRepository(): Repository
    {
        return $this->repository;
    }

    /**
     * Set application scope
     *
     * @param $scope
     * @return $this
     */
    public function setScope($scope): MultiLang
    {
        $this->scope = $scope;

        return $this;
    }

    /**
     * Get application scope
     *
     * @return string
     */
    public function getScope(): string
    {
        return $this->scope;
    }

    /**
     * Set locale
     *
     * @param  string $lang
     * @return void
     */
    public function setLocale(string $lang)
    {
        if (! $lang) {
            throw new InvalidArgumentException('Locale is empty');
        }
        $this->lang = $lang;
    }

    public function loadTexts(?string $locale = null, ?string $scope = null): array
    {
        if (is_null($locale)) {
            $locale = $this->getLocale();
        }

        if (is_null($scope)) {
            $scope = $this->getScope();
        }

        if ($this->environment !== 'production' || $this->config->get('cache.enabled', true) === false) {
            $texts = $this->repository->loadFromDatabase($locale, $scope);
        } else {
            if ($this->repository->existsInCache($locale, $scope)) {
                $texts = $this->repository->loadFromCache($locale, $scope);
            } else {
                $texts = $this->repository->loadFromDatabase($locale, $scope);
                $this->repository->storeInCache($locale, $texts, $scope);
            }
        }

        $this->createTranslator($locale, $scope, $texts);

        $this->texts = $texts;

        return $texts;
    }

    /**
     * Get translated text
     *
     * @param  string $key
     * @param  array $replacements
     * @return string
     */
    public function get(string $key, array $replacements = []): string
    {
        if (! $this->getConfig()->get('use_texts', true)) {
            throw new InvalidArgumentException('Using texts from database is disabled in config');
        }

        if (empty($key)) {
            throw new InvalidArgumentException('Text key not provided');
        }

        if (! $this->lang) {
            return $key;
        }

        if (is_null($this->texts)) {
            // Load texts from storage
            $this->loadTexts();
        }

        if (! isset($this->texts[$key])) {
            $this->queueToSave($key);
        }

        if (! empty($replacements)) {
            $keys = array_keys($replacements);
            $keys = array_map(static function ($v) {
                return ':' . $v;
            }, $keys);
            $replacements = array_combine($keys, $replacements);
        }

        return $this->translator->trans($key, $replacements, $this->getScope());
    }

    /**
     * Get redirect url in middleware
     *
     * @param \Illuminate\Http\Request $request
     * @return string
     */
    public function getRedirectUrl(Request $request): string
    {
        $excludePatterns = $this->config->get('exclude_segments', []);
        if (! empty($excludePatterns)) {
            if (call_user_func_array([$request, 'is'], $excludePatterns)) {
                return '';
            }
        }

        $locale = $request->segment(1);
        $fallbackLocale = $this->config->get('default_locale', 'en');
        if (! empty($locale) && strlen($locale) === 2) {
            $locales = $this->config->get('locales', []);

            if (! isset($locales[$locale])) {
                $segments = $request->segments();
                $segments[0] = $fallbackLocale;
                $url = implode('/', $segments);
                $queryString = $request->server->get('QUERY_STRING');
                if ($queryString) {
                    $url .= '?' . $queryString;
                }

                return $url;
            }
        } else {
            $segments = $request->segments();
            $url = $fallbackLocale . '/' . implode('/', $segments);
            $queryString = $request->server->get('QUERY_STRING');
            if ($queryString) {
                $url .= '?' . $queryString;
            }

            return $url;
        }

        return '';
    }

    /**
     * Detect locale based on url segment
     *
     * @param \Illuminate\Http\Request $request
     * @return string
     */
    public function detectLocale(Request $request): string
    {
        $locale = $request->segment(1);
        $locales = $this->config->get('locales');

        if (isset($locales[$locale])) {
            return $locales[$locale]['locale'] ?? $locale;
        }

        return (string) $this->config->get('default_locale', 'en');
    }

    /**
     * Wrap routes to available languages group
     *
     * @param \Closure $callback
     * @return void
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
     * Get texts
     *
     * @return array
     */
    public function getTexts(): array
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
    public function getAllTexts(?string $lang = null, ?string $scope = null): array
    {
        return $this->repository->loadAllFromDatabase($lang, $scope);
    }

    /**
     * Set texts manually
     *
     * @param  array $textsArray
     * @return \Longman\LaravelMultiLang\MultiLang
     */
    public function setTexts(array $textsArray): MultiLang
    {
        $texts = [];
        foreach ($textsArray as $key => $value) {
            $texts[$key] = $value;
        }

        $this->texts = $texts;

        $this->createTranslator($this->getLocale(), $this->getScope(), $texts);

        return $this;
    }

    /**
     * Get language prefixed url
     *
     * @param string $path
     * @param string $lang
     * @return string
     */
    public function getUrl(string $path, ?string $lang = null): string
    {
        $locale = $lang ?: $this->getLocale();
        if ($locale) {
            $path = $locale . '/' . $this->removeLocaleFromPath($path);
        }

        return $path;
    }

    /**
     * Get language prefixed route
     *
     * @param string $name
     * @return string
     */
    public function getRoute(string $name): string
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
        if ($this->environment === 'local' && $this->config->get('db.autosave', true)) {
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
        return $this->lang ?? $this->config->get('default_locale');
    }

    /**
     * Get available locales
     *
     * @return array
     */
    public function getLocales(): array
    {
        return (array) $this->config->get('locales');
    }

    /**
     * Save missing texts
     *
     * @return bool
     */
    public function saveTexts(): bool
    {
        if (empty($this->newTexts)) {
            return false;
        }

        return $this->repository->save($this->newTexts, $this->scope);
    }

    protected function createTranslator(string $locale, string $scope, array $texts): Translator
    {
        $this->translator = new Translator($locale);
        $this->translator->addLoader('array', new ArrayLoader());
        $this->translator->addResource('array', $texts, $locale, $scope);

        return $this->translator;
    }

    /**
     * Queue missing texts
     *
     * @param  string $key
     * @return void
     */
    protected function queueToSave(string $key)
    {
        $this->newTexts[$key] = $key;
    }

    /**
     * Remove locale from the path
     *
     * @param string $path
     * @return string
     */
    private function removeLocaleFromPath(string $path): string
    {
        $langPath = $path;

        // Remove domain from path
        $appUrl = config('app.url', '');
        if (! empty($appUrl) && mb_substr($langPath, 0, mb_strlen($appUrl)) === $appUrl) {
            $langPath = ltrim(str_replace($appUrl, '', $langPath), '/');
        }

        $locales = $this->config->get('locales');
        $locale = mb_substr($langPath, 0, 2);
        if (isset($locales[$locale])) {
            return mb_substr($langPath, 3);
        }

        return $path;
    }
}
