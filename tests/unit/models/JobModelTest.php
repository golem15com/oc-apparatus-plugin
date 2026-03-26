<?php namespace Golem15\Apparatus\Tests\Unit\Models;

use Golem15\Apparatus\Models\Job;
use Golem15\Apparatus\Tests\PluginTestCase;

/**
 * Tests for the Job model.
 */
class JobModelTest extends PluginTestCase
{
    // -------------------------------------------------------------------------
    // getMetadata
    // -------------------------------------------------------------------------

    public function testGetMetadataReturnsEmptyArrayWhenMetadataIsNull(): void
    {
        $job = new Job();
        $job->metadata = null;

        $this->assertSame([], $job->getMetadata());
    }

    public function testGetMetadataReturnsEmptyArrayWhenMetadataIsEmpty(): void
    {
        $job = new Job();
        $job->metadata = [];

        $this->assertSame([], $job->getMetadata());
    }

    public function testGetMetadataReturnsArrayWhenMetadataIsSet(): void
    {
        $job = new Job();
        $job->metadata = ['key' => 'value', 'count' => 42];

        $result = $job->getMetadata();

        $this->assertIsArray($result);
        $this->assertSame('value', $result['key']);
        $this->assertSame(42, $result['count']);
    }

    // -------------------------------------------------------------------------
    // getStatus
    // -------------------------------------------------------------------------

    public function testGetStatusReturnsStringForStatusInQueue(): void
    {
        $job = new Job();
        $job->status = 0;

        $this->assertIsString($job->getStatus());
    }

    public function testGetStatusReturnsStringForStatusInProgress(): void
    {
        $job = new Job();
        $job->status = 1;

        $this->assertIsString($job->getStatus());
    }

    public function testGetStatusReturnsStringForStatusComplete(): void
    {
        $job = new Job();
        $job->status = 2;

        $this->assertIsString($job->getStatus());
    }

    public function testGetStatusReturnsStringForStatusError(): void
    {
        $job = new Job();
        $job->status = 3;

        $this->assertIsString($job->getStatus());
    }

    public function testGetStatusReturnsStringForStatusStopped(): void
    {
        $job = new Job();
        $job->status = 4;

        $this->assertIsString($job->getStatus());
    }

    public function testGetStatusReturnsStringForUnknownStatus(): void
    {
        $job = new Job();
        $job->status = 99;

        $result = $job->getStatus();

        $this->assertIsString($result);
        // In the test environment trans() returns the key; assert it's not empty
        $this->assertNotEmpty($result);
    }

    // -------------------------------------------------------------------------
    // progressPercent
    // -------------------------------------------------------------------------

    public function testProgressPercentReturnsZeroWhenProgressIsZeroAndMaxIs100(): void
    {
        $job = new Job();
        $job->progress = 0;
        $job->progress_max = 100;

        $this->assertSame(0.0, $job->progressPercent());
    }

    public function testProgressPercentReturnsFiftyWhenProgressIsFiftyAndMaxIs100(): void
    {
        $job = new Job();
        $job->progress = 50;
        $job->progress_max = 100;

        $this->assertSame(50.0, $job->progressPercent());
    }

    public function testProgressPercentHandlesZeroProgressMax(): void
    {
        $job = new Job();
        $job->progress = 0;
        $job->progress_max = 0;

        // Should not throw a division by zero error; progress_max becomes 1
        $result = $job->progressPercent();

        $this->assertIsFloat($result);
        $this->assertSame(0.0, $result);
    }

    // -------------------------------------------------------------------------
    // canBeCanceled
    // -------------------------------------------------------------------------

    public function testCanBeCanceledReturnsTrueForStatusInQueue(): void
    {
        $job = new Job();
        $job->status = 0;

        $this->assertTrue($job->canBeCanceled());
    }

    public function testCanBeCanceledReturnsTrueForStatusInProgress(): void
    {
        $job = new Job();
        $job->status = 1;

        $this->assertTrue($job->canBeCanceled());
    }

    public function testCanBeCanceledReturnsFalseForStatusComplete(): void
    {
        $job = new Job();
        $job->status = 2;

        $this->assertFalse($job->canBeCanceled());
    }

    public function testCanBeCanceledReturnsFalseForStatusError(): void
    {
        $job = new Job();
        $job->status = 3;

        $this->assertFalse($job->canBeCanceled());
    }

    public function testCanBeCanceledReturnsFalseForStatusStopped(): void
    {
        $job = new Job();
        $job->status = 4;

        $this->assertFalse($job->canBeCanceled());
    }
}
