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
use Symfony\UX\DesignTokens\Exception\ResolverException;
use Symfony\UX\DesignTokens\Resolver\JsonDocumentLoader;
use Symfony\UX\DesignTokens\Resolver\ResolverDocument;
use Symfony\UX\DesignTokens\Resolver\TokenTreeBuilder;
use Symfony\UX\DesignTokens\Tests\Fixtures\ResolverDocuments;
use Symfony\UX\DesignTokens\Token\NumberToken;

#[CoversClass(ResolverDocument::class)]
final class ResolverConformanceTest extends TestCase
{
    public function testTheRuntimeUsesTheStableVersionIdentifier(): void
    {
        self::assertSame('2025.10', ResolverDocument::VERSION);
    }

    #[DataProvider('rejectedVersionIdentifiers')]
    public function testRejectsTheVersionIdentifiersTheReportContradictsItselfWith(string $version): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/version "2025\.10"/');

        ResolverDocuments::merged(new ResolverDocument([
            'version' => $version,
            'resolutionOrder' => [['$ref' => '#/sets/base']],
            'sets' => ['base' => ['sources' => [['a' => ['$type' => 'number', '$value' => 1]]]]],
        ], ''));
    }

    /** @return iterable<string, array{string}> */
    public static function rejectedVersionIdentifiers(): iterable
    {
        yield 'root property table' => ['2025-10-01'];
        yield 'section 4.1.2' => ['2025-11-01'];
    }

    public function testAcceptsTheCompleteNormativeDocumentSurface(): void
    {
        $document = new ResolverDocument([
            '$schema' => 'https://www.designtokens.org/schemas/2025.10/resolver.json',
            'name' => 'Complete resolver',
            'version' => '2025.10',
            'description' => 'Exercises optional metadata.',
            '$defs' => ['unused' => ['arbitrary' => true]],
            'sets' => [
                'empty' => [
                    'description' => 'Empty source arrays are valid.',
                    '$extensions' => ['example.com/tool' => ['stable' => true]],
                    'sources' => [],
                ],
            ],
            'modifiers' => [
                'optional' => [
                    '$extensions' => ['example.com/tool' => ['stable' => true]],
                    'contexts' => ['off' => [], 'on' => []],
                    'default' => 'off',
                ],
            ],
            'resolutionOrder' => [
                ['$ref' => '#/sets/empty'],
                ['$ref' => '#/modifiers/optional'],
            ],
        ]);

        self::assertSame([], ResolverDocuments::merged($document));
        self::assertCount(2, $document->getPermutations());
    }

    public function testResolvesEscapedAndPercentEncodedJsonPointerSegments(): void
    {
        $document = new ResolverDocument([
            'version' => '2025.10',
            'sets' => [
                'a/b~c' => ['sources' => [['escaped' => ['$type' => 'number', '$value' => 1]]]],
                'space name' => ['sources' => [['encoded' => ['$type' => 'number', '$value' => 2]]]],
            ],
            'resolutionOrder' => [
                ['$ref' => '#/sets/a~1b~0c'],
                ['$ref' => '#/sets/space%20name'],
            ],
        ]);

        self::assertSame(1, ResolverDocuments::merged($document)['escaped']['$value'] ?? null);
        self::assertSame(2, ResolverDocuments::merged($document)['encoded']['$value'] ?? null);
    }

    public function testRejectsAReferenceToItsParentNode(): void
    {
        $document = new ResolverDocument([
            'version' => '2025.10',
            'sets' => [
                'parent' => ['sources' => [['$ref' => '#/sets/parent']]],
            ],
            'resolutionOrder' => [['$ref' => '#/sets/parent']],
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Circular resolver reference');

        ResolverDocuments::sources($document);
    }

    public function testResolvesAReferenceObjectThatTargetsAnotherReferenceObject(): void
    {
        $document = new ResolverDocument([
            'version' => '2025.10',
            '$defs' => [
                'target' => ['type' => 'set', 'name' => 'generic', 'sources' => [
                    ['value' => ['$type' => 'number', '$value' => 1]],
                ]],
                'alias' => ['$ref' => '#/$defs/target'],
            ],
            'resolutionOrder' => [['$ref' => '#/$defs/alias']],
        ]);

        self::assertSame(1, ResolverDocuments::merged($document)['value']['$value'] ?? null);
    }

    public function testRejectsCaseInsensitiveModifierAndInputAmbiguities(): void
    {
        $modifier = ['contexts' => ['light' => [], 'dark' => []]];
        $document = new ResolverDocument([
            'version' => '2025.10',
            'modifiers' => ['theme' => $modifier, 'THEME' => $modifier],
            'resolutionOrder' => [
                ['$ref' => '#/modifiers/theme'],
                ['$ref' => '#/modifiers/THEME'],
            ],
        ]);

        try {
            ResolverDocuments::sources($document, ['theme' => 'light']);
            self::fail('Case-insensitive modifier names must not be ambiguous.');
        } catch (\InvalidArgumentException $exception) {
            self::assertStringContainsString('differ only by case', $exception->getMessage());
        }

        $document = new ResolverDocument([
            'version' => '2025.10',
            'modifiers' => ['theme' => $modifier],
            'resolutionOrder' => [['$ref' => '#/modifiers/theme']],
        ]);

        try {
            ResolverDocuments::sources($document, ['theme' => 'light', 'THEME' => 'dark']);
            self::fail('The same modifier input must not be accepted twice with different casing.');
        } catch (ResolverException $exception) {
            self::assertStringContainsString('provided more than once', $exception->getMessage());
        }
    }

    public function testOrderingPrecedesAliasResolutionAndPreservesTokenProperties(): void
    {
        $document = new ResolverDocument([
            'version' => '2025.10',
            'sets' => [
                'base' => ['sources' => [[
                    'source' => ['$type' => 'number', '$value' => 1],
                    'alias' => [
                        '$type' => 'number',
                        '$value' => '{source}',
                        '$description' => 'Resolved after all sources are merged.',
                        '$deprecated' => 'Use source.',
                        '$extensions' => ['example.com/tool' => ['preserved' => true]],
                    ],
                ]]],
                'override' => ['sources' => [[
                    'source' => ['$type' => 'number', '$value' => 2],
                ]]],
            ],
            'resolutionOrder' => [
                ['$ref' => '#/sets/base'],
                ['$ref' => '#/sets/override'],
            ],
        ]);

        $resolved = new TokenTreeBuilder()->resolveSources($document->sourceDescriptors());

        self::assertInstanceOf(NumberToken::class, $resolved['alias']);
        self::assertSame(2, $resolved['alias']->getValue());
        self::assertSame('Resolved after all sources are merged.', $resolved['alias']->getDescription());
        self::assertTrue($resolved['alias']->isDeprecated());
        self::assertSame('Use source.', $resolved['alias']->getDeprecationMessage());
        self::assertSame(['example.com/tool' => ['preserved' => true]], $resolved['alias']->getExtensions());
    }

    public function testEveryPermutationOfThePinnedPositiveFixtureResolvesAsDtcgTokens(): void
    {
        $path = self::resourcePath('resolver-complete.resolver.json');
        $document = new ResolverDocument(new JsonDocumentLoader()->load($path), \dirname($path));
        $resolver = new TokenTreeBuilder();

        foreach ($document->getPermutations() as $inputs) {
            $resolved = $resolver->resolveSources($document->sourceDescriptors($inputs));

            self::assertInstanceOf(NumberToken::class, $resolved['color']['surface']);
            self::assertInstanceOf(NumberToken::class, $resolved['component']['precedence']);
        }
    }

    /** @param array<string, mixed> $document */
    #[DataProvider('invalidDocumentProvider')]
    public function testRejectsNormativeInvalidDocuments(array $document, string $message): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        new ResolverDocument($document);
    }

    /** @return iterable<string, array{array<string, mixed>, string}> */
    public static function invalidDocumentProvider(): iterable
    {
        $cases = json_decode((string) file_get_contents(self::resourcePath('resolver-invalid-cases.json')), true, 512, \JSON_THROW_ON_ERROR);

        foreach ($cases as $name => $case) {
            yield $name => [$case['document'], $case['message']];
        }
    }

    private static function resourcePath(string $file): string
    {
        return \dirname(__DIR__, 2).'/Fixtures/dtcg/'.$file;
    }
}
