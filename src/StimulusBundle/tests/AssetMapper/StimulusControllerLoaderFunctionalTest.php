<?php

/*
 * This file is part of the Symfony StimulusBundle package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\StimulusBundle\Tests\AssetMapper;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\UX\StimulusBundle\Tests\fixtures\StimulusTestKernel;
use Zenstruck\Browser\Test\HasBrowser;

class StimulusControllerLoaderFunctionalTest extends WebTestCase
{
    use HasBrowser;

    public function testFullApplicationLoad()
    {
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
            // 2x from "controllers" (hello is overridden)
            '/assets/controllers/bye_controller.js',
            '/assets/controllers/subdir/deeper-controller.js',
            // 2x from UX packages, which are enabled in controllers.json
            '/assets/fake-vendor/ux-package1/package-controller-second.js',
            '/assets/fake-vendor/ux-package2/package-hello-controller.js',
            // 2x from more-controllers
            '/assets/more-controllers/hello-controller.js',
            '/assets/more-controllers/other-controller.js',
            // 3x from importmap.php
            '@hotwired/stimulus',
            '@symfony/stimulus-bundle',
            'app',
        ], $importMapKeys);

        // "app" & loader.js are pre-loaded. So, all non-lazy controllers should be preloaded:
        $preLoadHrefs = $crawler->filter('link[rel="modulepreload"]')->each(function ($link) {
            return $link->attr('href');
        });
        $this->assertCount(6, $preLoadHrefs);
        sort($preLoadHrefs);
        $this->assertStringStartsWith('/assets/@symfony/stimulus-bundle/controllers-', $preLoadHrefs[0]);
        $this->assertStringStartsWith('/assets/@symfony/stimulus-bundle/loader-', $preLoadHrefs[1]);
        $this->assertStringStartsWith('/assets/app-', $preLoadHrefs[2]);
        $this->assertStringStartsWith('/assets/controllers/subdir/deeper-controller-', $preLoadHrefs[3]);
        $this->assertStringStartsWith('/assets/fake-vendor/ux-package2/package-hello-controller-', $preLoadHrefs[4]);
        $this->assertStringStartsWith('/assets/more-controllers/hello-controller-', $preLoadHrefs[5]);
    }

    protected static function getKernelClass(): string
    {
        return StimulusTestKernel::class;
    }
}
