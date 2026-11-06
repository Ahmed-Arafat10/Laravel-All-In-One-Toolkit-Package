<?php

namespace AhmedArafat\AllInOne\Console;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;
use function Laravel\Prompts\progress;

/**
 * Run the seeders listed in the package configuration with the db:setup command.
 */
class DatabaseInitialSeedersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:setup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize the database by running all required seeders';

    private array $allSeedersObjects = [];

    /**
     * Load the seeder class names from all-in-one.database_seeders.
     */
    public function __construct()
    {
        parent::__construct();
        $this->allSeedersObjects = config('all-in-one.database_seeders', []);
    }

    /**
     * Run the configured seeders in one database transaction and display their progress.
     *
     * @throws Throwable
     */
    private function executeAllSeeders(): void
    {
        DB::transaction(function () {
            progress(
                'Seeding Database ...',
                count($this->allSeedersObjects),
                function ($num) {
                    (new $this->allSeedersObjects[$num])->run();
                }
            );
        });
    }

    /**
     * Run the configured seeders and report completion.
     *
     * @throws Exception|Throwable
     */
    public function handle(): void
    {
        $this->executeAllSeeders();
        $this->info('Done Seeding Database <3');
    }
}
