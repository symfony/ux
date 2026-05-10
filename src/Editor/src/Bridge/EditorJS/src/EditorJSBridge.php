<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Bridge\EditorJS;

use Symfony\UX\Editor\Bridge\EditorJS\Config\EditorJSConfig;
use Symfony\UX\Editor\Bridge\EditorJS\Transformer\EditorJSTransformer;
use Symfony\UX\Editor\Bridge\Format\Block\AbstractBlockBridge;
use Symfony\UX\Editor\Config\EditorConfigInterface;
use Symfony\UX\Editor\Form\DataTransformer\EditorContentTransformerInterface;

final class EditorJSBridge extends AbstractBlockBridge
{
    public function getId(): string
    {
        return 'editorjs';
    }

    public function getDefaultConfig(): EditorConfigInterface
    {
        return new EditorJSConfig();
    }

    public function createTransformer(): EditorContentTransformerInterface
    {
        return new EditorJSTransformer();
    }
}
