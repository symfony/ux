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
use Symfony\UX\TwigComponent\ComponentAttributes;
use Symfony\UX\TwigComponent\ComponentFactory;
use Symfony\UX\TwigComponent\ComponentMetadata;
use Twig\Environment;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @experimental
 */
final class LiveComponentRuntime
{
    public function __construct(
        private Environment $twig,
        private LiveComponentHydrator $hydrator,
        private ComponentFactory $factory,
        private UrlGeneratorInterface $urlGenerator,
        private ?CsrfTokenManagerInterface $csrfTokenManager = null
    ) {
    }

    public function getComponentUrl(string $name, array $props = []): string
    {
        $component = $this->factory->create($name, $props);
        $params = ['component' => $name] + $this->hydrator->dehydrate($component);

        return $this->urlGenerator->generate('live_component', $params);
    }

    public function getLiveAttributes(object $component, ComponentMetadata $metadata): ComponentAttributes
    {
        $url = $this->urlGenerator->generate('live_component', ['component' => $metadata->getName()]);
        $data = $this->hydrator->dehydrate($component);

        $attributes = [
            'data-controller' => 'live',
            'data-live-url-value' => twig_escape_filter($this->twig, $url, 'html_attr'),
            'data-live-data-value' => twig_escape_filter($this->twig, json_encode($data, \JSON_THROW_ON_ERROR), 'html_attr'),
        ];

        if ($this->csrfTokenManager) {
            $attributes['data-live-csrf-value'] = $this->csrfTokenManager->getToken($metadata->getName())->getValue();
        }

        return new ComponentAttributes($attributes);
    }
}
