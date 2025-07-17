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

use Symfony\UX\Map\Exception\LogicException;
use Symfony\UX\Map\Map;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class Renderers implements RendererInterface
{
    /**
     * @var array<string, RendererInterface>
     */
    private array $renderers = [];
    private RendererInterface $default;

    /**
     * @param iterable<string, RendererInterface> $renderers
     */
    public function __construct(iterable $renderers)
    {
        foreach ($renderers as $name => $renderer) {
            $this->default ??= $renderer;
            $this->renderers[$name] = $renderer;
        }

        if (!$this->renderers) {
            throw new LogicException(\sprintf('"%s" must have at least one renderer configured.', __CLASS__));
        }
    }

    public function renderMap(Map $map, array $attributes = []): string
    {
        $renderer = $this->default;

        if ($rendererName = $map->getRendererName()) {
            if (!isset($this->renderers[$rendererName])) {
                throw new LogicException(\sprintf('The "%s" renderer does not exist (available renderers: "%s").', $rendererName, implode('", "', array_keys($this->renderers))));
            }

            $renderer = $this->renderers[$rendererName];
        }

        return $renderer->renderMap($map, $attributes);
    }

    public function __toString(): string
    {
        return implode(', ', array_keys($this->renderers));
    }
}
