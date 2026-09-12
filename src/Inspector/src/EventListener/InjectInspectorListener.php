<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Inspector\EventListener;

use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Appends the <ux-inspector> element and its module script to HTML pages.
 *
 * Injection is limited to main, non-download, text/html responses outside the
 * excluded paths: an XHR, a redirection, a Turbo Frame request or a Turbo Stream
 * response must never receive the element. The element carries the inspector
 * configuration as JSON and is marked data-turbo-permanent so a Turbo visit
 * keeps the running instance instead of booting a second one.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class InjectInspectorListener
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly bool $enabled,
        /** @var array<string, mixed> */
        private readonly array $config,
        /** @var list<string> */
        private readonly array $excludePaths = [],
    ) {
    }

    public function __invoke(ResponseEvent $event): void
    {
        if (!$this->enabled) {
            return;
        }

        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();

        // Same guards as the web debug toolbar: an XHR, a redirection, a
        // non-HTML format or a download must never receive the element.
        if ($request->isXmlHttpRequest()
            || $response->isRedirection()
            || 'html' !== $request->getRequestFormat()
            || 0 === strcasecmp(trim(explode(';', $response->headers->get('Content-Disposition', '') ?? '')[0]), 'attachment')
        ) {
            return;
        }

        // Turbo Drive does not set X-Requested-With: the inspector is already
        // on the main page, so a frame response must be left alone.
        if ($request->headers->has('Turbo-Frame')) {
            return;
        }

        // Skip excluded URL paths (e.g. profiler, debug toolbar)
        // Segment-aware: /_wdt matches /_wdt and /_wdt/xxx, not /_wdtfoo
        $pathInfo = $request->getPathInfo();
        foreach ($this->excludePaths as $prefix) {
            $prefix = rtrim($prefix, '/');
            if ($pathInfo === $prefix || str_starts_with($pathInfo, $prefix.'/')) {
                return;
            }
        }

        $contentType = trim(explode(';', $response->headers->get('Content-Type', '') ?? '')[0]);

        // Exact MIME check: prevents injection into Turbo Stream responses
        // (text/vnd.turbo-stream.html would match a naive str_contains check)
        if ('text/html' !== strtolower($contentType)) {
            return;
        }

        $content = $response->getContent();
        if (false === $content) {
            return;
        }

        $positions = $this->findInjectionPositions($content);
        if (null === $positions) {
            return;
        }
        [$headPos, $bodyPos] = $positions;

        try {
            $scriptUrl = $this->urlGenerator->generate('_ux_inspector_script');
        } catch (RouteNotFoundException) {
            // The bundle is registered but "@UXInspectorBundle/config/routes.php"
            // was never imported. Stay out of the way instead of breaking the page.
            return;
        }

        $configJson = json_encode($this->config, \JSON_THROW_ON_ERROR);
        $configAttr = htmlspecialchars($configJson, \ENT_QUOTES, 'UTF-8');

        $jsUrl = htmlspecialchars($scriptUrl, \ENT_QUOTES, 'UTF-8');
        $script = "\n        <script type=\"module\" src=\"{$jsUrl}\"></script>\n";

        $injection = <<<HTML

            <!-- UX Inspector -->
            <ux-inspector id="ux-inspector" data-turbo-permanent data-config="{$configAttr}"></ux-inspector>

            HTML;

        $content = substr_replace($content, $injection, $bodyPos, 0);
        $content = substr_replace($content, $script, $headPos, 0);
        $response->setContent($content);
        $response->headers->remove('Content-Length');
    }

    /**
     * @return array{int, int}|null
     */
    private function findInjectionPositions(string $html): ?array
    {
        // Locate tag boundaries without rewriting the document or interpreting raw text as markup.
        $tag = <<<'REGEX'
            ~<!--.*?(?:--!?>|$)|<(?:[!?]|(/?)([a-z][a-z0-9:-]*))(?:[^"'>]|"[^"]*"|'[^']*')*>~is
            REGEX;
        $head = $body = null;
        $offset = $templates = 0;
        $inBody = false;
        while (preg_match($tag, $html, $match, \PREG_OFFSET_CAPTURE, $offset)) {
            $offset = $match[0][1] + \strlen($match[0][0]);
            if (!isset($match[2])) {
                continue;
            }
            $name = strtolower($match[2][0]);
            $closing = '/' === $match[1][0];
            if ('template' === $name) {
                $templates = max(0, $templates + ($closing ? -1 : 1));
            }
            if ($closing) {
                if (!$templates && 'head' === $name && !$inBody) {
                    $head ??= $match[0][1];
                } elseif (!$templates && 'body' === $name && $inBody) {
                    $body = $match[0][1];
                }
            } elseif (!$templates && 'ux-inspector' === $name) {
                return null;
            } elseif (!$templates && 'body' === $name) {
                $inBody = true;
            } elseif ('plaintext' === $name) {
                break;
            } elseif (\in_array($name, ['script', 'style', 'textarea', 'title', 'xmp', 'iframe', 'noembed', 'noframes', 'noscript'], true)) {
                if (!preg_match('~</'.$name.'(?=[\s/>])~i', $html, $end, \PREG_OFFSET_CAPTURE, $offset)) {
                    break;
                }
                $offset = $end[0][1];
            }
        }

        return null === $body ? null : [$head ?? $body, $body];
    }
}
