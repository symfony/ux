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
use Symfony\UX\LiveComponent\LiveComponentHydrator;
use Symfony\UX\TwigComponent\ComponentFactory;
use Twig\Environment;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @experimental
 */
final class LiveComponentRuntime
{
    private LiveComponentHydrator $hydrator;
    private ComponentFactory $factory;
    private UrlGeneratorInterface $urlGenerator;
    private ?CsrfTokenManagerInterface $csrfTokenManager;

    public function __construct(LiveComponentHydrator $hydrator, ComponentFactory $factory, UrlGeneratorInterface $urlGenerator, CsrfTokenManagerInterface $csrfTokenManager = null)
    {
        $this->hydrator = $hydrator;
        $this->factory = $factory;
        $this->urlGenerator = $urlGenerator;
        $this->csrfTokenManager = $csrfTokenManager;
    }

    public function renderLiveAttributes(Environment $env, object $component, string $name = null): string
    {
        $name = $this->nameFor($component, $name);
        $url = $this->urlGenerator->generate('live_component', ['component' => $name]);
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
            $this->csrfTokenManager->getToken($name)->getValue()
        );
    }

    public function getComponentUrl(string $name, array $props = []): string
    {
        $component = $this->factory->create($name, $props);
        $params = ['component' => $name] + $this->hydrator->dehydrate($component);

        return $this->urlGenerator->generate('live_component', $params);
    }

    private function nameFor(object $component, string $name = null): string
    {
        return $this->factory->configFor($component, $name)['name'];
    }
}
