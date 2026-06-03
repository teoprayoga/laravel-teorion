<?php

namespace Teoprayoga\Teorion;

use Illuminate\Support\ServiceProvider;
use Teoprayoga\Teorion\Console\MakeQueryFilterCommand;
use Teoprayoga\Teorion\Fingerprint\AlgorithmRegistry;
use Teoprayoga\Teorion\Fingerprint\Sha256Algorithm;
use Teoprayoga\Teorion\Fingerprint\Xxh128Algorithm;
use Teoprayoga\Teorion\Fingerprint\Xxh3Algorithm;

class TeorionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/teorion.php',
            'teorion'
        );

        AlgorithmRegistry::register(new Sha256Algorithm());
        AlgorithmRegistry::register(new Xxh3Algorithm());
        AlgorithmRegistry::register(new Xxh128Algorithm());
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
