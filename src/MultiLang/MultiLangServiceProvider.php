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

use Illuminate\Support\ServiceProvider;
use Longman\LaravelMultiLang\Console\MigrationCommand;
use Longman\LaravelMultiLang\Console\TextsCommand;
use Blade;

class MultiLangServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;


    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {

        // Publish config files
        $configPath = __DIR__ . '/../config/config.php';
        $this->publishes([$configPath => $this->getConfigPath()], 'config');

        // Register commands
        $this->commands(['command.multilang.migration', 'command.multilang.texts']);

        // Register blade directives
        Blade::directive('t', function ($expression) {

            if (strpos($expression, ',') !== false) {
                list($key, $default) = explode(',', str_replace(['(', ')'], '', $expression));
            } else {
                $key = str_replace(['(', ')'], '', $expression);
                $default = 'null';
            }

            return "<?php echo e(t({$key}, {$default})); ?>";
        });

    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $configPath = __DIR__ . '/../config/config.php';
        $this->mergeConfigFrom($configPath, 'debugbar');

        $this->app->singleton('multilang', function ($app) {
            $locale = $app->getLocale();
            $multilang = new \Longman\LaravelMultiLang\MultiLang(
                $app,
                $app['config'],
                $app['cache'],
                $app['db']
            );

            $multilang->setLocale($locale);
            return $multilang;
        });

        $this->app->alias('multilang', 'Longman\LaravelMultiLang\MultiLang');

        $this->app['command.multilang.migration'] = $this->app->share(
            function ($app) {
                return new MigrationCommand();
            }
        );

        $this->app['command.multilang.texts'] = $this->app->share(
            function ($app) {
                return new TextsCommand();
            }
        );

        $this->commands(['command.multilang.migration', 'command.multilang.texts']);

    }

    /**
     * Publish the config file
     *
     * @param  string $configPath
     */
    protected function publishConfig($configPath)
    {
        $this->publishes([$configPath => config_path('debugbar.php')], 'config');
    }

    /**
     * Get the config path
     *
     * @return string
     */
    protected function getConfigPath()
    {
        return config_path('multilang.php');
    }

    /**
     * Register the Debugbar Middleware
     *
     * @param  string $middleware
     */
    protected function registerMiddleware($middleware)
    {
        $kernel = $this->app['Illuminate\Contracts\Http\Kernel'];
        $kernel->prependMiddleware($middleware);
    }


    /**
     * Register the blade directives
     *
     * @return void
     */
    protected function registerBladeDirectives()
    {
        \Blade::directive('t', function ($expression) {
            return "<?php echo t({$expression}); ?>";
        });
    }



    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'multilang', 'command.multilang.migration',
            'command.multilang.texts', 'Longman\LaravelMultiLang\MultiLang'
        ];
    }

}
