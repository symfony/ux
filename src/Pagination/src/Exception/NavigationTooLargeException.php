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

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class NavigationTooLargeException extends \LengthException implements ExceptionInterface
{
    public function __construct(int $requestedPages, int $maximumPages)
    {
        parent::__construct(\sprintf('Cannot render %d pagination links: full navigation is limited to %d pages. Use sliding() or increase the explicit full() limit.', $requestedPages, $maximumPages));
    }
}
