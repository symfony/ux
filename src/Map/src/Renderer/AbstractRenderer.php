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

use Symfony\UX\Map\Icon\IconType;
use Symfony\UX\Map\Icon\UxIconRenderer;
use Symfony\UX\Map\Map;
use Symfony\UX\Map\MapOptionsInterface;
use Symfony\UX\StimulusBundle\Helper\StimulusHelper;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 */
abstract class AbstractRenderer implements RendererInterface
{
    public function __construct(
        private readonly StimulusHelper $stimulus,
        private readonly UxIconRenderer $uxIconRenderer,
    ) {
    }

    abstract protected function getName(): string;

    abstract protected function getProviderOptions(): array;

    abstract protected function getDefaultMapOptions(): MapOptionsInterface;

    /**
     * @template T of MapOptionsInterface
     *
     * @param T $options
     *
     * @return T
     */
    protected function tapOptions(MapOptionsInterface $options): MapOptionsInterface
    {
        return $options;
    }

    final public function renderMap(Map $map, array $attributes = []): string
    {
        if (!$map->hasOptions()) {
            $map->options($this->getDefaultMapOptions());
        } elseif (!$map->getOptions() instanceof ($defaultMapOptions = $this->getDefaultMapOptions())) {
            $map->options($defaultMapOptions);
        }

        $map->options($this->tapOptions($map->getOptions()));

        $controllers = [];
        if ($attributes['data-controller'] ?? null) {
            $controllers[$attributes['data-controller']] = [];
        }
        $controllers['@symfony/ux-'.$this->getName().'-map/map'] = [
            'provider-options' => (object) $this->getProviderOptions(),
            ...$this->getMapAttributes($map),
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

        return \sprintf('<div %s></div>', $stimulusAttributes);
    }

    private function getMapAttributes(Map $map): array
    {
        $computeId = fn (array $array) => hash('xxh3', json_encode($array, \JSON_THROW_ON_ERROR));

        $attrs = $map->toArray();

        foreach ($attrs['markers'] as $key => $marker) {
            if (isset($marker['icon']['type']) && IconType::UxIcon->value === $marker['icon']['type']) {
                $attrs['markers'][$key]['icon']['_generated_html'] = $this->uxIconRenderer->render($marker['icon']['name'], [
                    'width' => $marker['icon']['width'],
                    'height' => $marker['icon']['height'],
                ]);
            }
            $attrs['markers'][$key]['@id'] = $computeId($marker);
        }
        foreach ($attrs['polygons'] as $key => $polygon) {
            $attrs['polygons'][$key]['@id'] = $computeId($polygon);
        }

        foreach ($attrs['polylines'] as $key => $polyline) {
            $attrs['polylines'][$key]['@id'] = $computeId($polyline);
        }

        foreach ($attrs['circles'] as $key => $circle) {
            $attrs['circles'][$key]['@id'] = $computeId($circle);
        }

        foreach ($attrs['rectangles'] as $key => $rectangle) {
            $attrs['rectangles'][$key]['@id'] = $computeId($rectangle);
        }

        return $attrs;
    }
}
