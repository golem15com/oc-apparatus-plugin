<?php

namespace Golem15\Apparatus\ValueObjects;

class GenerationOptions
{
    public string $type;
    public string $url;
    public int $width;
    public int $height;
    public string $fileName;
    public string $path;
    public array $vars = [];
    public int $timeout = 90;
    public array $flags = [];
}
