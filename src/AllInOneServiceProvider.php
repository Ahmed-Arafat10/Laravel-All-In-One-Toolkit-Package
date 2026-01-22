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
        $this->app['router']->aliasMiddleware('jwt', JwtMiddleware::class);
    }
    public function register(): void
    {
        // bindings, config merge, singletons
    }
}
