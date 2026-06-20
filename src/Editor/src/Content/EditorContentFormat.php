<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Content;

enum EditorContentFormat: string
{
    case Html = 'html';
    case Blocks = 'blocks';
    case Page = 'page';
}
