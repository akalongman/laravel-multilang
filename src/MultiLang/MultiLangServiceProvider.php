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

use Blade;
use Illuminate\Foundation\Events\LocaleUpdated;
use Illuminate\Http\Request;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\ServiceProvider;
use Longman\LaravelMultiLang\Console\ExportCommand;
use Longman\LaravelMultiLang\Console\ImportCommand;
use Longman\LaravelMultiLang\Console\MigrationCommand;
use Longman\LaravelMultiLang\Console\TextsCommand;

class MultiLangServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Publish config files
        $this->publishes(
            [
                __DIR__ . '/../config/config.php' => config_path('multilang.php'),
                __DIR__ . '/../views'             => base_path('resources/views/vendor/multilang'),
            ]
        );

        // Append the country settings
        $this->mergeConfigFrom(
            __DIR__ . '/../config/config.php',
            'multilang'
        );

        // Register blade directives
        Blade::directive('t', function ($expression) {
            return "<?php echo e(t({$expression})); ?>";
        });

        $this->app['events']->listen(RouteMatched::class, function () {
            $scope = $this->app['config']->get('app.scope');
            if ($scope && $scope != 'global') {
                $this->app['multilang']->setScope($scope);
            }
        });

        $this->app['events']->listen(LocaleUpdated::class, function ($event) {
            $this->app['multilang']->setLocale($event->locale);
        });

        $this->loadViewsFrom(__DIR__ . '/../views', 'multilang');
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
            $environment = $app->environment();
            $config = $app['config']->get('multilang');

            $multilang = new \Longman\LaravelMultiLang\MultiLang(
                $environment,
                $config,
                $app['cache'],
                $app['db']
            );

            if ($multilang->autoSaveIsAllowed()) {
                $app->terminating(function () use ($multilang) {
                    $scope = $this->app['config']->get('app.scope');
                    if ($scope && $scope != 'global') {
                        $multilang->setScope($scope);
                    }

                    return $multilang->saveTexts();
                });
            }

            return $multilang;
        });

        $this->app->alias('multilang', 'Longman\LaravelMultiLang\MultiLang');

        $this->app->singleton(
            'command.multilang.migration',
            function () {
                return new MigrationCommand();
            }
        );

        $this->app->singleton(
            'command.multilang.texts',
            function () {
                return new TextsCommand();
            }
        );

        $this->app->singleton(
            'command.multilang.import',
            function () {
                return new ImportCommand();
            }
        );

        $this->app->singleton(
            'command.multilang.export',
            function () {
                return new ExportCommand();
            }
        );

        $this->commands(
            [
                'command.multilang.migration',
                'command.multilang.texts',
                'command.multilang.import',
                'command.multilang.export',
            ]
        );

        Request::macro('locale', function () {
            return app('multilang')->getLocale();
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
            'multilang',
            'command.multilang.migration',
            'command.multilang.texts',
            'command.multilang.import',
            'command.multilang.export',
            'Longman\LaravelMultiLang\MultiLang',
        ];
    }
}
