<?php

namespace Golem15\Apparatus\Factories;

use Golem15\Apparatus\ValueObjects\GenerationOptions;

class GenerationOptionsFactory
{
    public static function createForPDF(string $url, string $fileName, string $path, int $width = 1477, int $height = 768, array $vars = [])
    {
        $options = new GenerationOptions();
        $options->url = $url;
        $options->width = $width;
        $options->height = $height;
        $options->type = 'pdf';
        $options->fileName = $fileName;
        $options->vars = $vars;
        $options->path = $path;

        $options->flags = self::pdfFlags("{$width},{$height}", $path);
        return $options;
    }

    public static function createForImage(string $url, string $fileName, string $path, int $width = 1024, int $height = 1024, array $vars = [])
    {
        $options = new GenerationOptions();
        $options->url = $url;
        $options->width = $width;
        $options->height = $height;
        $options->type = 'image';
        $options->fileName = $fileName;
        $options->vars = $vars;
        $options->path = $path;

        $options->flags = self::imageFlags("{$width},{$height}", $path);
        return $options;
    }

    private static function pdfFlags($resolution, $path)
    {
        return [
            '--headless',
            '--no-sandbox',
            '--disable-dev-shm-usage',
            '--disable-gpu',
            '--no-zygote',
            '--disable-software-rasterizer',
            '--disable-background-networking',
            '--disable-default-apps',
            '--disable-extensions',
            '--disable-sync',
            '--metrics-recording-only',
            '--safebrowsing-disable-auto-update',
            '--disable-hang-monitor',
            '--disable-popup-blocking',
            '--disable-prompt-on-repost',
            '--disable-client-side-phishing-detection',
            '--disable-notifications',
            '--disable-push-messaging',
            '--disable-gcm-registration',
            '--ignore-certificate-errors',
            '--allow-insecure-localhost',
            '--incognito',
            "--window-size={$resolution}",
            '--hide-scrollbars',
            '--print-to-pdf-no-header',
            "--print-to-pdf={$path}",
            "--no-pdf-header-footer"
        ];
    }

    private static function imageFlags($resolution, $path)
    {
        return [
            '--headless',
            '--no-sandbox',
            '--disable-dev-shm-usage',
            '--disable-gpu',
            '--no-zygote',
            '--disable-software-rasterizer',
            '--disable-background-networking',
            '--disable-default-apps',
            '--disable-extensions',
            '--disable-sync',
            '--metrics-recording-only',
            '--safebrowsing-disable-auto-update',
            '--disable-hang-monitor',
            '--disable-popup-blocking',
            '--disable-prompt-on-repost',
            '--disable-client-side-phishing-detection',
            '--disable-notifications',
            '--disable-push-messaging',
            '--disable-gcm-registration',
            '--ignore-certificate-errors',
            '--allow-insecure-localhost',
            '--incognito',
            "--window-size={$resolution}",
            '--hide-scrollbars',
            "--screenshot={$path}",
        ];
    }
}
