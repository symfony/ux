<?php

namespace Symfony\UX\LazyImage\Tests\Fixtures\BlurHash;

final class LoggedFetchImageContent
{
    public array $logs = [];

    public function __invoke(string $filename): string
    {
        $this->logs[] = $filename;

        return file_get_contents($filename);
    }
}
