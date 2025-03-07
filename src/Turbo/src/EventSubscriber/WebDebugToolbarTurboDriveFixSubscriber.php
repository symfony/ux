<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Turbo\EventSubscriber;

use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Allows the Web Debug Toolbar (WDT) when navigating via Turbo Drive when a strict Content-Security-Policy is set.
 * This is done by reusing WDT nonces.
 */
#[When(env: 'dev')]
class WebDebugToolbarTurboDriveFixSubscriber implements EventSubscriberInterface
{
    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $routeName = $request->get('_route');

        if ('_wdt' === $routeName) {
            return;
        }
        if ($request->headers->has('X-Turbo-Request-Id')) {
            return;
        }
        if ('html' !== $request->getRequestFormat()) {
            return;
        }

        $response = $event->getResponse();
        $content = $response->getContent();

        $scriptContent = <<<'EOD'
            document.addEventListener('turbo:before-fetch-request', (event) =>
            {
                var wdt = document.querySelector('.sf-toolbar');
                if (wdt)
                {
                    let wdtStyle = wdt.nextElementSibling;
                    let wdtScript = wdtStyle.nextElementSibling;

                    if (wdtStyle.nonce) {event.detail.fetchOptions.headers['X-SymfonyProfiler-Style-Nonce'] = wdtStyle.nonce;}
                    if (wdtScript.nonce) {event.detail.fetchOptions.headers['X-SymfonyProfiler-Script-Nonce'] = wdtScript.nonce;}
                }
            });
        EOD;
        $scriptTag = '<script>'.$scriptContent.'</script>';

        $hash = base64_encode(hash('sha256', $scriptContent, true));
        $hashString = "'sha256-".$hash."'";

        $csp = $response->headers->get('Content-Security-Policy');
        if ($csp) {
            if (preg_match('/script-src\s+([^;]+)/', $csp, $matches)) {
                $scriptSrc = $matches[1];

                if (!str_contains($scriptSrc, $hashString)) {
                    $newScriptSrc = $scriptSrc.' '.$hashString;
                    $csp = str_replace($matches[0], 'script-src '.$newScriptSrc, $csp);
                }
            } else {
                $csp .= "; script-src 'self' ".$hashString;
            }
            $response->headers->set('Content-Security-Policy', $csp);
        }

        $modifiedContent = str_replace('</head>', $scriptTag.'</head>', $content);
        $response->setContent($modifiedContent);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', -999999],
        ];
    }
}
