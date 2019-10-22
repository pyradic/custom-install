<?php /** @noinspection PhpFullyQualifiedNameUsageInspection */

namespace Pyro\CustomInstall;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Support\ServiceProvider;
use Pyro\CustomInstall\Console\UninstallCommand;
use Pyro\CustomInstall\Installer\InstallerOptions;

class CustomInstallServiceProvider extends ServiceProvider
{
    use DispatchesJobs;

    public function register()
    {
        $this->mergeConfigFrom(dirname(__DIR__) . '/config/custom_install.php', 'custom_install');
        $this->registerInstallOptions();
        $this->registerCommands();
    }

    protected function registerInstallOptions()
    {
        $this->app->singleton('custom_install.options', function () {
            return new InstallerOptions($this->app[ 'config' ][ 'custom_install' ]);
        });
        $this->app->alias('custom_install.options', InstallerOptions::class);
    }

    protected function registerCommands()
    {
        $commands  = [];
        $installed = env('INSTALLED') === true;
        $this->app->singleton($commands[] = 'command.platform.uninstall', UninstallCommand::class);

        $this->app->extend(\Anomaly\Streams\Platform\Installer\Console\Install::class, function ($command) {
            return $this->app->make(Console\InstallCommand::class);
        });

//        $this->app->booted(function () {
//            /** @var \Illuminate\Console\Application $console */
//            $commands = collect($this->app->make('Illuminate\Contracts\Console\Kernel')->all());
//            if (false === $commands->has('install')) {
//                return;
//            }
//            $commands->get('install')->setHidden(true);
//        });


        $this->commands($commands);
    }

    public function boot()
    {
        $this->publishes([ dirname(__DIR__) . '/config/custom_install.php' => config_path('custom_install.php'), ], 'config');
    }
}
