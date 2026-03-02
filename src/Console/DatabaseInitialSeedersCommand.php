<?php

namespace AhmedArafat\AllInOne\Console;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;
use function Laravel\Prompts\progress;

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

    public function __construct()
    {
        parent::__construct();
        $this->allSeedersObjects = config('all-in-one.database_seeders', []);
    }

    /**
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
     * Execute the console command.
     *
     * @throws Exception|Throwable
     */
    public function handle(): void
    {
        $this->executeAllSeeders();
        $this->info('Done Seeding Database <3');
    }
}
