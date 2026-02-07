<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Native;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\UX\Native\Configuration\Configuration;

final class ConfigurationBuilder
{
    /**
     * @var array<string, Configuration>
     */
    private array $configs = [];

    public function __construct(
        private readonly string $outputDir,
    ) {
    }

    public function add(string $url, Configuration $configuration): void
    {
        $this->configs[$url] = $configuration;
    }

    public function has(string $url): bool
    {
        return \array_key_exists($url, $this->configs);
    }

    public function get(string $pathInfo): Configuration
    {
        return $this->configs[$pathInfo] ?? throw new FileNotFoundException(\sprintf('Configuration not found for path: "%s".', $pathInfo));
    }

    public function build(): void
    {
        $filesystem = new Filesystem();
        foreach ($this->configs as $url => $configuration) {
            $path = rtrim($this->outputDir, '/').'/'.ltrim($url, '/');
            $filesystem->dumpFile($path, $this->encode($configuration));
        }
    }

    private function encode(Configuration $configuration): string
    {
        return json_encode(
            $configuration->toArray(),
            \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT,
        );
    }
}
