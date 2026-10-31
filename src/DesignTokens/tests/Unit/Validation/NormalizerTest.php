<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DesignTokens\Tests\Unit\Validation;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\UX\DesignTokens\Resolver\JsonDocumentLoader;
use Symfony\UX\DesignTokens\Resolver\TokenTreeBuilder;
use Symfony\UX\DesignTokens\Tests\Fixtures\TemporaryDirectory;
use Symfony\UX\DesignTokens\Validation\DtcgValidator;
use Symfony\UX\DesignTokens\Validation\Normalizer;

#[CoversClass(Normalizer::class)]
final class NormalizerTest extends TestCase
{
    private Normalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new Normalizer(new DtcgValidator(new TokenTreeBuilder(new JsonDocumentLoader())));
    }

    public function testNormalizesWithoutDestroyingAuthoredStructure(): void
    {
        $json = '{"empty":{},"number":{"$type":"number","$value":1.0,"$description":"Échelle"},"alias":{"$type":"number","$value":"{number}"}}';

        $normalized = $this->normalizer->normalize($json);

        self::assertStringContainsString('"empty": {}', $normalized);
        self::assertStringContainsString('"$value": 1.0', $normalized);
        self::assertStringContainsString('"$description": "Échelle"', $normalized);
        self::assertStringContainsString('"$value": "{number}"', $normalized);
        self::assertStringEndsWith("\n", $normalized);
        self::assertLessThan(strpos($normalized, '"number"'), strpos($normalized, '"empty"'));
    }

    public function testNormalizesTokenAndResolverFiles(): void
    {
        $tokenPath = \dirname(__DIR__, 2).'/Integration/Fixtures/app.tokens.json';
        $resolverPath = \dirname(__DIR__, 2).'/Integration/Fixtures/theme.resolver.json';

        self::assertStringContainsString('"color"', $this->normalizer->normalizeFile($tokenPath));
        self::assertStringContainsString('"resolutionOrder"', $this->normalizer->normalizeFile($resolverPath));
    }

    /** @param class-string<\Throwable> $exception */
    #[DataProvider('fileErrors')]
    public function testReportsFileErrors(string $name, ?string $contents, bool $readable, string $exception, string $message): void
    {
        $directory = new TemporaryDirectory();
        $path = null === $contents ? $directory->path($name) : $directory->write($name, $contents);
        if (!$readable) {
            new Filesystem()->chmod($path, 0o000);
        }

        try {
            $this->expectException($exception);
            $this->expectExceptionMessage($message);

            $this->normalizer->normalizeFile($path);
        } finally {
            $directory->remove();
        }
    }

    /** @return iterable<string, array{string, ?string, bool, class-string<\Throwable>, string}> */
    public static function fileErrors(): iterable
    {
        yield 'missing file' => ['missing.tokens.json', null, true, \RuntimeException::class, 'not found'];
        yield 'unsupported extension' => ['tokens.json', '{"empty":{}}', true, \InvalidArgumentException::class, 'Expected a .tokens.json'];
        yield 'unreadable file' => ['unreadable.tokens', '{"empty":{}}', false, \RuntimeException::class, 'Could not read'];
    }
}
