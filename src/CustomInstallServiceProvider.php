<?php

namespace Pyro\CustomInstall;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Support\ServiceProvider;
use Pyro\CustomInstall\Installer\InstallerOptions;

class CustomInstallServiceProvider extends ServiceProvider
{
    use DispatchesJobs;

    public function register()
    {
        $this->mergeConfigFrom(dirname(__DIR__) . '/config/custom_install.php', 'custom_install');

        $this->app->singleton('custom_install.options', function () {
            return new InstallerOptions($this->app[ 'config' ][ 'custom_install' ]);
        });
        $this->app->alias('custom_install.options', InstallerOptions::class);


        $this->app->extend(\Anomaly\Streams\Platform\Installer\Console\Install::class, function ($command) {
            return $this->app->make(Installer\InstallCommand::class);
        });

    }

    public function boot()
    {
        $this->publishes([ dirname(__DIR__) . '/config/custom_install.php' => config_path('custom_install.php'), ], 'config');
    }
}
