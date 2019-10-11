<?php

namespace Pyro\CustomInstall\Installer;


use Anomaly\Streams\Platform\Installer\InstallerCollection;
use Illuminate\Console\Command;
use Illuminate\Contracts\Container\Container;
use Illuminate\Foundation\Bus\Dispatchable;
use Symfony\Component\Debug\Exception\FatalThrowableError;

class RunInstallers
{
    use Dispatchable;

    /**
     * The console command.
     *
     * @var Command
     */
    protected $command;

    /**
     * The installer collection.
     *
     * @var InstallerCollection
     */
    protected $installers;
    /** @var int */
    protected $step;
    /** @var int */
    protected $total;
    /** @var \Pyro\CustomInstall\Installer\InstallerOptions */
    protected $installerOptions;

    /**
     * Create a new RunInstallers instance.
     *
     * @param InstallerCollection $installers
     * @param Command             $command
     */
    public function __construct(InstallerCollection $installers, InstallerOptions $installerOptions, Command $command = null)
    {
        $this->command          = $command;
        $this->installers       = $installers;
        $this->installerOptions = $installerOptions;
    }

    /**
     * handle method
     *
     * @param \Illuminate\Contracts\Container\Container $container
     * @return void
     * @throws \Throwable
     */
    public function handle(Container $container)
    {
        $this->step  = 1;
        $this->total = $this->installers->count();


        /* @var InstallerTask $installer */
        while ($installer = $this->installers->shift()) {
            if ($this->installerOptions->shouldSkipStep($this->step)) {
                if ($this->command) {
                    $this->command->warn("{$this->step}/{$this->total} Skipped " . trans($installer->getMessage()));
                }
                $this->step++;
                continue;
            }
            if ($this->command) {
                $this->command->info("{$this->step}/{$this->total} " . trans($installer->getMessage()));
            }
            try {

                $container->call($installer->getTask());
            }
            catch (FatalThrowableError $e) {
                $this->handleException($e);
            }
            catch (\Throwable $e) {
                $this->handleException($e);
            }
            $this->step++;
        }
    }

    protected function handleException(\Throwable $e)
    {
        if ($this->command) {
            $this->command->error('The installer threw an exception: ' . $e->getMessage());
            if ( ! $this->command->confirm('Should the installer ignore this and continue?', $this->installerOptions->ignore_exceptions)) {
                throw $e;
            }
        } else {
            throw $e;
        }
    }
}
