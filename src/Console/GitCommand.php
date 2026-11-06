<?php

namespace AhmedArafat\AllInOne\Console;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

/**
 * Stage local changes, commit them with a Cairo timestamp and push to the requested remote and branch.
 */
class GitCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'git:git {msg} {branch} {remote=origin} ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'git add . then commit them push to GitHub';

    /**
     * Display Git status, stage changes, commit with the supplied message and push the selected branch.
     */
    public function handle(): int
    {
        $output = function (string $type, string $output) {
            $this->output->write($output);
        };
        $time = Carbon::now('Africa/Cairo')->format('h:i a Y/m/d');
        $message = $this->argument('msg') . ' --- ' . $time;

        foreach ([['git', 'status'], ['git', 'add', '.'], ['git', 'commit', '-m', $message]] as $command) {
            $result = Process::run($command, $output);
            if ($result->failed()) {
                return $result->exitCode();
            }
        }

        return Process::forever()->run(
            ['git', 'push', '--', $this->argument('remote'), $this->argument('branch')],
            $output
        )->exitCode();
    }
}
