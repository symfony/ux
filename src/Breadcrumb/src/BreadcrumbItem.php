<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Breadcrumb;

/**
 * A resolved crumb.
 *
 * Consumers must handle `$url === null`: it means either the current page, which is never a link, or a route that could not be generated.
 *
 * @author Romain Monteil <monteil.romain@gmail.com>
 */
final class BreadcrumbItem
{
    /**
     * @param array<string, mixed> $extra carried over untouched from the #[Breadcrumb] attribute
     */
    public function __construct(
        public readonly string $label,
        public readonly ?string $url = null,
        public readonly array $extra = [],
    ) {
    }
}
