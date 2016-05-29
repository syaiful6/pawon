<?php

namespace Pawon\Queue\Console\Commands;

use Pawon\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Pawon\Database\MigrationCreator;

class JobTable extends Command
{
    /**
     *
     */
    protected $creator;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'queue:table';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a migration for the queue jobs database table';

    /**
     *
     */
    public function __construct(MigrationCreator $creator)
    {
        parent::__construct();
        $this->creator = $creator;
    }

    /**
     * Execute the console command.
     */
    public function fire()
    {
        $config = $this->container->get('config');
        $table = Arr::get($config, 'queue.connections.database.table');

        $tableClassName = Str::studly($table);

        $fullPath = $this->createBaseMigration($table);

        $stub = str_replace(
            ['{{table}}', '{{tableClassName}}'],
            [$table, $tableClassName],
            file_get_contents(__DIR__.'/stubs/jobs.stub')
        );

        file_put_contents($fullPath, $stub);
        $this->info('Migration created successfully!');
    }

    /**
     * Create a base migration file for the table.
     *
     * @param string $table
     *
     * @return string
     */
    protected function createBaseMigration($table = 'failed_jobs')
    {
        $name = 'create_'.$table.'_table';

        $path = 'database/migrations';

        return $this->creator->create($name, $path);
    }
}
