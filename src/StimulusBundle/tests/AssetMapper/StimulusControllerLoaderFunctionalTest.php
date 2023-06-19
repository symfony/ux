<?php

/*
 * This file is part of the Symfony StimulusBundle package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\StimulusBundle\Tests\AssetMapper;

use Composer\InstalledVersions;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\UX\StimulusBundle\Tests\fixtures\StimulusTestKernel;
use Zenstruck\Browser\Test\HasBrowser;

class StimulusControllerLoaderFunctionalTest extends WebTestCase
{
    use HasBrowser;

    public function testFullApplicationLoad()
    {
        if (InstalledVersions::getVersion('symfony/framework-bundle') < '6.3') {
            $this->markTestSkipped('This test requires symfony/framework-bundle 6.3+');
        }

        $filesystem = new Filesystem();
        $filesystem->remove(__DIR__.'/../fixtures/var/cache');

        $crawler = $this->browser()
            ->get('/')
            ->crawler()
        ;

        $importMapJson = $crawler->filter('script[type="importmap"]')->html();
        $importMap = json_decode($importMapJson, true);
        $importMapKeys = array_keys($importMap['imports']);
        sort($importMapKeys);
        $this->assertSame([
            // 1x import from loader.js (which is aliased to @symfony/stimulus-bundle via importmap)
            '/assets/@symfony/stimulus-bundle/controllers.js',
            // 6x from "controllers" (hello is overridden)
            '/assets/controllers/bye_controller.js',
            '/assets/controllers/hello-with-dashes-controller.js',
            '/assets/controllers/hello_with_underscores-controller.js',
            '/assets/controllers/subdir/deeper-controller.js',
            '/assets/controllers/subdir/deeper-with-dashes-controller.js',
            '/assets/controllers/subdir/deeper_with_underscores-controller.js',
            // 2x from UX packages, which are enabled in controllers.json
            '/assets/fake-vendor/ux-package1/package-controller-second.js',
            '/assets/fake-vendor/ux-package2/package-hello-controller.js',
            // 2x from more-controllers
            '/assets/more-controllers/hello-controller.js',
            '/assets/more-controllers/other-controller.js',
            // 5x from importmap.php
            '@hotwired/stimulus',
            '@scoped/needed-vendor',
            '@symfony/stimulus-bundle',
            'app',
            'needed-vendor',
        ], $importMapKeys);

        // "app" & loader.js are pre-loaded. So, all non-lazy controllers should be preloaded:
        $preLoadHrefs = $crawler->filter('link[rel="modulepreload"]')->each(function ($link) {
            return $link->attr('href');
        });
        $this->assertCount(10, $preLoadHrefs);
        sort($preLoadHrefs);
        $this->assertStringStartsWith('/assets/@symfony/stimulus-bundle/controllers-', $preLoadHrefs[0]);
        $this->assertStringStartsWith('/assets/@symfony/stimulus-bundle/loader-', $preLoadHrefs[1]);
        $this->assertStringStartsWith('/assets/app-', $preLoadHrefs[2]);
        $this->assertStringStartsWith('/assets/controllers/hello-with-dashes-controller-', $preLoadHrefs[3]);
        $this->assertStringStartsWith('/assets/controllers/hello_with_underscores-controller-', $preLoadHrefs[4]);
        $this->assertStringStartsWith('/assets/controllers/subdir/deeper-controller-', $preLoadHrefs[5]);
        $this->assertStringStartsWith('/assets/controllers/subdir/deeper-with-dashes-controller-', $preLoadHrefs[6]);
        $this->assertStringStartsWith('/assets/controllers/subdir/deeper_with_underscores-controller-', $preLoadHrefs[7]);
        $this->assertStringStartsWith('/assets/fake-vendor/ux-package2/package-hello-controller-', $preLoadHrefs[8]);
        $this->assertStringStartsWith('/assets/more-controllers/hello-controller-', $preLoadHrefs[9]);
    }

    protected static function getKernelClass(): string
    {
        return StimulusTestKernel::class;
    }
}
