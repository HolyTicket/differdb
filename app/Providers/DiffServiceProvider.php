<?php

namespace App\Providers;

use App\Services\DependencyService;
use App\Services\SqlGenerationService;
use App\Services\SyncService;
use Illuminate\Support\ServiceProvider;

/**
 * Class DiffServiceProvider: The Diff Service Provider
 * @package App\Providers
 */
class DiffServiceProvider extends ServiceProvider
{
    /**
     *
     */
    public function boot()
    {

    }

    /**
     *
     */
    public function register()
    {
        // Register the Diff Services
        $this->app->bind('sql', function ($app) {
            $m = new SqlGenerationService();
            return $m;
        });
        $this->app->bind('sync', function ($app) {
            $m = new SyncService();
            return $m;
        });
        $this->app->bind('dependency', function ($app) {
            $m = new DependencyService();
            return $m;
        });
    }

}