<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Exception;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class ComponentAlreadyExistsException extends \RuntimeException
{
    public function __construct(
        public readonly string $componentName,
    ) {
        parent::__construct(\sprintf('The component "%s" already exists.', $this->componentName));
    }
}
