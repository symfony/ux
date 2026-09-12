<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Inspector\Tests\Integration;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\UX\Inspector\Controller\InspectorController;
use Symfony\UX\Inspector\Tests\Fixtures\InspectorKernel;

final class InspectorTest extends TestCase
{
    private InspectorKernel $kernel;
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir().'/ux-inspector-'.bin2hex(random_bytes(6));
    }

    protected function tearDown(): void
    {
        if (isset($this->kernel)) {
            $this->kernel->shutdown();
        }
        new Filesystem()->remove($this->directory);
    }

    public function testServesStandaloneModuleAndInjectsItsUrl(): void
    {
        $this->kernel = new InspectorKernel('dev', true, $this->directory);
        $page = $this->kernel->handle(Request::create('/'));
        self::assertSame(200, $page->getStatusCode());
        $html = $page->getContent();
        self::assertIsString($html);
        self::assertStringContainsString('<script type="module" src="/_ux/inspector.js"></script>', $html);
        self::assertLessThan(strpos($html, '</head>'), strpos($html, '<script'));
        self::assertSame(1, substr_count($html, '<ux-inspector'));
        self::assertStringContainsString('&quot;pull_tab&quot;:true', $html);
        self::assertStringNotContainsString('importmap', $html);

        $response = $this->kernel->handle(Request::create('/_ux/inspector.js'));
        self::assertInstanceOf(BinaryFileResponse::class, $response);
        self::assertSame(200, $response->getStatusCode());
        self::assertSame('text/javascript; charset=UTF-8', $response->headers->get('Content-Type'));
        self::assertTrue($response->headers->hasCacheControlDirective('private'));
        self::assertTrue($response->headers->hasCacheControlDirective('no-cache'));
        self::assertSame(realpath(__DIR__.'/../../assets/dist/inspector.js'), $response->getFile()->getRealPath());
        $etag = $response->getEtag();
        self::assertNotNull($etag);
        $cached = $this->kernel->handle(Request::create('/_ux/inspector.js', server: ['HTTP_IF_NONE_MATCH' => $etag]));
        self::assertSame(304, $cached->getStatusCode());

        $head = $this->kernel->handle(Request::create('/_ux/inspector.js', 'HEAD'));
        self::assertSame(200, $head->getStatusCode());
        $post = $this->kernel->handle(Request::create('/_ux/inspector.js', 'POST'));
        self::assertSame(405, $post->getStatusCode());
        $unknown = $this->kernel->handle(Request::create('/_ux/composer.json'));
        self::assertSame(404, $unknown->getStatusCode());
    }

    public function testDoesNotRegisterAnAssetMapperPath(): void
    {
        // The module is served by a route. Registering an AssetMapper path as
        // well would put the Inspector in the importmap and load it twice.
        $this->kernel = new InspectorKernel('dev', true, $this->directory, assetMapper: true);
        $this->kernel->boot();

        $mapper = $this->kernel->getContainer()->get('test.asset_mapper');
        self::assertNull($mapper->getAsset('@symfony/ux-inspector/inspector.js'));
    }

    public function testHiddenPullTabIsPassedToTheInjectedElement(): void
    {
        $this->kernel = new InspectorKernel('dev', true, $this->directory, pullTab: false);
        $response = $this->kernel->handle(Request::create('/'));

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('<ux-inspector', $response->getContent());
        self::assertStringContainsString('&quot;pull_tab&quot;:false', $response->getContent());
        self::assertStringContainsString('<script type="module" src="/_ux/inspector.js"></script>', $response->getContent());
    }

    public function testHonorsRoutePrefix(): void
    {
        $this->kernel = new InspectorKernel('dev', true, $this->directory, routePrefix: '/tools');
        $page = $this->kernel->handle(Request::create('/'));

        self::assertStringContainsString('src="/tools/_ux/inspector.js"', $page->getContent());
        self::assertSame(200, $this->kernel->handle(Request::create('/tools/_ux/inspector.js'))->getStatusCode());
        self::assertSame(404, $this->kernel->handle(Request::create('/_ux/inspector.js'))->getStatusCode());
    }

    #[DataProvider('disabledModes')]
    public function testDisabledInspectorHasNoRouteOrInjection(string $environment, bool $debug, bool $inspector, bool $enabled): void
    {
        $this->kernel = new InspectorKernel($environment, $debug, $this->directory, $inspector, $enabled);
        $page = $this->kernel->handle(Request::create('/'));
        self::assertSame(200, $page->getStatusCode());
        self::assertStringNotContainsString('<ux-inspector', $page->getContent());
        self::assertStringNotContainsString('/_ux/inspector.js', $page->getContent());
        self::assertNull($this->kernel->getContainer()->get('router')->getRouteCollection()->get('_ux_inspector_script'));
        self::assertFalse($this->kernel->getContainer()->has(InspectorController::class));
        self::assertSame(404, $this->kernel->handle(Request::create('/_ux/inspector.js'))->getStatusCode());
    }

    public static function disabledModes(): iterable
    {
        yield 'debug disabled' => ['dev', false, true, true];
        yield 'configuration disabled' => ['dev', true, true, false];
        yield 'production without the bundle' => ['prod', false, false, true];
        // The fixture imports the routes unconditionally: an application that
        // forgets when@dev must still expose nothing outside debug.
        yield 'production with the bundle accidentally enabled' => ['prod', false, true, true];
    }
}
