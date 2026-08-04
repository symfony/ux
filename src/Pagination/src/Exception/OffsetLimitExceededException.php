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
final class OffsetLimitExceededException extends BadRequestHttpException implements ExceptionInterface
{
    public function __construct(
        public readonly int $page,
        public readonly int $perPage,
        public readonly int $maximumOffset,
    ) {
        parent::__construct(\sprintf(
            'Page %d with %d items per page exceeds the configured maximum offset of %d. Use cursor pagination with $paginator->cursor($source)->orderBy(...)->paginate(), or raise maxOffset() explicitly.',
            $page,
            $perPage,
            $maximumOffset,
        ));
    }
}
