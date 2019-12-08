<?php

namespace Pyro\CustomInstall\Console;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;

class UninstallCommand extends Command
{
    use ConfirmableTrait;

    protected $signature = 'uninstall';

    protected $description = 'Uninstall the application';

    public function handle()
    {
        if ( ! $this->confirmToProceed()) {
            return;
        }
        $this->info('Setting INSTALLED to false');
        $this->call('env:set', [ 'line' => 'INSTALLED=false' ]);


        $db     = $this->getLaravel()->make('db');
        $schema = $db->getDoctrineSchemaManager();
        foreach ($schema->listViews() as $view) {
            $this->line('Dropping view: ' . $view->getName());
            $schema->dropView($view->getName());
        }
        foreach ($schema->listTables() as $table) {
            $this->line('Dropping table: ' . $table->getName());
            $schema->dropTable($table->getName());
        }
        $this->info('Truncated database');
        $this->info('Application uninstalled');
    }
}
