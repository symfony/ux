<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Tests\Documentation;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Image\ImageAsset;
use Symfony\UX\Image\Renderer\DefaultImageRenderer;
use Symfony\UX\Image\Renderer\ImageRenderOptions;
use Symfony\UX\Image\Test\ImageAssetFactory;
use Symfony\UX\Image\UrlGenerator\UrlGeneratorInterface;

final class DocumentationTest extends TestCase
{
    public function testThePublishedDocDirectoryContainsTheCompleteCorpus(): void
    {
        $root = \dirname(__DIR__, 2);
        $docRoot = self::publishedDocRoot();
        $requiredFiles = [
            'index.rst',
            'overview.md',
            'installation.md',
            'configuration.md',
            'processing.md',
            'rendering.md',
            'storage.md',
            'image-asset.md',
            'regeneration.md',
            'integrations.md',
            'debugging.md',
            'testing.md',
            'security.md',
            'architecture.md',
            'images/pipeline.svg',
        ];

        foreach ($requiredFiles as $file) {
            self::assertFileExists($docRoot.'/'.$file, \sprintf('The published documentation is missing "%s".', $file));
        }

        $legacyFiles = [];
        $legacyDir = $root.'/docs';
        if (is_dir($legacyDir)) {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($legacyDir));
            foreach ($iterator as $file) {
                if (!$file instanceof \SplFileInfo) {
                    throw new \RuntimeException('The legacy documentation directory cannot be inspected.');
                }

                if ($file->isFile()) {
                    $legacyFiles[] = $file->getPathname();
                }
            }
        }

        self::assertSame([], $legacyFiles, 'Documentation files must live under the configured doc_dir.');
    }

    public function testEveryLocalDocumentationLinkStaysInsideThePublishedDocDirectory(): void
    {
        $root = \dirname(__DIR__, 2);
        $docRoot = self::publishedDocRoot();

        $publishedFiles = glob($docRoot.'/*.{md,rst}', \GLOB_BRACE);
        if (false === $publishedFiles) {
            throw new \RuntimeException('The published documentation directory cannot be inspected.');
        }

        $files = [$root.'/README.md', ...$publishedFiles];
        foreach ($files as $file) {
            $contents = file_get_contents($file);
            self::assertNotFalse($contents);

            preg_match_all('/\[[^\]]*]\(([^)]+)\)/', $contents, $markdownMatches);
            preg_match_all('/`[^`]+ <([^>]+)>`_/', $contents, $rstMatches);

            foreach ([...$markdownMatches[1], ...$rstMatches[1]] as $target) {
                if (preg_match('/^(?:https?:\/\/|mailto:|#)/', $target)) {
                    continue;
                }

                $path = explode('#', $target, 2)[0];
                $resolved = realpath(\dirname($file).'/'.$path);

                self::assertNotFalse($resolved, \sprintf('Broken documentation link "%s" in "%s".', $target, $file));
                self::assertStringStartsWith(
                    $docRoot.\DIRECTORY_SEPARATOR,
                    $resolved,
                    \sprintf('Documentation link "%s" in "%s" escapes doc_dir.', $target, $file),
                );
            }
        }
    }

    public function testDocumentedResponsiveHtmlMatchesTheRealRenderer(): void
    {
        $asset = ImageAssetFactory::responsive(
            formats: ['avif', 'webp', 'jpeg'],
            widths: [300, 600, 1200],
        );
        $urls = new class implements UrlGeneratorInterface {
            public function generateAssetUrl(ImageAsset $asset): string
            {
                return '/media'.$asset->path;
            }

            public function generateVariantUrl(ImageAsset $asset, array $variant): string
            {
                $path = $variant['path'] ?? null;
                if (!\is_string($path)) {
                    throw new \UnexpectedValueException('A documented image variant must contain a path.');
                }

                return '/media'.$path;
            }
        };
        $rendered = new DefaultImageRenderer($urls)->render(
            $asset,
            new ImageRenderOptions(alt: 'Product photo'),
        );

        self::assertSame(
            self::normalizeHtml($rendered->toHtml()),
            self::normalizeHtml($this->documentedFixture('responsive-picture')),
        );
        self::assertSame(
            self::normalizeHtml($rendered->toImgHtml()),
            self::normalizeHtml($this->documentedFixture('responsive-img')),
        );
    }

    private function documentedFixture(string $name): string
    {
        $contents = file_get_contents(self::publishedDocRoot().'/rendering.md');
        if (false === $contents) {
            throw new \RuntimeException('The rendering guide cannot be read.');
        }

        $matched = preg_match(
            '/<!-- fixture: '.preg_quote($name, '/').' -->\s*```html\s*(.*?)\s*```/s',
            $contents,
            $matches,
        );
        if (1 !== $matched) {
            throw new \RuntimeException(\sprintf('The documented HTML fixture "%s" does not exist.', $name));
        }

        return $matches[1];
    }

    private static function normalizeHtml(string $html): string
    {
        $html = preg_replace('/>\s+</', '><', trim($html));
        if (null === $html) {
            throw new \RuntimeException('The HTML fixture cannot be normalized.');
        }

        $html = preg_replace('/\s+/', ' ', $html);
        if (null === $html) {
            throw new \RuntimeException('The HTML fixture cannot be normalized.');
        }

        return $html;
    }

    private static function publishedDocRoot(): string
    {
        $root = \dirname(__DIR__, 2);
        $bundleConfiguration = file_get_contents($root.'/.symfony.bundle.yaml');
        if (false === $bundleConfiguration) {
            throw new \RuntimeException('The bundle metadata cannot be read.');
        }

        $matched = preg_match('/^doc_dir:\s*[\'"]?([^\'"\s]+)[\'"]?\s*$/m', $bundleConfiguration, $matches);
        if (1 !== $matched || !isset($matches[1])) {
            throw new \RuntimeException('The bundle metadata must declare doc_dir.');
        }

        $docRoot = realpath($root.'/'.$matches[1]);
        if (false === $docRoot) {
            throw new \RuntimeException('The configured doc_dir does not exist.');
        }

        return $docRoot;
    }
}
