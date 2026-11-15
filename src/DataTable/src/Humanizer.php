<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DataTable;

/**
 * @internal
 *
 * @author Simon Daigre <simon@daig.re>
 */
final class Humanizer
{
    /**
     * Turns a name into a label: "createdAt" and "category.name" give "Created at" and "Category name".
     */
    public static function humanize(string $name): string
    {
        return ucfirst(strtolower(trim((string) preg_replace(['/([A-Z])/', '/[_.\s]+/'], ['_$1', ' '], $name))));
    }
}
