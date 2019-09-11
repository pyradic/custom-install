<?php

namespace Pyradic\CustomInstall;

use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Pyradic\CustomInstall\Installer\InstallerOptions;

class CustomInstallServiceProvider extends ServiceProvider
{
    use DispatchesJobs;

    public function register()
    {
        $this->mergeConfigFrom(dirname(__DIR__) . '/config/custom_install.php', 'custom_install');
        $this->app->register(\EddIriarte\Console\Providers\SelectServiceProvider::class);

        $this->app->extend(\Anomaly\Streams\Platform\Installer\Console\Install::class, function ($command) {
            return $this->app->make(Installer\InstallCommand::class);
        });

        $this->app->singleton('custom_install.options', function(){
            new InstallerOptions()
        });
    }

    public function boot()
    {
        $this->publishes([ dirname(__DIR__) . '/config/custom_install.php' => config_path('custom_install.php'), ], 'config');
    }
}
