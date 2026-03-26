<?php namespace Golem15\Apparatus\Tests\Unit\Classes;

use Golem15\Apparatus\Classes\Clearer;
use Golem15\Apparatus\Tests\PluginTestCase;
use Illuminate\Contracts\Queue\Factory as FactoryContract;
use Illuminate\Contracts\Queue\Queue;
use Mockery;

/**
 * Tests for the Clearer class.
 */
class ClearerTest extends PluginTestCase
{
    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // clear — empty queue
    // -------------------------------------------------------------------------

    public function testClearWithEmptyQueueReturnsZero(): void
    {
        $queue = Mockery::mock(Queue::class);
        $queue->shouldReceive('pop')->with('default')->andReturn(null)->once();

        $factory = Mockery::mock(FactoryContract::class);
        $factory->shouldReceive('connection')->with('sync')->andReturn($queue);

        $clearer = new Clearer($factory);
        $result = $clearer->clear('sync', 'default');

        $this->assertSame(0, $result);
    }

    // -------------------------------------------------------------------------
    // clear — queue with jobs
    // -------------------------------------------------------------------------

    public function testClearWithThreeJobsReturnsThreeAndDeletesEach(): void
    {
        $job1 = Mockery::mock('QueueJob');
        $job1->shouldReceive('delete')->once();

        $job2 = Mockery::mock('QueueJob');
        $job2->shouldReceive('delete')->once();

        $job3 = Mockery::mock('QueueJob');
        $job3->shouldReceive('delete')->once();

        $queue = Mockery::mock(Queue::class);
        $queue->shouldReceive('pop')
            ->with('default')
            ->andReturn($job1, $job2, $job3, null);

        $factory = Mockery::mock(FactoryContract::class);
        $factory->shouldReceive('connection')->with('sync')->andReturn($queue);

        $clearer = new Clearer($factory);
        $result = $clearer->clear('sync', 'default');

        $this->assertSame(3, $result);
    }

    // -------------------------------------------------------------------------
    // clear — uses the given connection and queue names
    // -------------------------------------------------------------------------

    public function testClearPassesConnectionAndQueueNameCorrectly(): void
    {
        $queue = Mockery::mock(Queue::class);
        $queue->shouldReceive('pop')->with('my-queue')->andReturn(null)->once();

        $factory = Mockery::mock(FactoryContract::class);
        $factory->shouldReceive('connection')->with('redis')->andReturn($queue)->once();

        $clearer = new Clearer($factory);
        $clearer->clear('redis', 'my-queue');

        // Mockery expectations above verify that the correct names were passed
        $this->assertTrue(true);
    }
}
