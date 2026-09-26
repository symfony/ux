<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DesignTokens\Exception;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
class TokenNotFoundException extends InvalidArgumentException
{
    public function __construct(private readonly string $path, ?string $message = null)
    {
        parent::__construct($message ?? \sprintf('Design token not found: "%s".', $path));
    }

    public function getPath(): string
    {
        return $this->path;
    }
}
