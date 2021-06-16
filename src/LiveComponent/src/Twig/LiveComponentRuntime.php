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
use Symfony\UX\LiveComponent\LiveComponentInterface;
use Symfony\UX\TwigComponent\ComponentInterface;
use Twig\Environment;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @experimental
 */
final class LiveComponentRuntime
{
    /** @var LiveComponentHydrator */
    private $hydrator;

    /** @var UrlGeneratorInterface */
    private $urlGenerator;

    /** @var CsrfTokenManagerInterface|null */
    private $csrfTokenManager;

    public function __construct(LiveComponentHydrator $hydrator, UrlGeneratorInterface $urlGenerator, CsrfTokenManagerInterface $csrfTokenManager = null)
    {
        $this->hydrator = $hydrator;
        $this->urlGenerator = $urlGenerator;
        $this->csrfTokenManager = $csrfTokenManager;
    }

    public function renderLiveAttributes(Environment $env, ComponentInterface $component): string
    {
        if (!$component instanceof LiveComponentInterface) {
            throw new \InvalidArgumentException(sprintf('The "%s" component (%s) is not a LiveComponent. Don\'t forget to implement LiveComponentInterface', $component::getComponentName(), get_class($component)));
        }

        $url = $this->urlGenerator->generate('live_component', ['component' => $component::getComponentName()]);
        $data = $this->hydrator->dehydrate($component);

        $ret = \sprintf(
            'data-controller="live" data-live-url-value="%s" data-live-data-value="%s"',
            twig_escape_filter($env, $url, 'html_attr'),
            twig_escape_filter($env, \json_encode($data, \JSON_THROW_ON_ERROR), 'html_attr'),

        );

        if (!$this->csrfTokenManager) {
            return $ret;
        }

        return \sprintf('%s data-live-csrf-value="%s"',
            $ret,
            $this->csrfTokenManager->getToken($component::getComponentName())->getValue()
        );
    }

    public function getComponentUrl(LiveComponentInterface $component): string
    {
        $data = $this->hydrator->dehydrate($component);
        $params = ['component' => $component::getComponentName()] + $data;

        return $this->urlGenerator->generate('live_component', $params);
    }
}
