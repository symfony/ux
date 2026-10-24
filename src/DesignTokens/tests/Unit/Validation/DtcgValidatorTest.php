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
use Symfony\UX\DesignTokens\Resolver\ArrayDocumentLoader;
use Symfony\UX\DesignTokens\Resolver\JsonDocumentLoader;
use Symfony\UX\DesignTokens\Resolver\TokenTreeBuilder;
use Symfony\UX\DesignTokens\Tests\Fixtures\TemporaryDirectory;
use Symfony\UX\DesignTokens\Validation\DtcgValidator;

#[CoversClass(DtcgValidator::class)]
final class DtcgValidatorTest extends TestCase
{
    private DtcgValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new DtcgValidator(new TokenTreeBuilder(new JsonDocumentLoader()));
    }

    public function testValidatesTokenAndResolverDocuments(): void
    {
        $this->validator->validateJson(self::tokens());

        $tokenPath = \dirname(__DIR__, 2).'/Integration/Fixtures/app.tokens.json';
        $resolverPath = \dirname(__DIR__, 2).'/Integration/Fixtures/theme.resolver.json';
        $this->validator->validateFile($tokenPath);
        $this->validator->validateFile($resolverPath);

        $resolverJson = (string) file_get_contents($resolverPath);
        $this->validator->validateJson($resolverJson, $resolverPath, \dirname($resolverPath));
        self::addToAssertionCount(4);
    }

    public function testReadsTheDocumentsAResolverReferencesThroughTheLoader(): void
    {
        $loader = new ArrayDocumentLoader(['parts.json' => [
            'sets' => ['base' => ['sources' => [['c' => ['$type' => 'number', '$value' => 1]]]]],
        ]]);
        $validator = new DtcgValidator(new TokenTreeBuilder($loader), documentLoader: $loader);

        self::assertSame([], $validator->validate([
            'version' => '2025.10',
            'resolutionOrder' => [['$ref' => 'parts.json#/sets/base']],
        ], kind: 'resolver'));
    }

    public function testRejectsInvalidJsonTopLevelAndSemantics(): void
    {
        foreach ([
            ['{', 'Invalid JSON'],
            ['[]', 'JSON object'],
            ['{"bad":{"$type":"dimension","$value":"12px"}}', 'structured dimension'],
        ] as [$json, $message]) {
            try {
                $this->validator->validateJson($json);
                self::fail('Expected validation to fail.');
            } catch (\RuntimeException|\InvalidArgumentException $error) {
                self::assertStringContainsString($message, $error->getMessage());
            }
        }
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

            $this->validator->validateFile($path);
        } finally {
            $directory->remove();
        }
    }

    /** @return iterable<string, array{string, ?string, bool, class-string<\Throwable>, string}> */
    public static function fileErrors(): iterable
    {
        yield 'missing file' => ['missing.tokens.json', null, true, \RuntimeException::class, 'not found'];
        yield 'unsupported extension' => ['tokens.json', self::tokens(), true, \InvalidArgumentException::class, 'Expected a .tokens.json'];
        yield 'unreadable file' => ['unreadable.tokens', self::tokens(), false, \RuntimeException::class, 'Could not read'];
    }

    public function testRejectsAnInvalidResolverDefaultContext(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must match a context key');

        $this->validator->validate([
            'version' => '2025.10',
            'modifiers' => [
                'theme' => [
                    'contexts' => ['light' => [], 'dark' => []],
                    'default' => 'unknown',
                ],
            ],
            'resolutionOrder' => [['$ref' => '#/modifiers/theme']],
        ], kind: 'resolver');
    }

    public function testFollowsTheNormativeGradientRule(): void
    {
        $this->validator->validate([
            'gradient' => [
                '$type' => 'gradient',
                '$value' => [
                    ['color' => ['colorSpace' => 'srgb', 'components' => [0, 0, 0]], 'position' => -99],
                    ['color' => ['colorSpace' => 'srgb', 'components' => [1, 1, 1]], 'position' => 42],
                ],
            ],
        ]);

        self::addToAssertionCount(1);
    }

    private static function tokens(): string
    {
        return '{"color":{"brand":{"$type":"color","$value":{"colorSpace":"srgb","components":[0.2,0.4,0.8]}}}}';
    }

    public function testNumericTopLevelNamesAreValid(): void
    {
        self::assertSame([], $this->validator->validateJson('{"100":{"$type":"number","$value":1}}'));
    }

    public function testAnEmptyGroupIsValid(): void
    {
        self::assertSame([], $this->validator->validateJson('{"empty":{},"a":{"$type":"number","$value":1}}'));
    }
}
