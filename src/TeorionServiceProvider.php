<?php

namespace Teoprayoga\Teorion;

use Illuminate\Support\ServiceProvider;
use Teoprayoga\Teorion\Console\MakeQueryFilterCommand;

class TeorionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/teorion.php',
            'teorion'
        );
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/teorion.php' => config_path('teorion.php'),
            ], 'teorion-config');

            $this->publishes([
                __DIR__ . '/../stubs/query-filter.stub' => base_path('stubs/query-filter.stub'),
            ], 'teorion-stubs');

            $this->commands([
                MakeQueryFilterCommand::class,
            ]);
        }
    }
}
