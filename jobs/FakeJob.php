<?php

namespace Golem15\Apparatus\Jobs;

use Golem15\Apparatus\Classes\JobManager;
use Golem15\Apparatus\Contracts\ApparatusQueueJob;

class FakeJob implements ApparatusQueueJob
{
    public $jobId;
    public int $seconds;

    public function __construct(int $seconds)
    {
        $this->seconds = $seconds;
    }

    public function handle(JobManager $jobManager)
    {
        // Start the job
        $jobManager->startJob($this->jobId, $this->seconds);

        // Sleep for the specified seconds, one second at a time
        for ($i = 0; $i < $this->seconds; $i++) {
            sleep(1);
            // Optionally, update job status every second
            $jobManager->updateJobState($this->jobId, $i);
        }

        // Mark the job as completed
        $jobManager->updateJobStatus($this->jobId, "Completed");

    }

    public function assignJobId(int $id) {
        $this->jobId = $id;
    }
}
