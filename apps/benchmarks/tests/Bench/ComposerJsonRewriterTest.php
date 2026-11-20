<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Bench;

use App\Bench\ComposerJsonRewriter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ComposerJsonRewriterTest extends TestCase
{
    #[DataProvider('provideRefs')]
    public function testATagGivesItsVersionAndAnythingElseThe3xBranch(string $ref, string $expected): void
    {
        $version = ComposerJsonRewriter::versionForRef($ref);

        self::assertSame($expected, $version);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideRefs(): iterable
    {
        yield 'v3.6.0' => ['v3.6.0', '3.6.0'];
        yield '3.7.2' => ['3.7.2', '3.7.2'];
        yield '3.x' => ['3.x', '3.x-dev'];
        yield '8550a137b04' => ['8550a137b04', '3.x-dev'];
    }

    public function testItPointsThePresentPackagesToTheWorktreeAndDropsTheMissingOnes(): void
    {
        $composerJson = [
            'name' => 'symfony/ux-benchmarks-app',
            'require' => ['php' => '>=8.4', 'symfony/ux-css' => '3.x-dev', 'symfony/ux-map' => '3.x-dev', 'symfony/stimulus-bundle' => '3.x-dev', 'twig/twig' => '^3.24'],
            'require-dev' => ['phpunit/phpunit' => '^12.0'],
            'autoload-dev' => ['psr-4' => ['App\\Tests\\' => 'tests/']],
        ];
        $paths = ['symfony/ux-map' => '/w/src/Map', 'symfony/stimulus-bundle' => '/w/src/StimulusBundle'];

        $rewritten = ComposerJsonRewriter::rewrite($composerJson, $paths, '3.6.0');

        self::assertSame(['php' => '>=8.4', 'symfony/ux-map' => '@dev', 'symfony/stimulus-bundle' => '@dev', 'twig/twig' => '^3.24'], $rewritten['require']);
        self::assertSame([
            ['type' => 'path', 'url' => '/w/src/Map', 'options' => ['symlink' => false, 'versions' => ['symfony/ux-map' => '3.6.0']]],
            ['type' => 'path', 'url' => '/w/src/StimulusBundle', 'options' => ['symlink' => false, 'versions' => ['symfony/stimulus-bundle' => '3.6.0']]],
        ], $rewritten['repositories']);
        self::assertArrayNotHasKey('require-dev', $rewritten);
        self::assertArrayNotHasKey('autoload-dev', $rewritten);
        self::assertSame('symfony/ux-benchmarks-app', $rewritten['name']);
    }

    public function testItKeepsTheExistingRepositoriesAndAppendsTheWorktreeOnes(): void
    {
        $existing = ['type' => 'vcs', 'url' => 'https://example.com/foo.git'];
        $composerJson = [
            'require' => ['symfony/ux-map' => '3.x-dev'],
            'repositories' => [$existing],
        ];
        $paths = ['symfony/ux-map' => '/w/src/Map'];

        $rewritten = ComposerJsonRewriter::rewrite($composerJson, $paths, '3.6.0');

        self::assertSame([
            $existing,
            ['type' => 'path', 'url' => '/w/src/Map', 'options' => ['symlink' => false, 'versions' => ['symfony/ux-map' => '3.6.0']]],
        ], $rewritten['repositories']);
    }

    public function testItDoesNotCreateRepositoriesWhenThereIsNoUxPackage(): void
    {
        $composerJson = ['require' => ['php' => '>=8.4', 'twig/twig' => '^3.24']];

        $rewritten = ComposerJsonRewriter::rewrite($composerJson, [], '3.6.0');

        self::assertArrayNotHasKey('repositories', $rewritten);
        self::assertSame(['php' => '>=8.4', 'twig/twig' => '^3.24'], $rewritten['require']);
    }
}
