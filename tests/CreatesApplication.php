<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;

trait CreatesApplication
{
    /**
     * Creates the application.
     */
    public function createApplication(): Application
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        if ($app->environment('testing') && config('database.default') === 'sqlite') {
            $database = database_path('testing.sqlite');
            if (! file_exists($database)) {
                touch($database);
            }

            $sqlite = array_merge(config('database.connections.sqlite'), [
                'database' => $database,
            ]);

            config([
                'database.connections.sqlite' => $sqlite,
                'database.connections.mysql' => $sqlite,
                'database.connections.expert' => $sqlite,
                'database.connections.lrd' => $sqlite,
            ]);
        }

        return $app;
    }
}
