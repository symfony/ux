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
use Symfony\Component\AssetMapper\ImportMap\ImportMapGenerator;
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
            // 1x import from loader.js (which is aliased to @symfony/stimulus-bundle via importmap)
            '/assets/@symfony/stimulus-bundle/runtime.js',
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
        $this->assertCount(12, $preLoadHrefs);
        sort($preLoadHrefs);
        $this->assertStringStartsWith('/assets/@symfony/stimulus-bundle/loader-', $preLoadHrefs[0]);
        $this->assertStringStartsWith('/assets/@symfony/stimulus-bundle/runtime-', $preLoadHrefs[1]);
        $this->assertStringStartsWith('/assets/controllers/hello-with-dashes-controller-', $preLoadHrefs[3]);
        $this->assertStringStartsWith('/assets/controllers/hello_with_underscores-controller-', $preLoadHrefs[4]);
        $this->assertStringStartsWith('/assets/controllers/subdir/deeper-controller-', $preLoadHrefs[5]);
        $this->assertStringStartsWith('/assets/controllers/subdir/deeper-with-dashes-controller-', $preLoadHrefs[6]);
        $this->assertStringStartsWith('/assets/controllers/subdir/deeper_with_underscores-controller-', $preLoadHrefs[7]);
        $this->assertStringStartsWith('/assets/controllers/typescript-controller-', $preLoadHrefs[8]);
        $this->assertStringStartsWith('/assets/fake-vendor/ux-package2/package-hello-controller-', $preLoadHrefs[9]);
        $this->assertStringStartsWith('/assets/more-controllers/hello-controller-', $preLoadHrefs[10]);
        $this->assertStringStartsWith('/assets/vendor/@hotwired/stimulus/stimulus.index', $preLoadHrefs[11]);
        self::assertNotPreloaded('/assets/@symfony/stimulus-bundle/controllers-', $preLoadHrefs);
    }

    public function testApplicationModulesPreloadOnlyTheirControllers(): void
    {
        self::removeApplicationsCache();

        $browser = $this->browser(['environment' => 'applications']);

        $frontPreloads = self::getPreloadHrefs($browser->get('/front')->crawler());
        self::assertPreloaded('/assets/@symfony/stimulus-bundle/runtime-', $frontPreloads);
        self::assertPreloaded('/assets/front/stimulus_loader-', $frontPreloads);
        self::assertPreloaded('/assets/front/controllers/menu_controller-', $frontPreloads);
        self::assertPreloaded('/assets/front/controllers/hello_controller-', $frontPreloads);
        self::assertPreloaded('/assets/controllers/hello-with-dashes-controller-', $frontPreloads);
        self::assertPreloaded('/assets/controllers/typescript-controller-', $frontPreloads);
        self::assertPreloaded('/assets/fake-vendor/ux-package2/package-hello-controller-', $frontPreloads);
        self::assertNotPreloaded('/assets/fake-vendor/ux-package1/package-controller-second-', $frontPreloads);
        self::assertNotPreloaded('/assets/more-controllers/hello-controller-', $frontPreloads);
        self::assertNotPreloaded('/assets/controllers/bye_controller-', $frontPreloads);
        self::assertNotPreloaded('/assets/@symfony/stimulus-bundle/loader-', $frontPreloads);
        self::assertNotPreloaded('/assets/@symfony/stimulus-bundle/controllers-', $frontPreloads);
        self::assertNotPreloaded('/assets/in/asset/mapper/zones/', $frontPreloads);
        self::assertNotPreloaded('/assets/in/asset/mapper/shared/', $frontPreloads);
        self::assertNotPreloaded('/assets/partner', $frontPreloads);
        self::assertNotPreloaded('/assets/admin/', $frontPreloads);
        self::assertNotPreloaded('/assets/shop/', $frontPreloads);

        $adminPreloads = self::getPreloadHrefs($browser->get('/admin')->crawler());
        self::assertPreloaded('/assets/@symfony/stimulus-bundle/runtime-', $adminPreloads);
        self::assertPreloaded('/assets/admin/stimulus-', $adminPreloads);
        self::assertPreloaded('/assets/admin/controllers/dashboard_controller-', $adminPreloads);
        self::assertPreloaded('/assets/fake-vendor/ux-package2/package-hello-controller-', $adminPreloads);
        self::assertPreloaded('/assets/fake-vendor/ux-package1/package-controller-second-', $adminPreloads);
        self::assertNotPreloaded('/assets/admin/controllers/report_controller-', $adminPreloads);
        self::assertNotPreloaded('/assets/front/', $adminPreloads);
        self::assertNotPreloaded('/assets/shop/', $adminPreloads);
        self::assertNotPreloaded('/assets/controllers/', $adminPreloads);
        self::assertNotPreloaded('/assets/more-controllers/', $adminPreloads);
        self::assertNotPreloaded('/assets/@symfony/stimulus-bundle/loader-', $adminPreloads);
        self::assertNotPreloaded('/assets/@symfony/stimulus-bundle/controllers-', $adminPreloads);
        self::assertNotPreloaded('/assets/in/asset/mapper/zones/', $adminPreloads);
        self::assertNotPreloaded('/assets/in/asset/mapper/shared/', $adminPreloads);
        self::assertNotPreloaded('/assets/partner', $adminPreloads);

        $shopPreloads = self::getPreloadHrefs($browser->get('/shop')->crawler());
        self::assertPreloaded('/assets/@symfony/stimulus-bundle/runtime-', $shopPreloads);
        self::assertPreloaded('/assets/shop/stimulus-', $shopPreloads);
        self::assertPreloaded('/assets/shop/controllers/cart_controller-', $shopPreloads);
        self::assertPreloaded('/assets/controllers/hello-with-dashes-controller-', $shopPreloads);
        self::assertPreloaded('/assets/more-controllers/hello-controller-', $shopPreloads);
        self::assertNotPreloaded('/assets/admin/', $shopPreloads);
        self::assertNotPreloaded('/assets/front/', $shopPreloads);
        self::assertNotPreloaded('/assets/@symfony/stimulus-bundle/loader-', $shopPreloads);
        self::assertNotPreloaded('/assets/@symfony/stimulus-bundle/controllers-', $shopPreloads);
        self::assertNotPreloaded('/assets/in/asset/mapper/zones/', $shopPreloads);
        self::assertNotPreloaded('/assets/in/asset/mapper/shared/', $shopPreloads);
        self::assertNotPreloaded('/assets/partner', $shopPreloads);

        $globalPreloads = self::getPreloadHrefs($browser->get('/')->crawler());
        self::assertPreloaded('/assets/@symfony/stimulus-bundle/loader-', $globalPreloads);
        self::assertPreloaded('/assets/@symfony/stimulus-bundle/runtime-', $globalPreloads);
        self::assertPreloaded('/assets/controllers/hello-with-dashes-controller-', $globalPreloads);
        self::assertPreloaded('/assets/more-controllers/hello-controller-', $globalPreloads);
        self::assertPreloaded('/assets/fake-vendor/ux-package2/package-hello-controller-', $globalPreloads);
        self::assertNotPreloaded('/assets/fake-vendor/ux-package1/package-controller-second-', $globalPreloads);
        self::assertNotPreloaded('/assets/admin/', $globalPreloads);
        self::assertNotPreloaded('/assets/front/', $globalPreloads);
        self::assertNotPreloaded('/assets/shop/', $globalPreloads);
        self::assertNotPreloaded('/assets/account/', $globalPreloads);
        self::assertNotPreloaded('/assets/@symfony/stimulus-bundle/controllers-', $globalPreloads);
        self::assertNotPreloaded('/assets/in/asset/mapper/zones/', $globalPreloads);
        self::assertNotPreloaded('/assets/in/asset/mapper/shared/', $globalPreloads);
        self::assertNotPreloaded('/assets/partner', $globalPreloads);
    }

    public function testApplicationsCanShareControllersWithoutLoadingTheModuleOfEachOther(): void
    {
        self::removeApplicationsCache();

        $browser = $this->browser(['environment' => 'applications']);

        $backofficePreloads = self::getPreloadHrefs($browser->get('/backoffice')->crawler());
        self::assertPreloaded('/assets/in/asset/mapper/zones/backoffice/app-', $backofficePreloads);
        self::assertPreloaded('/assets/in/asset/mapper/zones/backoffice/boot/start-', $backofficePreloads);
        self::assertPreloaded('/assets/in/asset/mapper/zones/backoffice/bootstrap-', $backofficePreloads);
        self::assertPreloaded('/assets/@symfony/stimulus-bundle/runtime-', $backofficePreloads);
        self::assertPreloaded('/assets/in/asset/mapper/shared/controllers/notify_controller-', $backofficePreloads);
        self::assertPreloaded('/assets/admin/controllers/dashboard_controller-', $backofficePreloads);
        self::assertNotPreloaded('/assets/in/asset/mapper/shared/controllers/chart_controller-', $backofficePreloads);
        self::assertNotPreloaded('/assets/admin/controllers/report_controller-', $backofficePreloads);
        self::assertNotPreloaded('/assets/fake-vendor/', $backofficePreloads);
        self::assertNotPreloaded('/assets/admin/stimulus-', $backofficePreloads);
        self::assertNotPreloaded('/assets/admin-', $backofficePreloads);
        self::assertNotPreloaded('/assets/shop/', $backofficePreloads);
        self::assertNotPreloaded('/assets/front/', $backofficePreloads);
        self::assertNotPreloaded('/assets/partner', $backofficePreloads);
        self::assertNotPreloaded('/assets/controllers/', $backofficePreloads);
        self::assertNotPreloaded('/assets/more-controllers/', $backofficePreloads);
        self::assertNotPreloaded('/assets/@symfony/stimulus-bundle/loader-', $backofficePreloads);
        self::assertNotPreloaded('/assets/@symfony/stimulus-bundle/controllers-', $backofficePreloads);

        $partnerPreloads = self::getPreloadHrefs($browser->get('/partner')->crawler());
        self::assertPreloaded('/assets/partner-', $partnerPreloads);
        self::assertPreloaded('/assets/partner_stimulus-', $partnerPreloads);
        self::assertPreloaded('/assets/@symfony/stimulus-bundle/runtime-', $partnerPreloads);
        self::assertPreloaded('/assets/in/asset/mapper/shared/controllers/notify_controller-', $partnerPreloads);
        self::assertPreloaded('/assets/shop/controllers/cart_controller-', $partnerPreloads);
        self::assertPreloaded('/assets/controllers/hello-with-dashes-controller-', $partnerPreloads);
        self::assertNotPreloaded('/assets/fake-vendor/ux-package2/', $partnerPreloads);
        self::assertNotPreloaded('/assets/fake-vendor/ux-package1/package-controller-second-', $partnerPreloads);
        self::assertNotPreloaded('/assets/in/asset/mapper/shared/controllers/chart_controller-', $partnerPreloads);
        self::assertNotPreloaded('/assets/shop/stimulus-', $partnerPreloads);
        self::assertNotPreloaded('/assets/shop-', $partnerPreloads);
        self::assertNotPreloaded('/assets/admin/', $partnerPreloads);
        self::assertNotPreloaded('/assets/front/', $partnerPreloads);
        self::assertNotPreloaded('/assets/in/asset/mapper/zones/', $partnerPreloads);
        self::assertNotPreloaded('/assets/@symfony/stimulus-bundle/loader-', $partnerPreloads);
        self::assertNotPreloaded('/assets/@symfony/stimulus-bundle/controllers-', $partnerPreloads);
    }

    public function testTwoEntrypointsOnOnePageImportBothApplications(): void
    {
        self::removeApplicationsCache();

        $preloads = self::getPreloadHrefs($this->browser(['environment' => 'applications'])->get('/admin-with-app')->crawler());

        self::assertPreloaded('/assets/app-', $preloads);
        self::assertPreloaded('/assets/@symfony/stimulus-bundle/loader-', $preloads);
        self::assertPreloaded('/assets/controllers/hello-with-dashes-controller-', $preloads);
        self::assertPreloaded('/assets/more-controllers/hello-controller-', $preloads);
        self::assertPreloaded('/assets/admin-', $preloads);
        self::assertPreloaded('/assets/admin/stimulus-', $preloads);
        self::assertPreloaded('/assets/admin/controllers/dashboard_controller-', $preloads);
        self::assertPreloaded('/assets/@symfony/stimulus-bundle/runtime-', $preloads);
        self::assertCount(1, array_filter($preloads, static fn (string $href) => str_starts_with($href, '/assets/@symfony/stimulus-bundle/runtime-')));
        self::assertNotPreloaded('/assets/admin/controllers/report_controller-', $preloads);
        self::assertNotPreloaded('/assets/shop', $preloads);
        self::assertNotPreloaded('/assets/front', $preloads);
        self::assertNotPreloaded('/assets/partner', $preloads);
        self::assertNotPreloaded('/assets/in/asset/mapper/zones/', $preloads);
        self::assertNotPreloaded('/assets/in/asset/mapper/shared/', $preloads);
        self::assertNotPreloaded('/assets/@symfony/stimulus-bundle/controllers-', $preloads);
    }

    public function testApplicationModuleContents(): void
    {
        self::removeApplicationsCache();

        $adminModule = self::getCompiledContent('admin/stimulus.js');
        $this->assertStringStartsWith('import { loadControllers, startApplication } from "../@symfony/stimulus-bundle/runtime.js";', $adminModule);
        $this->assertStringNotContainsString('from "@symfony/stimulus-bundle"', $adminModule);
        $this->assertStringContainsString('"dashboard": controller_', $adminModule);
        $this->assertStringContainsString('"report": () => import(', $adminModule);
        $this->assertStringNotContainsString('menu', $adminModule);
        $this->assertMatchesRegularExpression('/"fake-vendor--ux-package1--controller-second": controller_\d+/', $adminModule);
        $this->assertStringNotContainsString('.css', $adminModule);

        $frontModule = self::getCompiledContent('front/stimulus_loader.js');
        $this->assertStringContainsString('"menu": controller_', $frontModule);
        $this->assertMatchesRegularExpression('/"hello": (controller_\d+)/', $frontModule);
        preg_match('/"hello": (controller_\d+)/', $frontModule, $matches);
        $this->assertStringContainsString(\sprintf('import %s from "./controllers/hello_controller.js";', $matches[1]), $frontModule);
        $this->assertStringNotContainsString('more-controllers/hello-controller.js', $frontModule);

        $shopModule = self::getCompiledContent('shop/stimulus.js');
        $this->assertStringContainsString('import controller_', $shopModule);
        $this->assertStringContainsString('"cart": controller_', $shopModule);
        $this->assertStringContainsString('"hello-with-dashes": controller_', $shopModule);
        $this->assertStringNotContainsString('dashboard', $shopModule);

        $accountModule = self::getCompiledContent('account/stimulus.js');
        $this->assertStringContainsString('"hello-with-dashes": controller_', $accountModule);
        $this->assertStringNotContainsString('cart', $accountModule);
        $this->assertStringNotContainsString('dashboard', $accountModule);
        $this->assertStringNotContainsString('menu', $accountModule);

        $backofficeModule = self::getCompiledContent('in/asset/mapper/zones/backoffice/bootstrap.js');
        $this->assertStringStartsWith('import { loadControllers, startApplication } from "../../../../../@symfony/stimulus-bundle/runtime.js";', $backofficeModule);
        $this->assertStringContainsString(' from "../../../../../admin/controllers/dashboard_controller.js";', $backofficeModule);
        $this->assertStringContainsString(' from "../../shared/controllers/notify_controller.js";', $backofficeModule);
        $this->assertStringContainsString('"chart": () => import("../../shared/controllers/chart_controller.js")', $backofficeModule);
        $this->assertStringContainsString('"report": () => import("../../../../../admin/controllers/report_controller.js")', $backofficeModule);
        $this->assertStringNotContainsString('hello-with-dashes', $backofficeModule);
        $this->assertStringNotContainsString('fake-vendor', $backofficeModule);
        $this->assertStringNotContainsString('cart', $backofficeModule);

        $partnerModule = self::getCompiledContent('partner_stimulus.js');
        $this->assertStringStartsWith('import { loadControllers, startApplication } from "./@symfony/stimulus-bundle/runtime.js";', $partnerModule);
        $this->assertStringNotContainsString('from "@symfony/stimulus-bundle"', $partnerModule);
        $this->assertStringContainsString(' from "./in/asset/mapper/shared/controllers/notify_controller.js";', $partnerModule);
        $this->assertStringContainsString(' from "./shop/controllers/cart_controller.js";', $partnerModule);
        $this->assertStringContainsString('"hello-with-dashes": controller_', $partnerModule);
        $this->assertStringContainsString('"fake-vendor--ux-package1--controller-second": () => Promise.all(', $partnerModule);
        $this->assertStringNotContainsString('ux-package2', $partnerModule);
        $this->assertStringNotContainsString('dashboard', $partnerModule);
    }

    public function testImportMapOfAnApplicationPageListsEveryEntryByDefault(): void
    {
        self::removeApplicationsCache();

        $importMapKeys = self::getImportMapKeys($this->browser(['environment' => 'applications'])->get('/admin')->crawler());

        $this->assertContains('/assets/admin/stimulus.js', $importMapKeys);
        $this->assertContains('/assets/@symfony/stimulus-bundle/runtime.js', $importMapKeys);
        $this->assertNotContains('@symfony/stimulus-bundle/admin', $importMapKeys);
        $this->assertNotContains('/assets/@symfony/stimulus-bundle/controllers.js', $importMapKeys);
        // "all" entries: the other areas are listed, without being preloaded
        $this->assertContains('stimulus-front', $importMapKeys);
        $this->assertContains('@symfony/stimulus-bundle', $importMapKeys);
        $this->assertContains('/assets/shop/stimulus.js', $importMapKeys);
    }

    public function testReachableImportMapOnlyListsTheEntriesOfTheApplication(): void
    {
        if (!\defined(ImportMapGenerator::class.'::ENTRIES_REACHABLE')) {
            $this->markTestSkipped('This test requires the "reachable" import map entries of symfony/asset-mapper 8.2+');
        }

        self::removeApplicationsCache();

        $browser = $this->browser(['environment' => 'applications_reachable']);

        $adminKeys = self::getImportMapKeys($browser->get('/admin')->crawler());
        $this->assertContains('admin', $adminKeys);
        $this->assertContains('/assets/admin/stimulus.js', $adminKeys);
        $this->assertContains('/assets/@symfony/stimulus-bundle/runtime.js', $adminKeys);
        $this->assertContains('/assets/admin/controllers/dashboard_controller.js', $adminKeys);
        $this->assertContains('/assets/admin/controllers/report_controller.js', $adminKeys);
        $this->assertNotContains('@symfony/stimulus-bundle', $adminKeys);
        $this->assertNotContains('app', $adminKeys);
        $this->assertNotContains('front', $adminKeys);
        $this->assertNotContains('stimulus-front', $adminKeys);
        $this->assertNotContains('shop', $adminKeys);
        self::assertNoKeyStartsWith('/assets/front/', $adminKeys);
        self::assertNoKeyStartsWith('/assets/shop/', $adminKeys);
        self::assertNoKeyStartsWith('/assets/account/', $adminKeys);
        self::assertNoKeyStartsWith('/assets/controllers/', $adminKeys);
        self::assertNoKeyStartsWith('/assets/more-controllers/', $adminKeys);
        self::assertNoKeyStartsWith('/assets/@symfony/stimulus-bundle/loader', $adminKeys);
        self::assertNoKeyStartsWith('/assets/@symfony/stimulus-bundle/controllers', $adminKeys);
        self::assertNoKeyStartsWith('/assets/in/asset/mapper/zones/', $adminKeys);
        self::assertNoKeyStartsWith('/assets/in/asset/mapper/shared/', $adminKeys);
        self::assertNoKeyStartsWith('/assets/partner', $adminKeys);
        $this->assertNotContains('backoffice', $adminKeys);
        $this->assertNotContains('partner', $adminKeys);

        $frontKeys = self::getImportMapKeys($browser->get('/front')->crawler());
        $this->assertContains('front', $frontKeys);
        $this->assertContains('stimulus-front', $frontKeys);
        $this->assertContains('/assets/@symfony/stimulus-bundle/runtime.js', $frontKeys);
        $this->assertContains('/assets/front/controllers/menu_controller.js', $frontKeys);
        $this->assertContains('/assets/front/controllers/hello_controller.js', $frontKeys);
        $this->assertContains('/assets/controllers/bye_controller.js', $frontKeys);
        $this->assertNotContains('/assets/more-controllers/hello-controller.js', $frontKeys);
        $this->assertNotContains('@symfony/stimulus-bundle', $frontKeys);
        $this->assertNotContains('app', $frontKeys);
        $this->assertNotContains('admin', $frontKeys);
        $this->assertNotContains('shop', $frontKeys);
        self::assertNoKeyStartsWith('/assets/admin/', $frontKeys);
        self::assertNoKeyStartsWith('/assets/shop/', $frontKeys);
        self::assertNoKeyStartsWith('/assets/account/', $frontKeys);
        self::assertNoKeyStartsWith('/assets/@symfony/stimulus-bundle/loader', $frontKeys);
        self::assertNoKeyStartsWith('/assets/@symfony/stimulus-bundle/controllers', $frontKeys);
        self::assertNoKeyStartsWith('/assets/in/asset/mapper/zones/', $frontKeys);
        self::assertNoKeyStartsWith('/assets/in/asset/mapper/shared/', $frontKeys);
        self::assertNoKeyStartsWith('/assets/partner', $frontKeys);
        $this->assertNotContains('backoffice', $frontKeys);
        $this->assertNotContains('partner', $frontKeys);

        $shopKeys = self::getImportMapKeys($browser->get('/shop')->crawler());
        $this->assertContains('shop', $shopKeys);
        $this->assertContains('/assets/shop/stimulus.js', $shopKeys);
        $this->assertContains('/assets/@symfony/stimulus-bundle/runtime.js', $shopKeys);
        $this->assertContains('/assets/shop/controllers/cart_controller.js', $shopKeys);
        $this->assertContains('/assets/controllers/bye_controller.js', $shopKeys);
        $this->assertContains('/assets/more-controllers/hello-controller.js', $shopKeys);
        $this->assertNotContains('@symfony/stimulus-bundle', $shopKeys);
        $this->assertNotContains('app', $shopKeys);
        $this->assertNotContains('admin', $shopKeys);
        $this->assertNotContains('front', $shopKeys);
        $this->assertNotContains('stimulus-front', $shopKeys);
        self::assertNoKeyStartsWith('/assets/admin/', $shopKeys);
        self::assertNoKeyStartsWith('/assets/front/', $shopKeys);
        self::assertNoKeyStartsWith('/assets/account/', $shopKeys);
        self::assertNoKeyStartsWith('/assets/@symfony/stimulus-bundle/loader', $shopKeys);
        self::assertNoKeyStartsWith('/assets/@symfony/stimulus-bundle/controllers', $shopKeys);
        self::assertNoKeyStartsWith('/assets/in/asset/mapper/zones/', $shopKeys);
        self::assertNoKeyStartsWith('/assets/in/asset/mapper/shared/', $shopKeys);
        self::assertNoKeyStartsWith('/assets/partner', $shopKeys);
        $this->assertNotContains('backoffice', $shopKeys);
        $this->assertNotContains('partner', $shopKeys);

        $globalKeys = self::getImportMapKeys($browser->get('/')->crawler());
        $this->assertContains('app', $globalKeys);
        $this->assertContains('@symfony/stimulus-bundle', $globalKeys);
        $this->assertContains('/assets/@symfony/stimulus-bundle/runtime.js', $globalKeys);
        $this->assertContains('/assets/controllers/bye_controller.js', $globalKeys);
        $this->assertContains('/assets/more-controllers/hello-controller.js', $globalKeys);
        $this->assertContains('/assets/fake-vendor/ux-package2/package-hello-controller.js', $globalKeys);
        $this->assertNotContains('admin', $globalKeys);
        $this->assertNotContains('front', $globalKeys);
        $this->assertNotContains('stimulus-front', $globalKeys);
        $this->assertNotContains('shop', $globalKeys);
        self::assertNoKeyStartsWith('/assets/admin/', $globalKeys);
        self::assertNoKeyStartsWith('/assets/front/', $globalKeys);
        self::assertNoKeyStartsWith('/assets/shop/', $globalKeys);
        self::assertNoKeyStartsWith('/assets/account/', $globalKeys);
        self::assertNoKeyStartsWith('/assets/@symfony/stimulus-bundle/controllers', $globalKeys);
        self::assertNoKeyStartsWith('/assets/in/asset/mapper/zones/', $globalKeys);
        self::assertNoKeyStartsWith('/assets/in/asset/mapper/shared/', $globalKeys);
        self::assertNoKeyStartsWith('/assets/partner', $globalKeys);
        $this->assertNotContains('backoffice', $globalKeys);
        $this->assertNotContains('partner', $globalKeys);

        $backofficeKeys = self::getImportMapKeys($browser->get('/backoffice')->crawler());
        $this->assertContains('backoffice', $backofficeKeys);
        $this->assertContains('/assets/in/asset/mapper/zones/backoffice/boot/start.js', $backofficeKeys);
        $this->assertContains('/assets/in/asset/mapper/zones/backoffice/bootstrap.js', $backofficeKeys);
        $this->assertContains('/assets/@symfony/stimulus-bundle/runtime.js', $backofficeKeys);
        $this->assertContains('/assets/in/asset/mapper/shared/controllers/notify_controller.js', $backofficeKeys);
        $this->assertContains('/assets/in/asset/mapper/shared/controllers/chart_controller.js', $backofficeKeys);
        $this->assertContains('/assets/admin/controllers/dashboard_controller.js', $backofficeKeys);
        $this->assertContains('/assets/admin/controllers/report_controller.js', $backofficeKeys);
        $this->assertNotContains('/assets/admin/stimulus.js', $backofficeKeys);
        $this->assertNotContains('admin', $backofficeKeys);
        $this->assertNotContains('@symfony/stimulus-bundle', $backofficeKeys);
        self::assertNoKeyStartsWith('/assets/shop/', $backofficeKeys);
        self::assertNoKeyStartsWith('/assets/front/', $backofficeKeys);
        self::assertNoKeyStartsWith('/assets/partner', $backofficeKeys);
        self::assertNoKeyStartsWith('/assets/controllers/', $backofficeKeys);
        self::assertNoKeyStartsWith('/assets/more-controllers/', $backofficeKeys);
        self::assertNoKeyStartsWith('/assets/fake-vendor/', $backofficeKeys);

        $partnerKeys = self::getImportMapKeys($browser->get('/partner')->crawler());
        $this->assertContains('partner', $partnerKeys);
        $this->assertContains('/assets/partner_stimulus.js', $partnerKeys);
        $this->assertContains('/assets/@symfony/stimulus-bundle/runtime.js', $partnerKeys);
        $this->assertContains('/assets/in/asset/mapper/shared/controllers/notify_controller.js', $partnerKeys);
        $this->assertContains('/assets/in/asset/mapper/shared/controllers/chart_controller.js', $partnerKeys);
        $this->assertContains('/assets/shop/controllers/cart_controller.js', $partnerKeys);
        $this->assertContains('/assets/controllers/bye_controller.js', $partnerKeys);
        $this->assertContains('/assets/fake-vendor/ux-package1/package-controller-second.js', $partnerKeys);
        self::assertNoKeyStartsWith('/assets/fake-vendor/ux-package2/', $partnerKeys);
        $this->assertNotContains('/assets/shop/stimulus.js', $partnerKeys);
        $this->assertNotContains('shop', $partnerKeys);
        $this->assertNotContains('@symfony/stimulus-bundle', $partnerKeys);
        self::assertNoKeyStartsWith('/assets/admin/', $partnerKeys);
        self::assertNoKeyStartsWith('/assets/front/', $partnerKeys);
        self::assertNoKeyStartsWith('/assets/in/asset/mapper/zones/', $partnerKeys);

        $severalKeys = self::getImportMapKeys($browser->get('/admin-with-app')->crawler());
        $this->assertContains('app', $severalKeys);
        $this->assertContains('@symfony/stimulus-bundle', $severalKeys);
        $this->assertContains('admin', $severalKeys);
        $this->assertContains('/assets/admin/stimulus.js', $severalKeys);
        $this->assertContains('/assets/admin/controllers/report_controller.js', $severalKeys);
        $this->assertContains('/assets/controllers/bye_controller.js', $severalKeys);
        $this->assertNotContains('shop', $severalKeys);
        $this->assertNotContains('stimulus-front', $severalKeys);
        self::assertNoKeyStartsWith('/assets/shop/', $severalKeys);
        self::assertNoKeyStartsWith('/assets/front/', $severalKeys);
        self::assertNoKeyStartsWith('/assets/in/asset/mapper/zones/', $severalKeys);
        self::assertNoKeyStartsWith('/assets/in/asset/mapper/shared/', $severalKeys);
    }

    public function testApplicationModuleWithUserCodeIsRejected(): void
    {
        self::removeApplicationsCache();

        $filesystem = new Filesystem();
        $module = __DIR__.'/../fixtures/assets/shop/stimulus.js';
        $stub = file_get_contents($module);

        try {
            $filesystem->dumpFile($module, $stub."startStimulusApp().register('clipboard', class {});\n");
            touch($module, time() + 1);

            $this->expectException(\LogicException::class);
            $this->expectExceptionMessage('module of the "shop" Stimulus application is generated');

            self::getCompiledContent('shop/stimulus.js');
        } finally {
            $filesystem->dumpFile($module, $stub);
            self::ensureKernelShutdown();
        }
    }

    public function testStubThatNoApplicationUsesIsRejected(): void
    {
        self::ensureKernelShutdown();
        new Filesystem()->remove(__DIR__.'/../fixtures/var/cache/test');
        // Without applications, the stub of the shop fixture is used by no application.
        self::bootKernel();

        $assetMapper = self::getContainer()->get('asset_mapper');
        \assert($assetMapper instanceof AssetMapperInterface);

        try {
            $this->expectException(\LogicException::class);
            $this->expectExceptionMessage('no Stimulus application uses it');

            $assetMapper->getAsset('shop/stimulus.js');
        } finally {
            self::ensureKernelShutdown();
        }
    }

    public function testApplicationModulesAreRecompiledWhenAControllerIsAdded(): void
    {
        self::removeApplicationsCache();

        $filesystem = new Filesystem();
        $temporaryController = __DIR__.'/../fixtures/assets/admin/controllers/temporary_controller.js';

        $this->assertStringNotContainsString('"temporary"', self::getCompiledContent('admin/stimulus.js'));

        try {
            $filesystem->dumpFile($temporaryController, "import { Controller } from '@hotwired/stimulus';\n\nexport default class extends Controller {\n}\n");
            // symfony/config < 8.2 misses a file modified in the same second as the cache
            touch($temporaryController, time() + 1);

            $this->assertStringContainsString('"temporary": controller_', self::getCompiledContent('admin/stimulus.js'));
        } finally {
            $filesystem->remove($temporaryController);
            self::ensureKernelShutdown();
        }
    }

    public function testConventionControllersDirectoryIsPickedUpOnceCreated(): void
    {
        self::removeApplicationsCache();

        $filesystem = new Filesystem();
        $controllersDir = __DIR__.'/../fixtures/assets/account/controllers';

        $this->assertStringNotContainsString('"orders"', self::getCompiledContent('account/stimulus.js'));

        try {
            $filesystem->dumpFile($controllersDir.'/orders_controller.js', "import { Controller } from '@hotwired/stimulus';\n\nexport default class extends Controller {\n}\n");
            // symfony/config < 8.2 misses a directory modified in the same second as the cache
            touch(\dirname($controllersDir), time() + 1);

            $this->assertStringContainsString('"orders": controller_', self::getCompiledContent('account/stimulus.js'));
        } finally {
            $filesystem->remove($controllersDir);
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

        new Filesystem()->remove([
            __DIR__.'/../fixtures/var/cache/applications',
            __DIR__.'/../fixtures/var/cache/applications_reachable',
        ]);
    }

    private static function getCompiledContent(string $logicalPath): string
    {
        self::ensureKernelShutdown();
        self::bootKernel(['environment' => 'applications']);

        $assetMapper = self::getContainer()->get('asset_mapper');
        \assert($assetMapper instanceof AssetMapperInterface);

        $asset = $assetMapper->getAsset($logicalPath);
        self::assertNotNull($asset);

        return $asset->content;
    }

    /**
     * @return list<string>
     */
    private static function getImportMapKeys(Crawler $crawler): array
    {
        return array_keys(json_decode($crawler->filter('script[type="importmap"]')->html(), true, flags: \JSON_THROW_ON_ERROR)['imports']);
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

    /**
     * @param list<string> $keys
     */
    private static function assertNoKeyStartsWith(string $prefix, array $keys): void
    {
        self::assertSame(
            [],
            array_values(array_filter($keys, static fn (string $key) => str_starts_with($key, $prefix))),
            \sprintf('An import map entry starts with "%s".', $prefix),
        );
    }
}
