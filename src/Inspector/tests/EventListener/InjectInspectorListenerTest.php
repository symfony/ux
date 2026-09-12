<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Inspector\Tests\EventListener;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\Inspector\EventListener\InjectInspectorListener;

#[CoversClass(InjectInspectorListener::class)]
final class InjectInspectorListenerTest extends TestCase
{
    private function createResponseEvent(
        Response $response,
        int $requestType = HttpKernelInterface::MAIN_REQUEST,
        ?Request $request = null,
    ): ResponseEvent {
        $kernel = $this->createStub(KernelInterface::class);
        $request ??= new Request();

        return new ResponseEvent($kernel, $request, $requestType, $response);
    }

    /**
     * @param list<string> $excludePaths
     */
    private function createListener(array $excludePaths = []): InjectInspectorListener
    {
        return new InjectInspectorListener(
            $this->createUrlGenerator(),
            true,
            $this->createDefaultConfig(),
            $excludePaths,
        );
    }

    private function createHtmlResponse(string $content = '<html><body></body></html>'): Response
    {
        $response = new Response($content);
        $response->headers->set('Content-Type', 'text/html; charset=UTF-8');

        return $response;
    }

    private function createDefaultConfig(): array
    {
        return [
            'ignore_selectors' => [],
        ];
    }

    private function createUrlGenerator(string $url = '/_ux/inspector.js'): UrlGeneratorInterface
    {
        $generator = $this->createStub(UrlGeneratorInterface::class);
        $generator->method('generate')->willReturn($url);

        return $generator;
    }

    public function testDoesNotInjectWhenDisabled(): void
    {
        $listener = new InjectInspectorListener($this->createUrlGenerator(), false, $this->createDefaultConfig());
        $response = $this->createHtmlResponse();
        $event = $this->createResponseEvent($response);

        $listener($event);

        self::assertStringNotContainsString('ux-inspector', $response->getContent());
        self::assertSame('<html><body></body></html>', $response->getContent());
    }

    public function testDoesNotInjectForSubRequests(): void
    {
        $listener = new InjectInspectorListener($this->createUrlGenerator(), true, $this->createDefaultConfig());
        $response = $this->createHtmlResponse();
        $event = $this->createResponseEvent($response, HttpKernelInterface::SUB_REQUEST);

        $listener($event);

        self::assertStringNotContainsString('ux-inspector', $response->getContent());
    }

    public function testDoesNotInjectForNonHtmlResponse(): void
    {
        $listener = new InjectInspectorListener($this->createUrlGenerator(), true, $this->createDefaultConfig());
        $response = new Response('{"data": true}');
        $response->headers->set('Content-Type', 'application/json');
        $event = $this->createResponseEvent($response);

        $listener($event);

        self::assertSame('{"data": true}', $response->getContent());
    }

    public function testDoesNotInjectWhenContentIsFalse(): void
    {
        $listener = new InjectInspectorListener($this->createUrlGenerator(), true, $this->createDefaultConfig());
        // StreamedResponse::getContent() returns false -- there is nothing to inject into.
        $response = new StreamedResponse(static function (): void {});
        $response->headers->set('Content-Type', 'text/html');
        $event = $this->createResponseEvent($response);

        $listener($event);

        self::assertFalse($response->getContent());
    }

    public function testDoesNotInjectWhenNoBodyTag(): void
    {
        $listener = new InjectInspectorListener($this->createUrlGenerator(), true, $this->createDefaultConfig());
        $response = $this->createHtmlResponse('<html><head><title>No body</title></head></html>');
        $event = $this->createResponseEvent($response);

        $listener($event);

        self::assertStringNotContainsString('ux-inspector', $response->getContent());
        self::assertSame('<html><head><title>No body</title></head></html>', $response->getContent());
    }

    public function testInjectsScriptBeforeElementWhenHeadIsAbsent(): void
    {
        $listener = new InjectInspectorListener($this->createUrlGenerator(), true, $this->createDefaultConfig());
        $response = $this->createHtmlResponse('<html><body><h1>Hello</h1></body></html>');
        $event = $this->createResponseEvent($response);

        $listener($event);

        $content = $response->getContent();
        self::assertIsString($content);
        self::assertStringContainsString('<ux-inspector', $content);
        self::assertStringContainsString('data-turbo-permanent', $content);
        self::assertStringContainsString('id="ux-inspector"', $content);
        self::assertStringContainsString('<script type="module" src="/_ux/inspector.js"></script>', $content);
        self::assertStringNotContainsString('import ', $content);
        self::assertStringContainsString('</body>', $content);

        // The element must appear before </body>.
        $elementPos = strpos($content, '<ux-inspector');
        $bodyPos = strripos($content, '</body>');
        self::assertNotFalse($elementPos);
        self::assertNotFalse($bodyPos);
        self::assertLessThan($bodyPos, $elementPos);
    }

    public function testInjectsScriptInHeadAndPermanentElementInBody(): void
    {
        $listener = new InjectInspectorListener($this->createUrlGenerator(), true, $this->createDefaultConfig());
        $response = $this->createHtmlResponse('<html><HEAD></HEAD><body><h1>Hello</h1></body></html>');
        $response->headers->set('Content-Length', '75');
        $listener($this->createResponseEvent($response));

        $content = $response->getContent();
        self::assertIsString($content);
        self::assertStringContainsString('<script type="module" src="/_ux/inspector.js"></script>', $content);
        self::assertStringNotContainsString('importmap', $content);
        self::assertLessThan(strpos($content, '</HEAD>'), strpos($content, '<script'));
        self::assertGreaterThan(strpos($content, '<body>'), strpos($content, '<ux-inspector'));
        self::assertLessThan(strpos($content, '</body>'), strpos($content, '<ux-inspector'));
        self::assertFalse($response->headers->has('Content-Length'));
    }

    public function testInjectedElementContainsConfigAsDataAttribute(): void
    {
        $config = [
            'ignore_selectors' => ['.no-inspect'],
        ];
        $listener = new InjectInspectorListener($this->createUrlGenerator(), true, $config);
        $response = $this->createHtmlResponse('<html><body></body></html>');
        $event = $this->createResponseEvent($response);

        $listener($event);

        $content = $response->getContent();
        self::assertIsString($content);
        self::assertStringContainsString('data-config="', $content);
        // Config is HTML-encoded in the attribute
        $expectedJson = json_encode($config, \JSON_THROW_ON_ERROR);
        $expectedAttr = htmlspecialchars($expectedJson, \ENT_QUOTES, 'UTF-8');
        self::assertStringContainsString($expectedAttr, $content);
    }

    public function testEscapesGeneratedScriptUrl(): void
    {
        $jsUrl = '/assets/inspector.js?value="><script>alert(1)</script>&quote=\'';
        $listener = new InjectInspectorListener($this->createUrlGenerator($jsUrl), true, $this->createDefaultConfig());
        $response = $this->createHtmlResponse('<html><body></body></html>');
        $event = $this->createResponseEvent($response);

        $listener($event);

        $content = $response->getContent();
        self::assertIsString($content);
        self::assertStringContainsString('src="'.htmlspecialchars($jsUrl, \ENT_QUOTES, 'UTF-8').'"', $content);
        self::assertStringNotContainsString($jsUrl, $content);
    }

    public function testInjectedScriptIncludesComment(): void
    {
        $listener = new InjectInspectorListener($this->createUrlGenerator(), true, $this->createDefaultConfig());
        $response = $this->createHtmlResponse('<html><body></body></html>');
        $event = $this->createResponseEvent($response);

        $listener($event);

        $content = $response->getContent();
        self::assertIsString($content);
        self::assertStringContainsString('<!-- UX Inspector -->', $content);
    }

    public function testDoesNotInjectForContentTypeWithoutHtml(): void
    {
        $listener = new InjectInspectorListener($this->createUrlGenerator(), true, $this->createDefaultConfig());
        $response = new Response('<xml><body></body></xml>');
        $response->headers->set('Content-Type', 'application/xml');
        $event = $this->createResponseEvent($response);

        $listener($event);

        self::assertStringNotContainsString('ux-inspector', $response->getContent());
    }

    public function testCaseInsensitiveBodyTagMatch(): void
    {
        $listener = new InjectInspectorListener($this->createUrlGenerator(), true, $this->createDefaultConfig());
        $response = $this->createHtmlResponse('<html><BODY><h1>Hello</h1></BODY></html>');
        $event = $this->createResponseEvent($response);

        $listener($event);

        $content = $response->getContent();
        self::assertIsString($content);
        self::assertStringContainsString('<ux-inspector', $content);
    }

    public function testDoesNotInjectForTurboFrameRequest(): void
    {
        $listener = new InjectInspectorListener($this->createUrlGenerator(), true, $this->createDefaultConfig());
        $response = $this->createHtmlResponse('<html><body><turbo-frame id="main">content</turbo-frame></body></html>');
        $request = new Request();
        $request->headers->set('Turbo-Frame', 'main');
        $event = $this->createResponseEvent($response, HttpKernelInterface::MAIN_REQUEST, $request);

        $listener($event);

        self::assertStringNotContainsString('ux-inspector', $response->getContent());
    }

    public function testDoesNotInjectForTurboStreamResponse(): void
    {
        $listener = new InjectInspectorListener($this->createUrlGenerator(), true, $this->createDefaultConfig());
        $response = new Response('<turbo-stream action="replace" target="msg"><template><p>Updated</p></template></turbo-stream>');
        $response->headers->set('Content-Type', 'text/vnd.turbo-stream.html; charset=UTF-8');
        $event = $this->createResponseEvent($response);

        $listener($event);

        self::assertStringNotContainsString('ux-inspector', $response->getContent());
    }

    public function testDoesNotInjectWhenInspectorAlreadyPresent(): void
    {
        $listener = new InjectInspectorListener($this->createUrlGenerator(), true, $this->createDefaultConfig());
        $response = $this->createHtmlResponse('<html><body><ux-inspector id="ux-inspector"></ux-inspector></body></html>');
        $event = $this->createResponseEvent($response);

        $listener($event);

        // Should not have a second ux-inspector element
        $content = $response->getContent();
        self::assertIsString($content);
        self::assertSame(1, substr_count($content, '<ux-inspector'));
    }

    public function testStillInjectsForHtmlWithCharset(): void
    {
        $listener = new InjectInspectorListener($this->createUrlGenerator(), true, $this->createDefaultConfig());
        $response = new Response('<html><body></body></html>');
        $response->headers->set('Content-Type', 'text/html; charset=UTF-8');
        $event = $this->createResponseEvent($response);

        $listener($event);

        self::assertStringContainsString('<ux-inspector', $response->getContent());
    }

    public function testDoesNotInjectForExcludedPath(): void
    {
        $listener = new InjectInspectorListener($this->createUrlGenerator(), true, $this->createDefaultConfig(), ['/_profiler', '/_wdt']);
        $response = $this->createHtmlResponse('<html><body></body></html>');
        $request = Request::create('/_profiler/abc123');
        $event = $this->createResponseEvent($response, HttpKernelInterface::MAIN_REQUEST, $request);

        $listener($event);

        self::assertStringNotContainsString('ux-inspector', $response->getContent());
    }

    public function testDoesNotInjectForExactExcludedPath(): void
    {
        $listener = new InjectInspectorListener($this->createUrlGenerator(), true, $this->createDefaultConfig(), ['/_wdt']);
        $response = $this->createHtmlResponse('<html><body></body></html>');
        $request = Request::create('/_wdt');
        $event = $this->createResponseEvent($response, HttpKernelInterface::MAIN_REQUEST, $request);

        $listener($event);

        self::assertStringNotContainsString('ux-inspector', $response->getContent());
    }

    #[DataProvider('pathsWithTrailingSlashPrefix')]
    public function testExcludedPrefixWithTrailingSlash(string $path, bool $excluded): void
    {
        $listener = new InjectInspectorListener($this->createUrlGenerator(), true, $this->createDefaultConfig(), ['/_profiler/']);
        $response = $this->createHtmlResponse();
        $event = $this->createResponseEvent($response, request: Request::create($path));

        $listener($event);

        if ($excluded) {
            self::assertStringNotContainsString('<ux-inspector', $response->getContent());
        } else {
            self::assertStringContainsString('<ux-inspector', $response->getContent());
        }
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function pathsWithTrailingSlashPrefix(): iterable
    {
        yield 'exact path' => ['/_profiler', true];
        yield 'trailing slash' => ['/_profiler/', true];
        yield 'child path' => ['/_profiler/abc123', true];
        yield 'different segment' => ['/_profilerfoo', false];
    }

    public function testInjectsForNonExcludedPath(): void
    {
        $listener = new InjectInspectorListener($this->createUrlGenerator(), true, $this->createDefaultConfig(), ['/_profiler', '/_wdt']);
        $response = $this->createHtmlResponse('<html><body></body></html>');
        $request = Request::create('/dashboard');
        $event = $this->createResponseEvent($response, HttpKernelInterface::MAIN_REQUEST, $request);

        $listener($event);

        self::assertStringContainsString('<ux-inspector', $response->getContent());
    }

    public function testSegmentAwareExcludeDoesNotMatchPartialPrefix(): void
    {
        $listener = new InjectInspectorListener($this->createUrlGenerator(), true, $this->createDefaultConfig(), ['/_wdt']);
        $response = $this->createHtmlResponse('<html><body></body></html>');
        $request = Request::create('/_wdtfoo');
        $event = $this->createResponseEvent($response, HttpKernelInterface::MAIN_REQUEST, $request);

        $listener($event);

        self::assertStringContainsString('<ux-inspector', $response->getContent());
    }

    public function testDoesNotInjectIntoXmlHttpRequests(): void
    {
        $request = new Request();
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        $listener = $this->createListener();
        $response = $this->createHtmlResponse();

        $listener($this->createResponseEvent($response, request: $request));

        self::assertStringNotContainsString('<ux-inspector', (string) $response->getContent());
    }

    public function testDoesNotInjectIntoRedirections(): void
    {
        $listener = $this->createListener();
        $response = new RedirectResponse('/somewhere');
        $response->headers->set('Content-Type', 'text/html; charset=UTF-8');
        $response->setContent('<html><body></body></html>');

        $listener($this->createResponseEvent($response));

        self::assertStringNotContainsString('<ux-inspector', (string) $response->getContent());
    }

    public function testDoesNotInjectWhenRequestFormatIsNotHtml(): void
    {
        $request = new Request();
        $request->setRequestFormat('json');
        $listener = $this->createListener();
        $response = $this->createHtmlResponse();

        $listener($this->createResponseEvent($response, request: $request));

        self::assertStringNotContainsString('<ux-inspector', (string) $response->getContent());
    }

    #[DataProvider('attachmentDispositions')]
    public function testDoesNotInjectIntoDownloads(string $disposition): void
    {
        $listener = $this->createListener();
        $response = $this->createHtmlResponse();
        $response->headers->set('Content-Disposition', $disposition);
        $original = $response->getContent();

        $listener($this->createResponseEvent($response));

        self::assertSame($original, $response->getContent());
    }

    public static function attachmentDispositions(): iterable
    {
        yield 'bare disposition' => ['attachment'];
        yield 'mixed case and whitespace' => [" \tAtTaChMeNt \t"];
        yield 'with filename' => ['attachment; filename="report.html"'];
        yield 'space before parameter' => ['attachment ; filename="report.html"'];
    }

    public function testInlineFilenameDoesNotDetermineDisposition(): void
    {
        $response = $this->createHtmlResponse();
        $response->headers->set('Content-Disposition', 'inline; filename="attachment; report.html"');

        ($this->createListener())($this->createResponseEvent($response));

        self::assertStringContainsString('<ux-inspector', $response->getContent());
    }

    public function testDoesNotInjectWhenTheScriptRouteIsNotImported(): void
    {
        // Registering the bundle without importing its routes must disable the
        // inspector, not turn every HTML page into a 500.
        $generator = $this->createStub(UrlGeneratorInterface::class);
        $generator->method('generate')->willThrowException(new RouteNotFoundException('Route "_ux_inspector_script" does not exist.'));
        $listener = new InjectInspectorListener($generator, true, $this->createDefaultConfig());
        $response = $this->createHtmlResponse('<html><head></head><body></body></html>');

        $listener($this->createResponseEvent($response));

        self::assertSame('<html><head></head><body></body></html>', $response->getContent());
    }

    public function testInjectsScriptIntoTheFirstHeadNotALaterLiteralOne(): void
    {
        // A literal </head> in the body is ignored by the HTML parser, so the
        // script must go to the first one or it ends up as visible page text.
        $listener = $this->createListener();
        $response = $this->createHtmlResponse('<html><head></head><body><textarea>layout: </head></textarea></body></html>');

        $listener($this->createResponseEvent($response));

        $content = $response->getContent();
        self::assertIsString($content);
        self::assertStringContainsString('<head>'."\n".'        <script type="module" src="/_ux/inspector.js"></script>'."\n".'</head>', $content);
        self::assertStringContainsString('<textarea>layout: </head></textarea>', $content);
    }

    #[DataProvider('literalMarkup')]
    public function testInjectionPreservesLiteralMarkup(string $head, string $body, string $tail = ''): void
    {
        $html = '<!DOCTYPE html><html><head>'.$head.'</head><body>'.$body.'</body></html>'.$tail;
        $response = $this->createHtmlResponse($html);

        ($this->createListener())($this->createResponseEvent($response));

        $content = $response->getContent();
        $script = "\n        <script type=\"module\" src=\"/_ux/inspector.js\"></script>\n";
        self::assertStringContainsString('<head>'.$head.$script.'</head>', $content);
        self::assertStringContainsString('<body>'.$body, $content);
        self::assertStringEndsWith('</body></html>'.$tail, $content);
        $restored = preg_replace('~\n<!-- UX Inspector -->\n<ux-inspector [^>]+></ux-inspector>\n~', '', str_replace($script, '', $content));
        self::assertSame($html, $restored);

        if (class_exists(\Dom\HTMLDocument::class)) {
            $document = \Dom\HTMLDocument::createFromString($content, \LIBXML_NOERROR);
            self::assertCount(1, $document->querySelectorAll('head > script[type="module"]'));
            self::assertCount(1, $document->querySelectorAll('body > ux-inspector'));
        }
    }

    public static function literalMarkup(): iterable
    {
        yield 'script contents' => ['<script>const markup = "</head> <ux-inspector>";</script>', 'Hello'];
        yield 'head comment' => ['<!-- literal </head> -->', 'Hello'];
        yield 'style contents' => ['<style>body::before { content: "</head>"; }</style>', 'Hello'];
        yield 'title contents' => ['<title>Literal </head></title>', 'Hello'];
        yield 'quoted attribute' => ['<meta content="before > </head> after">', 'Hello'];
        yield 'single quoted attribute' => ["<meta content='before > </head> after'>", 'Hello'];
        yield 'textarea contents' => ['', '<textarea>Literal </head> </body> <ux-inspector></textarea>'];
        yield 'body comment' => ['', '<!-- literal </body> -->'];
        yield 'trailing comment' => ['', 'Hello', '<!-- literal </body> -->'];
        yield 'inert template' => ['', '<template><template></body></template></head><ux-inspector></ux-inspector></template>'];
    }

    public function testQuotedDeclarationDoesNotSupplyAnInjectionPoint(): void
    {
        $declaration = '<!DOCTYPE html PUBLIC "literal > </head>" "about:legacy-compat">';
        $response = $this->createHtmlResponse($declaration.'<html><head></head><body>Hello</body></html>');

        ($this->createListener())($this->createResponseEvent($response));

        self::assertStringStartsWith($declaration.'<html><head>'."\n".'        <script ', $response->getContent());
    }

    public function testFallsBackToBodyWhenHeadIsOnlyAStrayClosingTag(): void
    {
        $html = '<html><body><p>Hello</p></head></body></html>';
        $response = $this->createHtmlResponse($html);

        ($this->createListener())($this->createResponseEvent($response));

        self::assertStringStartsWith('<html><body><p>Hello</p></head>'."\n".'        <script ', $response->getContent());
    }

    public function testDetectsAnExistingInspectorTagCaseInsensitively(): void
    {
        $html = '<html><head></head><body><UX-INSPECTOR></UX-INSPECTOR></body></html>';
        $response = $this->createHtmlResponse($html);

        ($this->createListener())($this->createResponseEvent($response));

        self::assertSame($html, $response->getContent());
    }

    public function testDoesNotInjectWhenBodyClosingTagIsOnlyRawText(): void
    {
        $html = '<html><body><textarea>Literal </body></textarea>';
        $response = $this->createHtmlResponse($html);

        ($this->createListener())($this->createResponseEvent($response));

        self::assertSame($html, $response->getContent());
    }

    #[DataProvider('incompleteBodies')]
    public function testDoesNotInjectWithoutARealBodyPair(string $html): void
    {
        $response = $this->createHtmlResponse($html);

        ($this->createListener())($this->createResponseEvent($response));

        self::assertSame($html, $response->getContent());
    }

    public static function incompleteBodies(): iterable
    {
        yield 'closing tag alone' => ['<section>Fragment</section></body>'];
        yield 'head without body opening' => ['<html><head></head><main>Fragment</main></body></html>'];
        yield 'opening without closing' => ['<html><body><main>Fragment</main></html>'];
        yield 'closing before opening' => ['</body><body>Fragment'];
        yield 'opening in script' => ['<script>const markup = "<body>";</script>Fragment</body>'];
        yield 'opening in template' => ['<template><body>Example</template>Fragment</body>'];
        yield 'opening in attribute' => ['<section data-markup="<body>">Fragment</section></body>'];
        yield 'opening in comment' => ['<!-- <body> -->Fragment</body>'];
    }

    public function testInjectsIntoARealBodyPairWithoutHead(): void
    {
        $response = $this->createHtmlResponse('<html><body><main>Hello</main></body></html>');

        ($this->createListener())($this->createResponseEvent($response));

        $content = $response->getContent();
        self::assertStringStartsWith('<html><body><main>Hello</main>'."\n".'        <script ', $content);
        self::assertStringEndsWith('</body></html>', $content);
        if (class_exists(\Dom\HTMLDocument::class)) {
            $document = \Dom\HTMLDocument::createFromString($content, \LIBXML_NOERROR);
            self::assertCount(1, $document->querySelectorAll('body > script[type="module"]'));
            self::assertCount(1, $document->querySelectorAll('body > ux-inspector'));
        }
    }

    public function testDoesNotInjectIntoFragments(): void
    {
        $listener = $this->createListener(['/_fragment']);
        $response = $this->createHtmlResponse();

        $listener($this->createResponseEvent($response, request: Request::create('/_fragment?_path=x')));

        self::assertStringNotContainsString('<ux-inspector', (string) $response->getContent());
    }
}
