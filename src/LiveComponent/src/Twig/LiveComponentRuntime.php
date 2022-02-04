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
use Symfony\UX\LiveComponent\LiveComponentHydrator;
use Symfony\UX\TwigComponent\ComponentFactory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @experimental
 */
final class LiveComponentRuntime
{
    public function __construct(
        private LiveComponentHydrator $hydrator,
        private ComponentFactory $factory,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function getComponentUrl(string $name, array $props = []): string
    {
        $component = $this->factory->create($name, $props);
        $params = ['component' => $name] + $this->hydrator->dehydrate($component);

        return $this->urlGenerator->generate('live_component', $params);
    }
}
