<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent;

use Symfony\UX\TwigComponent\ComponentInterface;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @experimental
 * @internal
 */
final class DefaultComponentController
{
    /** @var LiveComponentInterface */
    private $component;

    public function __construct(ComponentInterface $component)
    {
        if (!$component instanceof LiveComponentInterface) {
            throw new \InvalidArgumentException('Not a live component.');
        }

        $this->component = $component;
    }

    public function __invoke(): void
    {
    }

    public function getComponent(): LiveComponentInterface
    {
        return $this->component;
    }
}
