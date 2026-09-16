<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Icons;

/**
 * Provides the names of the icons used by an application.
 *
 * Implementations are registered as services tagged "ux_icons.finder", and are
 * used to lock and warm up icons.
 *
 * @author Pierre du Plessis <pierre@pcservice.co.za>
 */
interface IconFinderInterface
{
    /**
     * Names are either prefixed ("lucide:circle") or, for icons stored in the
     * local icon directory, bare ("circle").
     *
     * @return iterable<string>
     */
    public function icons(): iterable;
}
