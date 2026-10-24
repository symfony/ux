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
class ResolverException extends InvalidArgumentException
{
    /**
     * @param non-empty-list<string> $errors
     */
    public function __construct(private readonly array $errors)
    {
        parent::__construct(implode("\n", $errors));
    }

    /** @return non-empty-list<string> */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
