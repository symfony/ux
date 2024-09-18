<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Map\Renderer;

use Symfony\UX\Map\Map;
use Symfony\UX\Map\MapOptionsInterface;
use Symfony\UX\StimulusBundle\Helper\StimulusHelper;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 */
abstract readonly class AbstractRenderer implements RendererInterface
{
    public function __construct(
        private StimulusHelper $stimulus,
    ) {
    }

    abstract protected function getName(): string;

    abstract protected function getProviderOptions(): array;

    abstract protected function getDefaultMapOptions(): MapOptionsInterface;

    final public function renderMap(Map $map, array $attributes = []): string
    {
        if (!$map->hasOptions()) {
            $map->options($this->getDefaultMapOptions());
        } elseif (!$map->getOptions() instanceof ($defaultMapOptions = $this->getDefaultMapOptions())) {
            $map->options($defaultMapOptions);
        }

        $controllers = [];
        if ($attributes['data-controller'] ?? null) {
            $controllers[$attributes['data-controller']] = [];
        }
        $controllers['@symfony/ux-'.$this->getName().'-map/map'] = [
            'provider-options' => (object) $this->getProviderOptions(),
        ];

        $stimulusAttributes = $this->stimulus->createStimulusAttributes();
        foreach ($controllers as $name => $controllerValues) {
            $stimulusAttributes->addController($name, $controllerValues);
        }

        foreach ($attributes as $name => $value) {
            if ('data-controller' === $name) {
                continue;
            }

            if (true === $value) {
                $stimulusAttributes->addAttribute($name, $name);
            } elseif (false !== $value) {
                $stimulusAttributes->addAttribute($name, $value);
            }
        }

        return \sprintf('<div %s %s></div>',
            $stimulusAttributes,
            'data-symfony--ux-'.$this->getName().'-map--map-view-value="'.htmlentities(json_encode($map->toArray(), flags: \JSON_THROW_ON_ERROR)).'"'
        );
    }
}
