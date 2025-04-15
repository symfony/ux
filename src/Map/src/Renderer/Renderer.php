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

use Symfony\UX\Map\Exception\UnsupportedSchemeException;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class Renderer
{
    public function __construct(
        /**
         * @param iterable<RendererFactoryInterface> $factories
         */
        private readonly iterable $factories,
    ) {
    }

    public function fromStrings(#[\SensitiveParameter] array $dsns): Renderers
    {
        $renderers = [];
        foreach ($dsns as $name => $dsn) {
            $renderers[$name] = $this->fromString($dsn);
        }

        return new Renderers($renderers);
    }

    public function fromString(#[\SensitiveParameter] string $dsn): RendererInterface
    {
        return $this->fromDsnObject(new Dsn($dsn));
    }

    public function fromDsnObject(Dsn $dsn): RendererInterface
    {
        foreach ($this->factories as $factory) {
            if ($factory->supports($dsn)) {
                return $factory->create($dsn);
            }
        }

        throw new UnsupportedSchemeException($dsn);
    }
}
