<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Form\DataTransformer;

enum StorageShape: string
{
    case Scalar = 'scalar';
    case Json = 'json';
    case Split = 'split';
}
