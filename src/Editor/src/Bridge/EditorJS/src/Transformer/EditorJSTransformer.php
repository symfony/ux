<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Bridge\EditorJS\Transformer;

use Symfony\UX\Editor\Bridge\Format\Block\AbstractBlockTransformer;

final class EditorJSTransformer extends AbstractBlockTransformer
{
    public function getBridgeId(): string
    {
        return 'editorjs';
    }
}
