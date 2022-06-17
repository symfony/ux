<?php

namespace App\Service;

use App\Util\SourceCleaner;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class CustomTwigExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('cleanup_php_file', [$this, 'cleanupPhpFile'], [
                'is_safe' => ['html'],
            ]),
        ];
    }

    public function cleanupPhpFile(string $contents, bool $removeClass = false): string
    {
        return SourceCleaner::cleanupPhpFile($contents, $removeClass);
    }
}
