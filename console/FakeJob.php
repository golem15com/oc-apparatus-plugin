<?php

namespace Golem15\Apparatus\Console;

use Golem15\Apparatus\Classes\JobManager;
use Golem15\Apparatus\Jobs\FakeJob as FakeJobJob;
use Illuminate\Console\Command;

class FakeJob extends Command
{
    /**
     * The console command name.
     */
    protected $name = 'apparatus:fakejob';

    /**
     * The console command signature.
     */
    protected $signature = 'apparatus:fakejob {amount}';

    /**
     * The console command description.
     */
    protected $description = 'Runs a fake job for {amount} seconds';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $seconds = (int) $this->argument('amount');

        $job = new FakeJobJob($seconds);

        /** @var JobManager $jobManager */
        $jobManager = app(JobManager::class);

        // Assign a job ID
        $jobManager->dispatch($job, 'Test Job');

        $this->info("Fake job handled for {$seconds} seconds.");
    }
}