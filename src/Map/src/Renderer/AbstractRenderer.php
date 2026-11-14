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
        // the "@id" is appended to the JSON of the element, so that StimulusAttributes does not encode it again
        $encode = static function (array $element, ?string $id = null): string {
            $json = json_encode($element, \JSON_THROW_ON_ERROR);

            return substr($json, 0, -1).',"@id":"'.($id ?? hash('xxh3', $json)).'"}';
        };

        $attrs = $map->toArray();
        $icons = [];
        $generatedIcons = [];

        foreach ($attrs['markers'] as $key => $marker) {
            $id = null;
            if (isset($marker['icon']['type']) && IconType::UxIcon->value === $marker['icon']['type']) {
                $id = hash('xxh3', json_encode($marker, \JSON_THROW_ON_ERROR));
                $icon = $marker['icon'];
                $iconKey = $icon['name'].'|'.$icon['width'].'|'.$icon['height'];
                $generatedHtml = $generatedIcons[$iconKey] ??= $this->uxIconRenderer->render($icon['name'], [
                    'width' => $icon['width'],
                    'height' => $icon['height'],
                ]);
                // Markers overwhelmingly share the same icon, so each SVG is written once and referenced by its index.
                $marker['icon']['_generated_html'] = $icons[$generatedHtml] ??= \count($icons);
            }

            $attrs['markers'][$key] = $encode($marker, $id);
        }
        $attrs['markers'] = new JsonList($attrs['markers']);

        foreach (['polygons', 'polylines', 'circles', 'rectangles'] as $type) {
            $attrs[$type] = new JsonList(array_map($encode, $attrs[$type]));
        }

        if ([] !== $icons) {
            $attrs['icons'] = array_keys($icons);
        }

        return $attrs;
    }
}
