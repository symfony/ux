<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\StimulusBundle\Tests\AssetMapper;

use Composer\InstalledVersions;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\UX\StimulusBundle\Tests\fixtures\StimulusTestKernel;
use Zenstruck\Browser\Test\HasBrowser;

class StimulusControllerLoaderFunctionalTest extends WebTestCase
{
    use HasBrowser;

    public function testFullApplicationLoad(): void
    {
        if (InstalledVersions::getVersion('symfony/framework-bundle') < '6.3') {
            $this->markTestSkipped('This test requires symfony/framework-bundle 6.3+');
        }

        $filesystem = new Filesystem();
        $filesystem->remove(__DIR__.'/../fixtures/var/cache');

        $crawler = $this->browser()
            ->get('/')
            ->crawler();

        $importMapJson = $crawler->filter('script[type="importmap"]')->html();
        $importMap = json_decode($importMapJson, true);
        $importMapKeys = array_keys($importMap['imports']);

        // filter out items ending in .css
        $importMapJsKeys = array_filter($importMapKeys, static function ($key) {
            return '.css' !== substr($key, -4);
        });
        $importMapCssKeys = array_filter($importMapKeys, static function ($key) {
            return '.css' === substr($key, -4);
        });
        sort($importMapJsKeys);
        $this->assertSame([
            // 2x import from loader.js (which is aliased to @symfony/stimulus-bundle via importmap)
            '/assets/@symfony/stimulus-bundle/controllers.js',
            '/assets/@symfony/stimulus-bundle/core.js',
            // 7x from "controllers" (hello is overridden)
            '/assets/controllers/bye_controller.js',
            '/assets/controllers/hello-with-dashes-controller.js',
            '/assets/controllers/hello_with_underscores-controller.js',
            '/assets/controllers/preserved-comment_controller.js',
            '/assets/controllers/subdir/deeper-controller.js',
            '/assets/controllers/subdir/deeper-with-dashes-controller.js',
            '/assets/controllers/subdir/deeper_with_underscores-controller.js',
            '/assets/controllers/typescript-controller.ts',
            // 2x from UX packages, which are enabled in controllers.json
            '/assets/fake-vendor/ux-package1/package-controller-second.js',
            '/assets/fake-vendor/ux-package2/package-hello-controller.js',
            // 4x from more-controllers
            '/assets/more-controllers/excluded-controller.js',
            '/assets/more-controllers/hello-controller.js',
            '/assets/more-controllers/minified-controller.js',
            '/assets/more-controllers/other-controller.js',
            // 5x from importmap.php
            '@hotwired/stimulus',
            '@scoped/needed-vendor',
            '@symfony/stimulus-bundle',
            'app',
            'needed-vendor',
        ], array_values($importMapJsKeys));

        // the autoimport CSS
        $this->assertSame([
            '/assets/in/asset/mapper/controller_second1.css',
            // enabled => false
            // '/assets/in/asset/mapper/controller_second2.css',
            '/assets/fake-vendor/ux-package1/styles.css',

            // 2x from importmap.php: so they should, of course, be here.
            // But our compiler should not add "path-based" entries
            // '/assets/vendor/needed-vendor/file.css',
            // '/assets/vendor/@scoped/needed-vendor/the/file2.css',
            'needed-vendor/file.css',
            '@scoped/needed-vendor/the/file2.css',
        ], array_values($importMapCssKeys));

        // "app" is the entry. So, all non-lazy controllers should be preloaded:
        $preLoadHrefs = $crawler->filter('link[rel="modulepreload"]')->each(static function ($link) {
            return $link->attr('href');
        });
        $this->assertCount(13, $preLoadHrefs);
        sort($preLoadHrefs);
        $this->assertStringStartsWith('/assets/@symfony/stimulus-bundle/controllers-', $preLoadHrefs[0]);
        $this->assertStringStartsWith('/assets/@symfony/stimulus-bundle/core-', $preLoadHrefs[1]);
        $this->assertStringStartsWith('/assets/@symfony/stimulus-bundle/loader-', $preLoadHrefs[2]);
        $this->assertStringStartsWith('/assets/controllers/hello-with-dashes-controller-', $preLoadHrefs[4]);
        $this->assertStringStartsWith('/assets/controllers/hello_with_underscores-controller-', $preLoadHrefs[5]);
        $this->assertStringStartsWith('/assets/controllers/subdir/deeper-controller-', $preLoadHrefs[6]);
        $this->assertStringStartsWith('/assets/controllers/subdir/deeper-with-dashes-controller-', $preLoadHrefs[7]);
        $this->assertStringStartsWith('/assets/controllers/subdir/deeper_with_underscores-controller-', $preLoadHrefs[8]);
        $this->assertStringStartsWith('/assets/controllers/typescript-controller-', $preLoadHrefs[9]);
        $this->assertStringStartsWith('/assets/fake-vendor/ux-package2/package-hello-controller-', $preLoadHrefs[10]);
        $this->assertStringStartsWith('/assets/more-controllers/hello-controller-', $preLoadHrefs[11]);
        $this->assertStringStartsWith('/assets/vendor/@hotwired/stimulus/stimulus.index', $preLoadHrefs[12]);
    }

    public function testApplicationLoadersPreloadOnlyTheirControllers(): void
    {
        self::removeApplicationsCache();

        $browser = $this->browser(['environment' => 'applications']);

        $frontPreloads = self::getPreloadHrefs($browser->get('/front')->crawler());
        self::assertPreloaded('/assets/@symfony/stimulus-bundle/core-', $frontPreloads);
        self::assertPreloaded('/assets/front/stimulus_loader-', $frontPreloads);
        self::assertPreloaded('/assets/front/controllers/menu_controller-', $frontPreloads);
        self::assertPreloaded('/assets/front/controllers/hello_controller-', $frontPreloads);
        self::assertPreloaded('/assets/controllers/hello-with-dashes-controller-', $frontPreloads);
        self::assertPreloaded('/assets/controllers/typescript-controller-', $frontPreloads);
        self::assertPreloaded('/assets/fake-vendor/ux-package2/package-hello-controller-', $frontPreloads);
        self::assertNotPreloaded('/assets/fake-vendor/ux-package1/package-controller-second-', $frontPreloads);
        self::assertNotPreloaded('/assets/more-controllers/hello-controller-', $frontPreloads);
        self::assertNotPreloaded('/assets/controllers/bye_controller-', $frontPreloads);
        self::assertNotPreloaded('/assets/@symfony/stimulus-bundle/controllers-', $frontPreloads);
        self::assertNotPreloaded('/assets/admin/', $frontPreloads);

        $adminPreloads = self::getPreloadHrefs($browser->get('/admin')->crawler());
        self::assertPreloaded('/assets/@symfony/stimulus-bundle/core-', $adminPreloads);
        self::assertPreloaded('/assets/admin/stimulus_loader-', $adminPreloads);
        self::assertPreloaded('/assets/admin/controllers/dashboard_controller-', $adminPreloads);
        self::assertPreloaded('/assets/fake-vendor/ux-package2/package-hello-controller-', $adminPreloads);
        self::assertPreloaded('/assets/fake-vendor/ux-package1/package-controller-second-', $adminPreloads);
        self::assertNotPreloaded('/assets/admin/controllers/report_controller-', $adminPreloads);
        self::assertNotPreloaded('/assets/front/', $adminPreloads);
        self::assertNotPreloaded('/assets/controllers/', $adminPreloads);
        self::assertNotPreloaded('/assets/more-controllers/', $adminPreloads);
        self::assertNotPreloaded('/assets/@symfony/stimulus-bundle/controllers-', $adminPreloads);
    }

    public function testApplicationLoaderContents(): void
    {
        self::removeApplicationsCache();

        $adminLoader = self::getApplicationLoaderContent('admin');
        $this->assertStringContainsString('"dashboard": controller_', $adminLoader);
        $this->assertStringContainsString('"report": () => import(', $adminLoader);
        $this->assertStringNotContainsString('menu', $adminLoader);
        $this->assertMatchesRegularExpression('/"fake-vendor--ux-package1--controller-second": controller_\d+/', $adminLoader);
        $this->assertStringNotContainsString('.css', $adminLoader);

        $frontLoader = self::getApplicationLoaderContent('front');
        $this->assertStringContainsString('"menu": controller_', $frontLoader);
        $this->assertMatchesRegularExpression('/"hello": (controller_\d+)/', $frontLoader);
        preg_match('/"hello": (controller_\d+)/', $frontLoader, $matches);
        $this->assertStringContainsString(\sprintf('import %s from "./controllers/hello_controller.js";', $matches[1]), $frontLoader);
        $this->assertStringNotContainsString('more-controllers/hello-controller.js', $frontLoader);
    }

    public function testApplicationLoadersAreRecompiledWhenAControllerIsAdded(): void
    {
        self::removeApplicationsCache();

        $filesystem = new Filesystem();
        $temporaryController = __DIR__.'/../fixtures/assets/admin/controllers/temporary_controller.js';

        $this->assertStringNotContainsString('"temporary"', self::getApplicationLoaderContent('admin'));

        try {
            $filesystem->dumpFile($temporaryController, "import { Controller } from '@hotwired/stimulus';\n\nexport default class extends Controller {\n}\n");
            // symfony/config < 8.2 misses a file modified in the same second as the cache
            touch($temporaryController, time() + 1);

            $this->assertStringContainsString('"temporary": controller_', self::getApplicationLoaderContent('admin'));
        } finally {
            $filesystem->remove($temporaryController);
            self::ensureKernelShutdown();
        }
    }

    protected static function getKernelClass(): string
    {
        return StimulusTestKernel::class;
    }

    private static function removeApplicationsCache(): void
    {
        self::ensureKernelShutdown();

        new Filesystem()->remove(__DIR__.'/../fixtures/var/cache/applications');
    }

    private static function getApplicationLoaderContent(string $application): string
    {
        self::ensureKernelShutdown();
        self::bootKernel(['environment' => 'applications']);

        $assetMapper = self::getContainer()->get('asset_mapper');
        \assert($assetMapper instanceof AssetMapperInterface);

        $loader = $assetMapper->getAsset($application.'/stimulus_loader.js');
        self::assertNotNull($loader);

        return $loader->content;
    }

    /**
     * @return list<string>
     */
    private static function getPreloadHrefs(Crawler $crawler): array
    {
        return $crawler->filter('link[rel="modulepreload"]')->each(static fn (Crawler $link) => $link->attr('href'));
    }

    /**
     * @param list<string> $hrefs
     */
    private static function assertPreloaded(string $prefix, array $hrefs): void
    {
        self::assertNotEmpty(
            array_filter($hrefs, static fn (string $href) => str_starts_with($href, $prefix)),
            \sprintf('No preloaded module starts with "%s". Preloaded modules: "%s".', $prefix, implode('", "', $hrefs)),
        );
    }

    /**
     * @param list<string> $hrefs
     */
    private static function assertNotPreloaded(string $prefix, array $hrefs): void
    {
        self::assertSame(
            [],
            array_values(array_filter($hrefs, static fn (string $href) => str_starts_with($href, $prefix))),
            \sprintf('A preloaded module starts with "%s".', $prefix),
        );
    }
}
