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

final class NullRendererFactory implements RendererFactoryInterface
{
    /**
     * @param array<string> $availableBridges
     */
    public function __construct(
        private readonly array $availableBridges = [],
    ) {
    }

    public function create(Dsn $dsn): RendererInterface
    {
        if (!$this->supports($dsn)) {
            throw new UnsupportedSchemeException($dsn);
        }

        return new NullRenderer($this->availableBridges);
    }

    public function supports(Dsn $dsn): bool
    {
        return 'null' === $dsn->getScheme();
    }
}
