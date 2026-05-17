<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Bridge\GrapesJS\Transformer;

use Symfony\UX\Editor\Bridge\Format\Page\AbstractPageTransformer;

final class GrapesJSTransformer extends AbstractPageTransformer
{
    public function getBridgeId(): string
    {
        return 'grapesjs';
    }
}
