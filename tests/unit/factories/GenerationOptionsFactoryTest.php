<?php namespace Golem15\Apparatus\Tests\Unit\Factories;

use Golem15\Apparatus\Factories\GenerationOptionsFactory;
use Golem15\Apparatus\ValueObjects\GenerationOptions;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the GenerationOptionsFactory class.
 */
class GenerationOptionsFactoryTest extends TestCase
{
    // -------------------------------------------------------------------------
    // createForPDF
    // -------------------------------------------------------------------------

    public function testCreateForPdfReturnsGenerationOptionsInstance(): void
    {
        $options = GenerationOptionsFactory::createForPDF(
            'https://example.com',
            'output.pdf',
            '/tmp/output.pdf'
        );

        $this->assertInstanceOf(GenerationOptions::class, $options);
    }

    public function testCreateForPdfSetsTypeAsPdf(): void
    {
        $options = GenerationOptionsFactory::createForPDF(
            'https://example.com',
            'output.pdf',
            '/tmp/output.pdf'
        );

        $this->assertSame('pdf', $options->type);
    }

    public function testCreateForPdfSetsUrl(): void
    {
        $url = 'https://example.com/invoice/123';
        $options = GenerationOptionsFactory::createForPDF($url, 'file.pdf', '/tmp/file.pdf');

        $this->assertSame($url, $options->url);
    }

    public function testCreateForPdfSetsFileName(): void
    {
        $options = GenerationOptionsFactory::createForPDF(
            'https://example.com',
            'my_invoice.pdf',
            '/tmp/my_invoice.pdf'
        );

        $this->assertSame('my_invoice.pdf', $options->fileName);
    }

    public function testCreateForPdfSetsPath(): void
    {
        $options = GenerationOptionsFactory::createForPDF(
            'https://example.com',
            'output.pdf',
            '/var/storage/output.pdf'
        );

        $this->assertSame('/var/storage/output.pdf', $options->path);
    }

    public function testCreateForPdfDefaultWidthIs1477(): void
    {
        $options = GenerationOptionsFactory::createForPDF(
            'https://example.com',
            'output.pdf',
            '/tmp/output.pdf'
        );

        $this->assertSame(1477, $options->width);
    }

    public function testCreateForPdfDefaultHeightIs768(): void
    {
        $options = GenerationOptionsFactory::createForPDF(
            'https://example.com',
            'output.pdf',
            '/tmp/output.pdf'
        );

        $this->assertSame(768, $options->height);
    }

    public function testCreateForPdfCustomDimensionsArePassedThrough(): void
    {
        $options = GenerationOptionsFactory::createForPDF(
            'https://example.com',
            'output.pdf',
            '/tmp/output.pdf',
            1920,
            1080
        );

        $this->assertSame(1920, $options->width);
        $this->assertSame(1080, $options->height);
    }

    public function testCreateForPdfFlagsContainPrintToPdf(): void
    {
        $options = GenerationOptionsFactory::createForPDF(
            'https://example.com',
            'output.pdf',
            '/tmp/output.pdf'
        );

        $flagsString = implode(' ', $options->flags);
        $this->assertStringContainsString('--print-to-pdf', $flagsString);
    }

    public function testCreateForPdfFlagsContainHeadless(): void
    {
        $options = GenerationOptionsFactory::createForPDF(
            'https://example.com',
            'output.pdf',
            '/tmp/output.pdf'
        );

        $this->assertContains('--headless', $options->flags);
    }

    // -------------------------------------------------------------------------
    // createForImage
    // -------------------------------------------------------------------------

    public function testCreateForImageReturnsGenerationOptionsInstance(): void
    {
        $options = GenerationOptionsFactory::createForImage(
            'https://example.com',
            'screenshot.png',
            '/tmp/screenshot.png'
        );

        $this->assertInstanceOf(GenerationOptions::class, $options);
    }

    public function testCreateForImageSetsTypeAsImage(): void
    {
        $options = GenerationOptionsFactory::createForImage(
            'https://example.com',
            'screenshot.png',
            '/tmp/screenshot.png'
        );

        $this->assertSame('image', $options->type);
    }

    public function testCreateForImageSetsUrl(): void
    {
        $url = 'https://example.com/page/preview';
        $options = GenerationOptionsFactory::createForImage($url, 'preview.png', '/tmp/preview.png');

        $this->assertSame($url, $options->url);
    }

    public function testCreateForImageDefaultWidthIs1024(): void
    {
        $options = GenerationOptionsFactory::createForImage(
            'https://example.com',
            'screenshot.png',
            '/tmp/screenshot.png'
        );

        $this->assertSame(1024, $options->width);
    }

    public function testCreateForImageDefaultHeightIs1024(): void
    {
        $options = GenerationOptionsFactory::createForImage(
            'https://example.com',
            'screenshot.png',
            '/tmp/screenshot.png'
        );

        $this->assertSame(1024, $options->height);
    }

    public function testCreateForImageCustomDimensionsArePassedThrough(): void
    {
        $options = GenerationOptionsFactory::createForImage(
            'https://example.com',
            'screenshot.png',
            '/tmp/screenshot.png',
            1280,
            720
        );

        $this->assertSame(1280, $options->width);
        $this->assertSame(720, $options->height);
    }

    public function testCreateForImageFlagsContainScreenshot(): void
    {
        $options = GenerationOptionsFactory::createForImage(
            'https://example.com',
            'screenshot.png',
            '/tmp/screenshot.png'
        );

        $flagsString = implode(' ', $options->flags);
        $this->assertStringContainsString('--screenshot', $flagsString);
    }

    public function testCreateForImageFlagsContainHeadless(): void
    {
        $options = GenerationOptionsFactory::createForImage(
            'https://example.com',
            'screenshot.png',
            '/tmp/screenshot.png'
        );

        $this->assertContains('--headless', $options->flags);
    }
}
