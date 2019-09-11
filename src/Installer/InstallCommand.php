<?php namespace Pyradic\CustomInstall\Installer;

use Laradic\Support\FS;
use Laradic\Support\Wrap;
use Illuminate\Console\Command;
use Symfony\Component\Yaml\Yaml;
use Pyradic\CustomInstall\Command\GetPlatformRc;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Foundation\Bus\DispatchesJobs;
use EddIriarte\Console\Helpers\SelectionHelper;
use Anomaly\Streams\Platform\Addon\AddonManager;
use Anomaly\Streams\Platform\Support\Collection;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Anomaly\Streams\Platform\Addon\Module\ModuleCollection;
use Anomaly\Streams\Platform\Addon\Extension\ExtensionCollection;
use Anomaly\Streams\Platform\Installer\Console\Command\SetAdminData;
use Anomaly\Streams\Platform\Installer\Console\Command\SetOtherData;
use Anomaly\Streams\Platform\Application\Command\WriteEnvironmentFile;
use Anomaly\Streams\Platform\Installer\Console\Command\SetStreamsData;
use Anomaly\Streams\Platform\Installer\Console\Command\SetDatabaseData;
use Anomaly\Streams\Platform\Installer\Console\Command\SetApplicationData;

class InstallCommand extends Command
{
    use DispatchesJobs;

    protected $signature = 'install 
                                    {method=install : method name}
                                    {--ready : Indicates that the installer should use an existing .env file.}
    ';

    /** @var \Pyradic\CustomInstall\PlatformRc */
    protected $rc;

    /** @var \Pyradic\CustomInstall\Installer\InstallerOptions */
    protected $installerOptions;

    public function getInstallerOptions()
    {
        if ($this->installerOptions === null) {
            $this->installerOptions = new InstallerOptions();
        }
        return $this->installerOptions;
    }

    public function handle(Dispatcher $events, AddonManager $manager)
    {
        $this->rc = $this->dispatchNow(new GetPlatformRc());
        $this->getHelperSet()->set(new SelectionHelper($this->input, $this->output));
        $method = $this->argument('method');
        $this->laravel->call([ $this, $method ]);
    }

    public function ignore(ModuleCollection $modules, ExtensionCollection $extensions)
    {
        Wrap::dot([])->contains();
//        Yaml::parseFile()
        Yaml::dump([
            'asdf' => 'sad',
            'rew' => [
                '34','efg'
            ],
            'bool' =>true
        ]);
        $filePath = base_path('.platformrc');
        if (FS::exists($filePath) && $this->confirm('A .platformrc file already exists in your project root. Do you wish to override it?', false)) {
            $backupFilePath = $filePath . '.bak';
            if (FS::exists($backupFilePath)) {
                FS::delete($backupFilePath);
                $this->line('Removed old backup file: ' . $backupFilePath);
            }
            FS::copy($filePath, $filePath . '.bak');
            $this->line('Backed up current .platformrc to: ' . $backupFilePath);
            FS::delete($filePath);
        }
        $data = [ 'ignore' => [] ];
        if ($this->confirm('Do you wish to add any <fg=blue>modules</> to the ignore?')) {
            $ignoreModules    = $this->select('Select <fg=blue>modules</> to add to the ignore', $modules->toBase()->map->getNamespace()->toArray());
            $data[ 'ignore' ] = array_merge($data[ 'ignore' ], $ignoreModules);
        }
        if ($this->confirm('Do you wish to add any <fg=green>extensions</> to the ignore?')) {
            $ignoreExtensions = $this->select('Select <fg=green>extensions</> to add to the ignore', $extensions->toBase()->map->getNamespace()->toArray());
            $data[ 'ignore' ] = array_merge($data[ 'ignore' ], $ignoreExtensions);
        }

        $json = json_encode($data, null, 4);
        FS::put($filePath, $json);
        $this->line('Alll done');
        return;
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
        $installer->run($this);
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
