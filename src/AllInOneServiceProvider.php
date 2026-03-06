<?php

namespace AhmedArafat\AllInOne;

use AhmedArafat\AllInOne\Console\DatabaseInitialSeedersCommand;
use AhmedArafat\AllInOne\Console\GitCommand;
use AhmedArafat\AllInOne\Middleware\JwtMiddleware;
use Illuminate\Support\ServiceProvider;


class AllInOneServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                GitCommand::class,
                DatabaseInitialSeedersCommand::class,
            ]);
        }
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/all-in-one.php' =>
                    config_path('all-in-one.php'),
            ], 'all-in-one-config');
        }

        // Register SmartMailSender logging channel
        if (!config()->has('logging.channels.SmartMailSender')) {
            config([
                'logging.channels.SmartMailSender' => [
                    'driver' => 'single',
                    'path' => storage_path('logs/SmartMailSender.log'),
                    'level' => 'debug',
                ],
            ]);
        }

        $this->app['router']->aliasMiddleware('jwt', JwtMiddleware::class);
    }

    public function register(): void
    {
        // bindings, config merge, singletons
        $this->mergeConfigFrom(
            __DIR__ . '/../config/all-in-one.php',
            'all-in-one'
        );
    }
}
