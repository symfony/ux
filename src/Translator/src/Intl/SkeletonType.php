<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Translator\Intl;

/**
 * Adapted from https://github.com/formatjs/formatjs/blob/590f1f81b26934c6dc7a55fff938df5436c6f158/packages/icu-messageformat-parser/types.ts#L48-L51.
 *
 * @experimental
 */
final class SkeletonType
{
    public const NUMBER = 'number';
    public const DATE_TIME = 'dateTime';

    private function __construct()
    {
    }
}
