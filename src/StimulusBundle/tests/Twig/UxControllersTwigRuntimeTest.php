<?php

/*
 * This file is part of the Symfony StimulusBundle package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\StimulusBundle\Tests\Twig;

use PHPUnit\Framework\TestCase;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\AssetMapper\MappedAsset;
use Symfony\UX\StimulusBundle\AssetMapper\ControllersMapGenerator;
use Symfony\UX\StimulusBundle\Twig\UxControllersTwigRuntime;
use Symfony\UX\StimulusBundle\Ux\UxPackageReader;

class UxControllersTwigRuntimeTest extends TestCase
{
    public function testRenderLinkTags()
    {
        $controllersMapGenerator = $this->createMock(ControllersMapGenerator::class);
        $controllersMapGenerator->expects($this->any())
            ->method('getControllersJsonPath')
            ->willReturn(__DIR__.'/../fixtures/assets/controllers.json')
        ;

        $assetMapper = $this->createMock(AssetMapperInterface::class);
        $assetMapper->expects($this->any())
            ->method('getAsset')
            ->willReturnCallback(function ($path) {
                if (str_starts_with($path, 'in/asset/mapper')) {
                    return new MappedAsset(basename($path), publicPath: '/assets/mapper/'.basename($path));
                }

                if (str_starts_with($path, '@fake-vendor/ux-package1')) {
                    return new MappedAsset('fake-vendor/ux-package1/'.basename($path), publicPath: '/assets/@fake-vendor/ux-package1/'.basename($path));
                }

                return null;
            });

        $runtime = new UxControllersTwigRuntime(
            $controllersMapGenerator,
            $assetMapper,
            new UxPackageReader(__DIR__.'/../fixtures'),
            __DIR__.'/../fixtures'
        );

        $this->assertStringNotContainsString(
            'controller_first.css',
            $runtime->renderLinkTags()
        );
        $this->assertStringContainsString(
            'href="/assets/mapper/controller_second1.css"',
            $runtime->renderLinkTags()
        );
        $this->assertStringNotContainsString(
            'controller_second2.css',
            $runtime->renderLinkTags()
        );
        $this->assertStringContainsString(
            'href="/assets/@fake-vendor/ux-package1/styles.css"',
            $runtime->renderLinkTags()
        );
        $this->assertStringContainsString(
            'href="https://cdn.jsdelivr.net/npm/needed-vendor@3.2.0/file.css"',
            $runtime->renderLinkTags()
        );
        $this->assertStringContainsString(
            'href="https://cdn.jsdelivr.net/npm/scoped/needed-vendor@1.2.3/the/file2.css"',
            $runtime->renderLinkTags()
        );

        // loop through a controllers.json file with several autoimports
        // cases for the assets:
        //   1) asset path is in the pipeline
        //   2) path starts with the "package name" (try with scoped and non-scoped)
        //      then look right inside the package
        //   3) look inside importmap.php for a "url" package and map to jsdelivr
    }
}
