<?php

namespace App\Services\Shoptok;

use Illuminate\Support\Facades\File;
use RuntimeException;

final class FixtureShoptokHtmlSource implements ShoptokHtmlSource
{
    public function __construct(private readonly string $basePath) {}

    public function fetch(string $relativePath): string
    {
        $absolutePath = rtrim($this->basePath, '/')
            . '/'
            . ltrim($relativePath, '/');

        if (!File::exists($absolutePath)) {
            throw new RuntimeException("Fixture not found: {$absolutePath}");
        }

        return File::get($absolutePath);
    }
}
