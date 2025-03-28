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

use Symfony\UX\Map\Exception\IncompleteDsnException;
use Symfony\UX\Map\Icon\UxIconRenderer;
use Symfony\UX\StimulusBundle\Helper\StimulusHelper;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 */
abstract class AbstractRendererFactory
{
    public function __construct(
        protected StimulusHelper $stimulus,
        protected UxIconRenderer $uxIconRenderer,
    ) {
    }

    public function supports(Dsn $dsn): bool
    {
        return \in_array($dsn->getScheme(), $this->getSupportedSchemes(), true);
    }

    protected function getUser(Dsn $dsn): string
    {
        return $dsn->getUser() ?? throw new IncompleteDsnException('User is not set.');
    }

    /**
     * @return string[]
     */
    abstract protected function getSupportedSchemes(): array;
}
