<?php namespace Golem15\Apparatus\Tests\Unit\ValueObjects;

use Golem15\Apparatus\ValueObjects\GenerationOptions;
use Golem15\Apparatus\Tests\PluginTestCase;

/**
 * Tests for the GenerationOptions value object.
 */
class GenerationOptionsTest extends PluginTestCase
{
    // -------------------------------------------------------------------------
    // default values
    // -------------------------------------------------------------------------

    public function testDefaultVarsIsEmptyArray(): void
    {
        $options = new GenerationOptions();

        $this->assertSame([], $options->vars);
    }

    public function testDefaultTimeoutIs90(): void
    {
        $options = new GenerationOptions();

        $this->assertSame(90, $options->timeout);
    }

    public function testDefaultFlagsIsEmptyArray(): void
    {
        $options = new GenerationOptions();

        $this->assertSame([], $options->flags);
    }

    // -------------------------------------------------------------------------
    // setting properties
    // -------------------------------------------------------------------------

    public function testCanSetTypeProperty(): void
    {
        $options = new GenerationOptions();
        $options->type = 'pdf';

        $this->assertSame('pdf', $options->type);
    }

    public function testCanSetUrlProperty(): void
    {
        $options = new GenerationOptions();
        $options->url = 'https://example.com';

        $this->assertSame('https://example.com', $options->url);
    }

    public function testCanSetWidthProperty(): void
    {
        $options = new GenerationOptions();
        $options->width = 1920;

        $this->assertSame(1920, $options->width);
    }

    public function testCanSetHeightProperty(): void
    {
        $options = new GenerationOptions();
        $options->height = 1080;

        $this->assertSame(1080, $options->height);
    }

    public function testCanSetFileNameProperty(): void
    {
        $options = new GenerationOptions();
        $options->fileName = 'output.pdf';

        $this->assertSame('output.pdf', $options->fileName);
    }

    public function testCanSetPathProperty(): void
    {
        $options = new GenerationOptions();
        $options->path = '/var/storage/output.pdf';

        $this->assertSame('/var/storage/output.pdf', $options->path);
    }

    public function testCanSetVarsProperty(): void
    {
        $options = new GenerationOptions();
        $options->vars = ['key' => 'value'];

        $this->assertSame(['key' => 'value'], $options->vars);
    }

    public function testCanSetTimeoutProperty(): void
    {
        $options = new GenerationOptions();
        $options->timeout = 120;

        $this->assertSame(120, $options->timeout);
    }

    public function testCanSetFlagsProperty(): void
    {
        $options = new GenerationOptions();
        $options->flags = ['--headless', '--no-sandbox'];

        $this->assertSame(['--headless', '--no-sandbox'], $options->flags);
    }

    // -------------------------------------------------------------------------
    // all properties together
    // -------------------------------------------------------------------------

    public function testCanSetAllProperties(): void
    {
        $options = new GenerationOptions();
        $options->type = 'image';
        $options->url = 'https://example.com';
        $options->width = 1024;
        $options->height = 768;
        $options->fileName = 'screenshot.png';
        $options->path = '/tmp/screenshot.png';
        $options->vars = ['token' => 'abc123'];
        $options->timeout = 60;
        $options->flags = ['--headless', '--screenshot=/tmp/screenshot.png'];

        $this->assertSame('image', $options->type);
        $this->assertSame('https://example.com', $options->url);
        $this->assertSame(1024, $options->width);
        $this->assertSame(768, $options->height);
        $this->assertSame('screenshot.png', $options->fileName);
        $this->assertSame('/tmp/screenshot.png', $options->path);
        $this->assertSame(['token' => 'abc123'], $options->vars);
        $this->assertSame(60, $options->timeout);
        $this->assertSame(['--headless', '--screenshot=/tmp/screenshot.png'], $options->flags);
    }
}
