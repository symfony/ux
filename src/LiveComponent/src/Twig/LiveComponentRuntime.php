<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Twig;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\LiveComponentHydrator;
use Twig\Environment;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @experimental
 */
final class LiveComponentRuntime
{
    private LiveComponentHydrator $hydrator;
    private UrlGeneratorInterface $urlGenerator;
    private ?CsrfTokenManagerInterface $csrfTokenManager;

    public function __construct(LiveComponentHydrator $hydrator, UrlGeneratorInterface $urlGenerator, CsrfTokenManagerInterface $csrfTokenManager = null)
    {
        $this->hydrator = $hydrator;
        $this->urlGenerator = $urlGenerator;
        $this->csrfTokenManager = $csrfTokenManager;
    }

    public function renderLiveAttributes(Environment $env, object $component): string
    {
        $url = $this->urlGenerator->generate('live_component', ['component' => AsLiveComponent::forClass($component::class)->getName()]);
        $data = $this->hydrator->dehydrate($component);

        $ret = sprintf(
            'data-controller="live" data-live-url-value="%s" data-live-data-value="%s"',
            twig_escape_filter($env, $url, 'html_attr'),
            twig_escape_filter($env, json_encode($data, \JSON_THROW_ON_ERROR), 'html_attr'),
        );

        if (!$this->csrfTokenManager) {
            return $ret;
        }

        return sprintf('%s data-live-csrf-value="%s"',
            $ret,
            $this->csrfTokenManager->getToken(AsLiveComponent::forClass($component::class)->getName())->getValue()
        );
    }

    public function getComponentUrl(object $component): string
    {
        $data = $this->hydrator->dehydrate($component);
        $params = ['component' => AsLiveComponent::forClass($component::class)->getName()] + $data;

        return $this->urlGenerator->generate('live_component', $params);
    }
}
