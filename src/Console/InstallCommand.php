<?php namespace Pyro\CustomInstall\Console;

use Anomaly\Streams\Platform\Addon\AddonManager;
use Anomaly\Streams\Platform\Addon\Extension\ExtensionCollection;
use Anomaly\Streams\Platform\Addon\Module\ModuleCollection;
use Anomaly\Streams\Platform\Application\Command\WriteEnvironmentFile;
use Anomaly\Streams\Platform\Installer\Console\Command\SetAdminData;
use Anomaly\Streams\Platform\Installer\Console\Command\SetApplicationData;
use Anomaly\Streams\Platform\Installer\Console\Command\SetDatabaseData;
use Anomaly\Streams\Platform\Installer\Console\Command\SetOtherData;
use Anomaly\Streams\Platform\Installer\Console\Command\SetStreamsData;
use Anomaly\Streams\Platform\Support\Collection;
use Illuminate\Console\Command;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Pyro\CustomInstall\Installer\Installer;
use Pyro\CustomInstall\Installer\InstallerOptions;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class InstallCommand extends Command
{
    use DispatchesJobs;

    protected $name = 'install';

    protected $signature2 = 'install {--ready : Indicates that the installer should use an existing .env file.}
    ';

    protected $description = 'Install PyroCMS';

    /** @var \Pyro\CustomInstall\Installer\InstallerOptions */
    protected $options;

    public function getInstallerOptions()
    {
        if ($this->options === null) {
            $this->options = app(InstallerOptions::class);
        }
        return $this->options;
    }

    public function handle()
    {
        $method = $this->argument('method');
        $this->laravel->call([ $this, $method ]);
    }

    public function list(ModuleCollection $modules, ExtensionCollection $extensions)
    {
        $mc = $modules->count();
        $ec = $extensions->count();

        $this->line("<fg=green;options=bold>Pending installations:</>");
        $this->line("- <fg=blue>{$mc}</> modules");
        $this->line("- <fg=green>{$ec}</> extensions");

        $rows = [];
        $i    = 0;
        foreach ($modules as $namespace => $module) {
            /** @var \Anomaly\Streams\Platform\Addon\Module\Module $module */
            $namespace   = $module->getNamespace();
            $name        = trans($namespace . '::addon.name');
            $description = trans($namespace . '::addon.description');
            $rows[]      = [ $i, "<fg=blue>{$namespace}</>", $name, $description ];
            $i++;
        }
        foreach ($extensions as $namespace => $extension) {
            /** @var \Anomaly\Streams\Platform\Addon\Extension\Extension $extension */
            $namespace   = $extension->getNamespace();
            $name        = trans($namespace . '::addon.name');
            $description = trans($namespace . '::addon.description');
            $rows[]      = [ $i, "<fg=green>{$namespace}</>", $name, $description ];
            $i++;
        }
        $this->table([ '#', 'namespace', 'name', 'description' ], $rows);
    }

    /** @var \Anomaly\Streams\Platform\Installer\InstallerCollection */
    protected $installers;

    /**
     * Execute the console command.
     *
     * @param Dispatcher   $events
     * @param AddonManager $manager
     */
    public function install(Dispatcher $events, AddonManager $manager, Installer $installer)
    {
        $data = new Collection();

        if ( ! $this->option('ready')) {
//            $this->dispatchNow(new ConfirmLicense($this));
            $this->dispatchNow(new SetStreamsData($data));
            $this->dispatchNow(new SetDatabaseData($data, $this));
            $this->dispatchNow(new SetApplicationData($data, $this));
            $this->dispatchNow(new SetAdminData($data, $this));
            $this->dispatchNow(new SetOtherData($data, $this));
            $this->dispatchNow(new WriteEnvironmentFile($data->all()));
        }

        $options = $this->resolveInstallerOptions($installer);
        $this->callOptionsCallback($options->call_before);
        $installer->run($this);
        $this->callOptionsCallback($options->call_after);
    }

    protected function resolveInstallerOptions(Installer $installer)
    {
        $options = $installer->getOptions();
        foreach ($options as $key => $default) {
            $value = $this->option($key);
            if (is_int($default)) {
                $value = (int)$value;
            } elseif (is_bool($default)) {
                $value = (bool)$value;
            }
            $options->put($key, $value);
        }
        return $options;
    }

    protected function callOptionsCallback($callbacks)
    {
        foreach ($callbacks as $call) {
            [$command, $arguments] = $call;
            if (is_int($command)) {
                $command = $arguments;
            }
            $this->call($command, $arguments);
        }
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        $options = [
            [ 'ready', null, InputOption::VALUE_NONE, 'Indicates that the installer should use an existing .env file.' ],
        ];
        foreach ($this->getInstallerOptions()->all() as $key => $value) {
            $type = InputOption::VALUE_OPTIONAL;
            if (is_bool($value)) {
                $type  = InputOption::VALUE_NONE;
                $value = null;
            } elseif (is_array($value)) {
                $type = InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL;
            }
            $options[] = [ $key, null, $type, '', $value ];
        }
        return $options;
    }

    protected function getArguments()
    {
        return [
            [ 'method', InputArgument::OPTIONAL, 'method name', 'install' ],
        ];
    }

}
