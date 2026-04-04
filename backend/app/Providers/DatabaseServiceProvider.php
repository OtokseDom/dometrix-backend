<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\ServiceProvider;

class DatabaseServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register factory namespace
        Factory::guessFactoryNamesUsing(function (string $modelName) {
            $namespace = 'Database\\Factories\\';
            $modelBaseName = class_basename($modelName);
            return $namespace . $modelBaseName . 'Factory';
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
