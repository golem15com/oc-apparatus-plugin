<?php namespace Golem15\Apparatus\Tests\Unit\Classes;

use Golem15\Apparatus\Classes\JobManager;
use Golem15\Apparatus\Contracts\ApparatusQueueJob;
use Golem15\Apparatus\Contracts\JobStatus;
use Golem15\Apparatus\Tests\PluginTestCase;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Database\Connection;
use Mockery;

/**
 * Tests for the JobManager class.
 */
class JobManagerTest extends PluginTestCase
{
    private Connection $db;
    private Queue $queue;
    private JobManager $manager;

    public function setUp(): void
    {
        parent::setUp();
        $this->db = Mockery::mock(Connection::class);
        $this->queue = Mockery::mock(Queue::class);
        $this->manager = new JobManager($this->db, $this->queue);
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Helper: create a query builder mock
    // -------------------------------------------------------------------------

    private function makeQueryBuilderMock(): \Mockery\MockInterface
    {
        $qb = Mockery::mock('QueryBuilder');
        $qb->shouldReceive('where')->andReturn($qb)->byDefault();
        $qb->shouldReceive('select')->andReturn($qb)->byDefault();
        return $qb;
    }

    // -------------------------------------------------------------------------
    // dispatch
    // -------------------------------------------------------------------------

    public function testDispatchInsertsJobRecordAndPushesToQueue(): void
    {
        $qb = $this->makeQueryBuilderMock();
        $qb->shouldReceive('insertGetId')
            ->once()
            ->with(Mockery::on(function ($data) {
                return $data['status'] === JobStatus::IN_PROGRESS
                    && $data['label'] === 'Test Job'
                    && $data['progress'] === 0;
            }))
            ->andReturn(42);

        $this->db->shouldReceive('table')
            ->with(JobManager::JOB_TABLE)
            ->andReturn($qb);

        $job = Mockery::mock(ApparatusQueueJob::class);
        $job->shouldReceive('assignJobId')->with(42)->once();

        $this->queue->shouldReceive('push')->with($job)->once();

        $jobId = $this->manager->dispatch($job, 'Test Job');

        $this->assertSame(42, $jobId);
    }

    public function testDispatchWithDelayUsesQueueLater(): void
    {
        $qb = $this->makeQueryBuilderMock();
        $qb->shouldReceive('insertGetId')->once()->andReturn(1);

        $this->db->shouldReceive('table')
            ->with(JobManager::JOB_TABLE)
            ->andReturn($qb);

        $job = Mockery::mock(ApparatusQueueJob::class);
        $job->shouldReceive('assignJobId')->once();

        $this->queue->shouldReceive('later')->with(60, $job)->once();
        $this->queue->shouldNotReceive('push');

        $this->manager->dispatch($job, 'Delayed Job', [], 60);

        $this->addToAssertionCount(Mockery::getContainer()->mockery_getExpectationCount());
    }

    // -------------------------------------------------------------------------
    // startJob
    // -------------------------------------------------------------------------

    public function testStartJobUpdatesProgressAndProgressMax(): void
    {
        $qb = $this->makeQueryBuilderMock();
        $qb->shouldReceive('update')
            ->once()
            ->with(Mockery::on(function ($data) {
                return $data['progress'] === 0
                    && $data['progress_max'] === 100;
            }));

        $this->db->shouldReceive('table')
            ->with(JobManager::JOB_TABLE)
            ->andReturn($qb);

        $this->manager->startJob(1, 100);

        $this->addToAssertionCount(Mockery::getContainer()->mockery_getExpectationCount());
    }

    // -------------------------------------------------------------------------
    // updateJobState
    // -------------------------------------------------------------------------

    public function testUpdateJobStateUpdatesProgress(): void
    {
        $qb = $this->makeQueryBuilderMock();
        $qb->shouldReceive('update')
            ->once()
            ->with(['progress' => 5]);

        $this->db->shouldReceive('table')
            ->with(JobManager::JOB_TABLE)
            ->andReturn($qb);

        $this->manager->updateJobState(1, 5);

        $this->addToAssertionCount(Mockery::getContainer()->mockery_getExpectationCount());
    }

    public function testUpdateJobStateWithMetadataAlsoUpdatesMetadata(): void
    {
        $qb = $this->makeQueryBuilderMock();

        // First call: update progress; second call: update metadata
        $qb->shouldReceive('update')->twice();

        $this->db->shouldReceive('table')
            ->with(JobManager::JOB_TABLE)
            ->andReturn($qb);

        $this->manager->updateJobState(1, 3, ['key' => 'value']);

        $this->addToAssertionCount(Mockery::getContainer()->mockery_getExpectationCount());
    }

    // -------------------------------------------------------------------------
    // completeJob
    // -------------------------------------------------------------------------

    public function testCompleteJobUpdatesStatusToComplete(): void
    {
        $progressRow = (object)['progress_max' => 10];
        $qb = $this->makeQueryBuilderMock();

        // first() returns progress_max row
        $qb->shouldReceive('first')->andReturn($progressRow)->once();

        // update() with COMPLETE status
        $qb->shouldReceive('update')
            ->once()
            ->with(Mockery::on(function ($data) {
                return $data['status'] === JobStatus::COMPLETE
                    && $data['progress'] === 10;
            }));

        $this->db->shouldReceive('table')
            ->with(JobManager::JOB_TABLE)
            ->andReturn($qb);

        $this->manager->completeJob(1);

        $this->addToAssertionCount(Mockery::getContainer()->mockery_getExpectationCount());
    }

    public function testCompleteJobWithSimpleJobDeletesRecord(): void
    {
        $progressRow = (object)['progress_max' => 5];
        $qb = $this->makeQueryBuilderMock();

        $qb->shouldReceive('first')->andReturn($progressRow)->once();
        $qb->shouldReceive('delete')->once();
        $qb->shouldNotReceive('update');

        $this->db->shouldReceive('table')
            ->with(JobManager::JOB_TABLE)
            ->andReturn($qb);

        $this->manager->setSimpleJob(true);
        $this->manager->completeJob(1);

        $this->addToAssertionCount(Mockery::getContainer()->mockery_getExpectationCount());
    }

    // -------------------------------------------------------------------------
    // failJob
    // -------------------------------------------------------------------------

    public function testFailJobUpdatesStatusToError(): void
    {
        $qb = $this->makeQueryBuilderMock();
        $qb->shouldReceive('update')
            ->once()
            ->with(Mockery::on(function ($data) {
                return $data['status'] === JobStatus::ERROR;
            }));

        $this->db->shouldReceive('table')
            ->with(JobManager::JOB_TABLE)
            ->andReturn($qb);

        $this->manager->failJob(1);

        $this->addToAssertionCount(Mockery::getContainer()->mockery_getExpectationCount());
    }

    // -------------------------------------------------------------------------
    // cancelJob
    // -------------------------------------------------------------------------

    public function testCancelJobUpdatesStatusToStopped(): void
    {
        $qb = $this->makeQueryBuilderMock();
        $qb->shouldReceive('update')
            ->once()
            ->with(Mockery::on(function ($data) {
                return $data['status'] === JobStatus::STOPPED;
            }));

        $this->db->shouldReceive('table')
            ->with(JobManager::JOB_TABLE)
            ->andReturn($qb);

        $this->manager->cancelJob(1);

        $this->addToAssertionCount(Mockery::getContainer()->mockery_getExpectationCount());
    }

    // -------------------------------------------------------------------------
    // isSimpleJob / setSimpleJob
    // -------------------------------------------------------------------------

    public function testIsSimpleJobReturnsFalseByDefault(): void
    {
        $this->assertFalse($this->manager->isSimpleJob());
    }

    public function testSetSimpleJobUpdatesFlag(): void
    {
        $this->manager->setSimpleJob(true);
        $this->assertTrue($this->manager->isSimpleJob());

        $this->manager->setSimpleJob(false);
        $this->assertFalse($this->manager->isSimpleJob());
    }

    // -------------------------------------------------------------------------
    // checkIfCanceled
    // -------------------------------------------------------------------------

    public function testCheckIfCanceledReturnsBoolFromDatabase(): void
    {
        $row = (object)['is_canceled' => 1];
        $qb = $this->makeQueryBuilderMock();
        $qb->shouldReceive('first')->andReturn($row)->once();

        $this->db->shouldReceive('table')
            ->with(JobManager::JOB_TABLE)
            ->andReturn($qb);

        $this->assertTrue($this->manager->checkIfCanceled(1));
    }

    public function testCheckIfCanceledReturnsFalseWhenNotCanceled(): void
    {
        $row = (object)['is_canceled' => 0];
        $qb = $this->makeQueryBuilderMock();
        $qb->shouldReceive('first')->andReturn($row)->once();

        $this->db->shouldReceive('table')
            ->with(JobManager::JOB_TABLE)
            ->andReturn($qb);

        $this->assertFalse($this->manager->checkIfCanceled(1));
    }

    // -------------------------------------------------------------------------
    // getMetadata
    // -------------------------------------------------------------------------

    public function testGetMetadataReturnsDecodedJsonArray(): void
    {
        $row = (object)['metadata' => json_encode(['foo' => 'bar', 'count' => 5])];
        $qb = $this->makeQueryBuilderMock();
        $qb->shouldReceive('first')->andReturn($row)->once();

        $this->db->shouldReceive('table')
            ->with(JobManager::JOB_TABLE)
            ->andReturn($qb);

        $result = $this->manager->getMetadata(1);

        $this->assertSame(['foo' => 'bar', 'count' => 5], $result);
    }

    public function testGetMetadataReturnsEmptyArrayForNullOrInvalidJson(): void
    {
        $row = (object)['metadata' => null];
        $qb = $this->makeQueryBuilderMock();
        $qb->shouldReceive('first')->andReturn($row)->once();

        $this->db->shouldReceive('table')
            ->with(JobManager::JOB_TABLE)
            ->andReturn($qb);

        $result = $this->manager->getMetadata(1);

        $this->assertSame([], $result);
    }
}
