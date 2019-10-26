<?php

namespace Pyro\CustomInstall\Installer;

use Anomaly\Streams\Platform\Addon\AddonManager;
use Anomaly\Streams\Platform\Addon\Extension\Extension;
use Anomaly\Streams\Platform\Addon\Extension\ExtensionCollection;
use Anomaly\Streams\Platform\Addon\Module\Module;
use Anomaly\Streams\Platform\Addon\Module\ModuleCollection;
use Anomaly\Streams\Platform\Application\Application;
use Anomaly\Streams\Platform\Application\Command\InitializeApplication;
use Anomaly\Streams\Platform\Application\Command\LoadEnvironmentOverrides;
use Anomaly\Streams\Platform\Application\Command\ReloadEnvironmentFile;
use Anomaly\Streams\Platform\Console\Kernel;
use Anomaly\Streams\Platform\Entry\Command\AutoloadEntryModels;
use Anomaly\Streams\Platform\Installer\Console\Command\ConfigureDatabase;
use Anomaly\Streams\Platform\Installer\Console\Command\LoadApplicationInstallers;
use Anomaly\Streams\Platform\Installer\Console\Command\LoadBaseMigrations;
use Anomaly\Streams\Platform\Installer\Console\Command\LoadBaseSeeders;
use Anomaly\Streams\Platform\Installer\Console\Command\LoadCoreInstallers;
use Anomaly\Streams\Platform\Installer\Console\Command\SetDatabasePrefix;
use Anomaly\Streams\Platform\Installer\InstallerCollection;
use Illuminate\Console\Command;
use Illuminate\Contracts\Container\Container;
use Illuminate\Foundation\Bus\DispatchesJobs;

class Installer
{
    use DispatchesJobs;

    /** @var \Anomaly\Streams\Platform\Installer\InstallerCollection */
    protected $tasks;

    /** @var \Pyro\CustomInstall\Installer\InstallerOptions */
    protected $options;

    /** @var \Anomaly\Streams\Platform\Addon\AddonManager */
    protected $manager;

    /** @var \Illuminate\Contracts\Container\Container */
    private $container;

    public function __construct(InstallerCollection $tasks, InstallerOptions $options, AddonManager $manager, Container $container)
    {
        $this->tasks     = $tasks;
        $this->options   = $options;
        $this->manager   = $manager;
        $this->container = $container;
    }

    public function add(InstallerTask $task)
    {
        $this->tasks->add($task);
        return $this;
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function getTasks()
    {
        return $this->tasks;
    }

    protected function dispatchJob($job)
    {
        if (is_callable($job)) {
            $job = $this->container->call($job);
        }
        $this->dispatchNow($job);
    }

    public function run(Command $command = null)
    {
        $this->options->dispatch_before->call([$this,'dispatchJob]']);
        $this->load();
        $this->dispatchNow(new RunInstallers($this->tasks, $this->options, $command));
        $this->options->dispatch_after->call([$this,'dispatchJob]']);
    }

    protected $loaded;

    protected function load()
    {
        if ($this->loaded) {
            return $this;
        }
        $this->dispatchNow(new ReloadEnvironmentFile());
        $this->dispatchNow(new LoadEnvironmentOverrides());
        $this->dispatchNow(new InitializeApplication());

        $this->dispatchNow(new ConfigureDatabase());
        $this->dispatchNow(new SetDatabasePrefix());

        $tasks = $this->tasks = new InstallerCollection();

        $this->dispatchNow(new LoadCoreInstallers($tasks));
        $this->dispatchNow(new LoadApplicationInstallers($tasks));

        app()->call([ $this, 'loadModuleInstallers' ]);
        app()->call([ $this, 'loadExtensionInstallers' ]);

        $tasks->add(
            new InstallerTask(
                'streams::installer.reloading_application',
                function () {

                    \Artisan::call('env:set', [ 'line' => 'INSTALLED=true' ]);

                    $this->dispatchNow(new ReloadEnvironmentFile());
                    $this->dispatchNow(new AutoloadEntryModels()); // Don't forget!

                    $this->manager->register(true); // Register all of our addons.

                    $this->dispatchNow(new AutoloadEntryModels()); // Yes, again.
                }
            )
        );

        app()->call([ $this, 'loadModuleSeeders' ]);
        app()->call([ $this, 'loadExtensionSeeders' ]);

        if ($this->options->shouldMigrateBase()) {
            $this->dispatchNow(new LoadBaseMigrations($tasks));
        }
        if ($this->options->shouldSeedBase()) {
            $this->dispatchNow(new LoadBaseSeeders($tasks));
        }
        return $this;
    }


    /**
     * loadExtensionSeeders method
     *
     * @param \Anomaly\Streams\Platform\Addon\Extension\ExtensionCollection $extensions
     *
     * @return void
     */
    public function loadExtensionSeeders(ExtensionCollection $extensions): void
    {
        // $this->dispatchNow(new LoadExtensionSeeders($installers));
        /* @var Extension $extension */
        foreach ($extensions as $extension) {
            if ($this->options->shouldSkipInstall($extension) || $this->options->shouldSkipSeed($extension)) {
                continue;
            }
            $this->add(
                InstallerTask::seed(
                    trans('streams::installer.seeding', [ 'seeding' => trans($extension->getName()) ]) . " <comment>{$extension->getNamespace()}</comment>",
                    function (Kernel $console) use ($extension) {
                        $console->call(
                            'db:seed',
                            [
                                '--addon' => $extension->getNamespace(),
                                '--force' => true,
                            ]
                        );
                    }
                )->setAddon($extension)->setCall('db:seed', [ '--addon' => $extension->getNamespace() ])
            );
        }
    }

    /**
     * loadModuleSeeders method
     *
     * @param \Anomaly\Streams\Platform\Addon\Module\ModuleCollection $modules
     *
     * @return void
     */
    public function loadModuleSeeders(ModuleCollection $modules): void
    {
        // $this->dispatchNow(new LoadModuleSeeders($installers));
        /* @var Module $module */
        foreach ($modules as $module) {
            if ($this->options->shouldSkipInstall($module) || $this->options->shouldSkipSeed($module)) {
                continue;
            }
            if ($module->getNamespace() === 'anomaly.module.installer') {
                continue;
            }

            $this->add(
                InstallerTask::seed(
                    trans('streams::installer.seeding', [ 'seeding' => trans($module->getName()) ]) . " <comment>{$module->getNamespace()}</comment>",
                    function (Kernel $console) use ($module) {
                        $console->call(
                            'db:seed',
                            [
                                '--addon' => $module->getNamespace(),
                                '--force' => true,
                            ]
                        );
                    }
                )->setAddon($module)->setCall('db:seed', [ '--addon' => $module->getNamespace() ])
            );
        }
    }

    /**
     * loadExtensionInstallers method
     *
     * @param \Anomaly\Streams\Platform\Addon\Extension\ExtensionCollection $extensions
     * @param \Anomaly\Streams\Platform\Application\Application             $application
     *
     * @return void
     */
    public function loadExtensionInstallers(ExtensionCollection $extensions, Application $application): void
    {
        //        $this->dispatchNow(new LoadExtensionInstallers($installers));
        /* @var Extension $extension */
        foreach ($extensions as $extension) {
            if ($this->options->shouldSkipInstall($extension)) {
                continue;
            }
            $this->add(
                InstallerTask::install(
                    trans('streams::installer.installing', [ 'installing' => trans($extension->getName()) ]),
                    function (Kernel $console) use ($extension, $application) {
                        $console->call(
                            'addon:install',
                            [
                                'addon' => $extension->getNamespace(),
                                '--app' => $application->getReference(),
                            ]
                        );
                    }
                )->setAddon($extension)->setCall('addon:install', [
                    'addon' => $extension->getNamespace(),
                    '--app' => $application->getReference(),
                ])
            );
        }
    }

    /**
     * loadModuleInstallers method
     *
     * @param \Anomaly\Streams\Platform\Addon\Module\ModuleCollection $modules
     * @param \Anomaly\Streams\Platform\Application\Application       $application
     *
     * @return void
     */
    public function loadModuleInstallers(ModuleCollection $modules, Application $application): void
    {
        //        $this->dispatchNow(new LoadModuleInstallers($installers));
        /* @var Module $module */
        foreach ($modules as $module) {
            if ($this->options->shouldSkipInstall($module)) {
                continue;
            }
            if ($module->getNamespace() === 'anomaly.module.installer') {
                continue;
            }

            $this->add(
                InstallerTask::install(
                    trans('streams::installer.installing', [ 'installing' => trans($module->getName()) ]),
                    function (Kernel $console) use ($module, $application) {
                        $console->call(
                            'addon:install',
                            [
                                'addon' => $module->getNamespace(),
                                '--app' => $application->getReference(),
                            ]
                        );
                    }
                )->setAddon($module)->setCall('addon:install', [
                    'addon' => $module->getNamespace(),
                    '--app' => $application->getReference(),
                ])
            );
        }
    }
}
