<?php

namespace Frontier\Admin\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Translation\FileLoader;

class AdminServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // 1. Override the admin menu configuration dynamically
        config([
            'menu.admin' => array_merge(config('menu.admin', []), [
                [
                    'key'   => 'telescope',
                    'name'  => 'admin::app.components.layouts.sidebar.monitoring',
                    'route' => 'telescope',
                    'sort'  => 10,
                    'icon'  => 'icon-cms',
                ]
            ])
        ]);

        // 2. Register our custom language path as an override path for the translator loader
        $loader = $this->app['translator']->getLoader();
        if ($loader instanceof FileLoader) {
            $loader->addPath(realpath(__DIR__.'/../Resources/lang'));
        }
    }
}
