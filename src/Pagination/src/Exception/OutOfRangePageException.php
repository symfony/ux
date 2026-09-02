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

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Thrown when the requested page exceeds the total number of pages.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class OutOfRangePageException extends NotFoundHttpException implements ExceptionInterface
{
    public function __construct(
        public readonly int $requestedPage,
        public readonly int $lastPage,
    ) {
        parent::__construct(\sprintf(
            'Page %d is out of range. Last page is %d.',
            $requestedPage,
            $lastPage,
        ));
    }
}
