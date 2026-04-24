<?php namespace Golem15\Apparatus\Tests\Unit\Classes;

use Carbon\Carbon;
use Golem15\Apparatus\Classes\HumanDateExtension;
use Golem15\Apparatus\Tests\PluginTestCase;

/**
 * Tests for the HumanDateExtension class.
 *
 * Note: trans() in the test environment returns the translation key string,
 * so we assert that results are non-empty strings rather than exact translations.
 */
class HumanDateExtensionTest extends PluginTestCase
{
    private HumanDateExtension $extension;

    public function setUp(): void
    {
        parent::setUp();
        $this->extension = new HumanDateExtension();
    }

    public function tearDown(): void
    {
        Carbon::setTestNow(null);
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Today
    // -------------------------------------------------------------------------

    public function testHumanDateFilterWithTodayReturnsNonEmptyString(): void
    {
        Carbon::setTestNow(Carbon::parse('2024-06-15 10:00:00'));
        $today = Carbon::now()->setTime(14, 30, 0);

        $result = $this->extension->humanDateFilter($today);

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function testHumanDateFilterWithTodayContainsTimeFormat(): void
    {
        Carbon::setTestNow(Carbon::parse('2024-06-15 10:00:00'));
        $today = Carbon::now()->setTime(14, 30, 0);

        $result = $this->extension->humanDateFilter($today);

        // The today translation includes the time portion (H:i format)
        $this->assertStringContainsString('14:30', $result);
    }

    // -------------------------------------------------------------------------
    // Tomorrow
    // -------------------------------------------------------------------------

    public function testHumanDateFilterWithTomorrowReturnsNonEmptyString(): void
    {
        Carbon::setTestNow(Carbon::parse('2024-06-15 10:00:00'));
        $tomorrow = Carbon::tomorrow()->setTime(9, 0, 0);

        $result = $this->extension->humanDateFilter($tomorrow);

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    // -------------------------------------------------------------------------
    // Same week
    // -------------------------------------------------------------------------

    public function testHumanDateFilterWithinSameWeekReturnsNonEmptyString(): void
    {
        // Monday
        Carbon::setTestNow(Carbon::parse('2024-06-10 10:00:00'));
        // Thursday — same week, after now
        $thisWeek = Carbon::parse('2024-06-13 15:00:00');

        $result = $this->extension->humanDateFilter($thisWeek);

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    // -------------------------------------------------------------------------
    // Next week
    // -------------------------------------------------------------------------

    public function testHumanDateFilterWithNextWeekReturnsNonEmptyString(): void
    {
        Carbon::setTestNow(Carbon::parse('2024-06-10 10:00:00'));
        $nextWeek = Carbon::parse('2024-06-17 10:00:00');

        $result = $this->extension->humanDateFilter($nextWeek);

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    // -------------------------------------------------------------------------
    // Far future
    // -------------------------------------------------------------------------

    public function testHumanDateFilterWithFarFutureReturnsNonEmptyString(): void
    {
        Carbon::setTestNow(Carbon::parse('2024-06-15 10:00:00'));
        $farFuture = Carbon::parse('2027-01-01 00:00:00');

        $result = $this->extension->humanDateFilter($farFuture);

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    // -------------------------------------------------------------------------
    // instantiation without register()
    // -------------------------------------------------------------------------

    public function testHumanDateExtensionCanBeInstantiatedWithoutRegister(): void
    {
        $extension = new HumanDateExtension();

        $this->assertInstanceOf(HumanDateExtension::class, $extension);
    }
}
