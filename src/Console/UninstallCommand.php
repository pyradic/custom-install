<?php

namespace Pyro\CustomInstall\Console;

use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\View;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;

class UninstallCommand extends Command
{
    use ConfirmableTrait;

    protected $signature = 'uninstall';

    protected $description = 'Uninstall the application';

    /**
     * @var \Illuminate\Database\DatabaseManager
     */
    protected $db;

    /** @var \Doctrine\DBAL\Schema\AbstractSchemaManager */
    protected $schema;

    public function handle()
    {
        if ( ! $this->confirmToProceed()) {
            return;
        }
        $this->db     = $this->getLaravel()->make('db');
        $this->schema = $schema= $this->db->getDoctrineSchemaManager();

        $this->info('Setting INSTALLED to false');
        $this->call('env:set', [ 'line' => 'INSTALLED=false' ]);

        foreach ($schema->listViews() as $view) {
            $this->dropView($view);
        }

        /** @var Table[] $fktables */
        $fktables = array_filter($schema->listTables(),function(Table $table){
            return count($table->getForeignKeys()) > 0;
        });

        foreach($fktables as $table){
            $this->dropTable($table);
        }

        foreach ($schema->listTables() as $table) {
            $this->dropTable($table);
        }

        $this->info('Truncated database');
        $this->info('Application uninstalled');
    }

    protected function dropView(View $view)
    {
        $this->line('Dropping view: ' . $view->getName());
        $this->schema->dropView($view->getName());

    }

    protected function dropTable(Table $table)
    {
        $this->line('Dropping table: ' . $table->getName());
        $this->schema->dropTable($table->getName());
    }
}
