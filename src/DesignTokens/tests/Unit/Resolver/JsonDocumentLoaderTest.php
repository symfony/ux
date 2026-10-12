<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DesignTokens\Tests\Unit\Resolver;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\UX\DesignTokens\Exception\RuntimeException;
use Symfony\UX\DesignTokens\Resolver\JsonDocumentLoader;
use Symfony\UX\DesignTokens\Tests\Fixtures\TemporaryDirectory;

#[CoversClass(JsonDocumentLoader::class)]
final class JsonDocumentLoaderTest extends TestCase
{
    private string $fixtureDir;
    private TemporaryDirectory $directory;

    protected function setUp(): void
    {
        $this->fixtureDir = \dirname(__DIR__, 2).'/Fixtures';
        $this->directory = new TemporaryDirectory();
    }

    protected function tearDown(): void
    {
        $this->directory->remove();
    }

    public function testLoadReturnsDecodedArray(): void
    {
        $loader = new JsonDocumentLoader();
        $data = $loader->load($this->fixtureDir.'/base.tokens.json');

        self::assertIsArray($data);
        self::assertArrayHasKey('color', $data);
    }

    public function testLoadPreservesNestedStructure(): void
    {
        $loader = new JsonDocumentLoader();
        $data = $loader->load($this->fixtureDir.'/base.tokens.json');

        self::assertArrayHasKey('brand', $data['color']);
        self::assertSame(['colorSpace' => 'srgb', 'components' => [0.231, 0.51, 0.965]], $data['color']['brand']['primary']['$value']);
    }

    public function testLoadWithBasepathPrependsForRelativeUri(): void
    {
        $loader = new JsonDocumentLoader($this->fixtureDir);
        $data = $loader->load('base.tokens.json');

        self::assertIsArray($data);
        self::assertArrayHasKey('color', $data);
    }

    public function testLoadWithAbsolutePathIgnoresBasePath(): void
    {
        $loader = new JsonDocumentLoader('/some/other/base');
        $data = $loader->load($this->fixtureDir.'/base.tokens.json');

        self::assertIsArray($data);
    }

    public function testLoadThrowsForMissingFile(): void
    {
        $loader = new JsonDocumentLoader();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/not found/i');

        $loader->load('/does/not/exist/tokens.json');
    }

    /** @param array<array-key, mixed> $expected */
    #[DataProvider('objectDocuments')]
    public function testLoadsAJsonObject(string $contents, array $expected): void
    {
        self::assertSame($expected, new JsonDocumentLoader()->load($this->directory->write('tokens.json', $contents)));
    }

    /** @return iterable<string, array{string, array<array-key, mixed>}> */
    public static function objectDocuments(): iterable
    {
        yield 'empty object' => ['{}', []];
        yield 'numeric names' => ['{"0": {"$type": "number", "$value": 1}}', [['$type' => 'number', '$value' => 1]]];
    }

    /** @param class-string<\Throwable> $exception */
    #[DataProvider('invalidDocuments')]
    public function testRejectsAFileThatIsNotAJsonObject(string $contents, string $exception, string $message): void
    {
        $path = $this->directory->write('tokens.json', $contents);

        $this->expectException($exception);
        $this->expectExceptionMessage($message);

        new JsonDocumentLoader()->load($path);
    }

    /** @return iterable<string, array{string, class-string<\Throwable>, string}> */
    public static function invalidDocuments(): iterable
    {
        yield 'invalid JSON' => ['{ invalid json }', RuntimeException::class, 'Invalid JSON in design token file'];
        yield 'JSON scalar' => ['42', \RuntimeException::class, 'must contain a JSON object'];
        yield 'JSON list' => ['[1, 2]', RuntimeException::class, 'must contain a JSON object'];
    }

    public function testWithoutAllowedRootsAnyReadablePathIsLoaded(): void
    {
        $loader = new JsonDocumentLoader($this->fixtureDir);

        self::assertIsArray($loader->load('color-scheme/../base.tokens.json'));
    }

    public function testAllowedRootsRejectADocumentClimbingOutOfThem(): void
    {
        $loader = new JsonDocumentLoader($this->fixtureDir, [$this->fixtureDir.'/color-scheme']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('resolves outside');

        $loader->load('color-scheme/../base.tokens.json');
    }

    public function testAllowedRootsAcceptADocumentInsideThem(): void
    {
        $loader = new JsonDocumentLoader($this->fixtureDir, [$this->fixtureDir]);

        self::assertIsArray($loader->load('base.tokens.json'));
    }

    public function testAllowedRootsStillReportAMissingFileAsMissing(): void
    {
        $loader = new JsonDocumentLoader($this->fixtureDir, [$this->fixtureDir]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/not found/i');

        $loader->load('nope.tokens.json');
    }

    public function testAllowedRootsFollowSymlinksSoAnInstalledPackageStaysReachable(): void
    {
        symlink($this->fixtureDir, $linked = $this->directory->path('link'));

        $loader = new JsonDocumentLoader('', [realpath($this->fixtureDir)]);

        self::assertIsArray($loader->load($linked.'/base.tokens.json'));
    }

    public function testRootsAreComparedThroughTheirRealPath(): void
    {
        $directory = $this->directory->path();
        $this->directory->write('ok.json', '{}');

        self::assertSame([], new JsonDocumentLoader('', [$directory])->load(realpath($directory).'/ok.json'));
        self::assertSame([], new JsonDocumentLoader('', [realpath($directory) ?: $directory])->load($directory.'/ok.json'));
    }
}
