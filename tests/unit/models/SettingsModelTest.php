<?php namespace Golem15\Apparatus\Tests\Unit\Models;

use Golem15\Apparatus\Models\Settings;
use Golem15\Apparatus\Tests\PluginTestCase;

/**
 * Tests for the Settings model option-list methods.
 */
class SettingsModelTest extends PluginTestCase
{
    // -------------------------------------------------------------------------
    // listAnimations
    // -------------------------------------------------------------------------

    public function testListAnimationsReturnsNonEmptyArray(): void
    {
        $settings = new Settings();
        $result = $settings->listAnimations();

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    public function testListAnimationsFirstKeyIsEmptyString(): void
    {
        $settings = new Settings();
        $result = $settings->listAnimations();

        $keys = array_keys($result);
        $this->assertSame('', $keys[0]);
    }

    public function testListAnimationsContainsKnownEntry(): void
    {
        $settings = new Settings();
        $result = $settings->listAnimations();

        $this->assertArrayHasKey('animated bounce', $result);
        $this->assertSame('bounce', $result['animated bounce']);
    }

    // -------------------------------------------------------------------------
    // getThemeOptions
    // -------------------------------------------------------------------------

    public function testGetThemeOptionsReturnsArrayWithTailwindKey(): void
    {
        $settings = new Settings();
        $result = $settings->getThemeOptions();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('tailwind', $result);
    }

    public function testGetThemeOptionsReturnsNonEmptyArray(): void
    {
        $settings = new Settings();
        $result = $settings->getThemeOptions();

        $this->assertNotEmpty($result);
    }

    // -------------------------------------------------------------------------
    // getLayoutOptions
    // -------------------------------------------------------------------------

    public function testGetLayoutOptionsReturnsArrayWithExpectedPositionKeys(): void
    {
        $settings = new Settings();
        $result = $settings->getLayoutOptions();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('top', $result);
        $this->assertArrayHasKey('center', $result);
        $this->assertArrayHasKey('bottom', $result);
        $this->assertArrayHasKey('topLeft', $result);
        $this->assertArrayHasKey('topRight', $result);
        $this->assertArrayHasKey('bottomLeft', $result);
        $this->assertArrayHasKey('bottomRight', $result);
    }

    // -------------------------------------------------------------------------
    // getOpenAnimationOptions / getCloseAnimationOptions
    // -------------------------------------------------------------------------

    public function testGetOpenAnimationOptionsReturnsSameAsListAnimations(): void
    {
        $settings = new Settings();

        $this->assertSame($settings->listAnimations(), $settings->getOpenAnimationOptions());
    }

    public function testGetCloseAnimationOptionsReturnsSameAsListAnimations(): void
    {
        $settings = new Settings();

        $this->assertSame($settings->listAnimations(), $settings->getCloseAnimationOptions());
    }
}
