<?php

declare(strict_types=1);

namespace Longman\LaravelMultiLang;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Foundation\Events\LocaleUpdated;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\Compilers\BladeCompiler;
use Longman\LaravelMultiLang\Console\ExportCommand;
use Longman\LaravelMultiLang\Console\ImportCommand;
use Longman\LaravelMultiLang\Console\MigrationCommand;
use Longman\LaravelMultiLang\Console\TextsCommand;

class MultiLangServiceProvider extends ServiceProvider implements DeferrableProvider
{
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
            ],
        );

        // Append the country settings
        $this->mergeConfigFrom(
            __DIR__ . '/../config/config.php',
            'multilang',
        );

        // Register blade directives
        $this->getBlade()->directive('t', static function ($expression) {
            return "<?php echo e(t({$expression})); ?>";
        });

        $this->app['events']->listen(RouteMatched::class, function () {
            $scope = $this->app['config']->get('app.scope');
            if ($scope && $scope !== 'global') {
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
        $this->mergeConfigFrom($configPath, 'multilang');

        $this->app->singleton('multilang', function ($app) {
            $environment = $app->environment();
            $config = $app['config']->get('multilang');

            $multilang = new MultiLang(
                $environment,
                $config,
                $app['cache'],
                $app['db'],
            );

            if ($multilang->autoSaveIsAllowed()) {
                $app->terminating(function () use ($multilang) {
                    $scope = $this->app['config']->get('app.scope');
                    if ($scope && $scope !== 'global') {
                        $multilang->setScope($scope);
                    }

                    return $multilang->saveTexts();
                });
            }

            return $multilang;
        });

        $this->app->alias('multilang', MultiLang::class);

        $this->app->singleton(
            'command.multilang.migration',
            static function () {
                return new MigrationCommand();
            },
        );

        $this->app->singleton(
            'command.multilang.texts',
            static function () {
                return new TextsCommand();
            },
        );

        $this->app->singleton(
            'command.multilang.import',
            static function () {
                return new ImportCommand();
            },
        );

        $this->app->singleton(
            'command.multilang.export',
            static function () {
                return new ExportCommand();
            },
        );

        $this->commands(
            [
                'command.multilang.migration',
                'command.multilang.texts',
                'command.multilang.import',
                'command.multilang.export',
            ],
        );

        $this->app->make('request')->macro('locale', static function () {
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
            MultiLang::class,
            'command.multilang.migration',
            'command.multilang.texts',
            'command.multilang.import',
            'command.multilang.export',
        ];
    }

    private function getBlade(): BladeCompiler
    {
        return $this->app->make('view')->getEngineResolver()->resolve('blade')->getCompiler();
    }
}
