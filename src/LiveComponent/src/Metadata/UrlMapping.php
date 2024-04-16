<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Metadata;

/**
 * Mapping configuration to bind a LiveProp to a URL query parameter.
 *
 * @author Nicolas Rigaud <squrious@protonmail.com>
 */
final class UrlMapping
{
    public function __construct(
        /**
         * The name of the prop that appears in the URL. If null, the LiveProp's field name is used.
         */
        public readonly ?string $as = null,
    ) {
    }
}
