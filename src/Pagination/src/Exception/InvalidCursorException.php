<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Pagination\Exception;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class InvalidCursorException extends BadRequestHttpException implements ExceptionInterface
{
    public function __construct(string $message = 'Invalid cursor value.', ?\Throwable $previous = null)
    {
        parent::__construct($message, $previous);
    }
}
