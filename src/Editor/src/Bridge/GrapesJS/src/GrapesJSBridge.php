<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Bridge\GrapesJS;

use Symfony\UX\Editor\Bridge\Format\Page\AbstractPageBuilderBridge;
use Symfony\UX\Editor\Bridge\GrapesJS\Config\GrapesJSConfig;
use Symfony\UX\Editor\Bridge\GrapesJS\Transformer\GrapesJSTransformer;
use Symfony\UX\Editor\Config\EditorConfigInterface;
use Symfony\UX\Editor\Form\DataTransformer\EditorContentTransformerInterface;

final class GrapesJSBridge extends AbstractPageBuilderBridge
{
    public function getId(): string
    {
        return 'grapesjs';
    }

    public function getDefaultConfig(): EditorConfigInterface
    {
        return new GrapesJSConfig();
    }

    public function createTransformer(): EditorContentTransformerInterface
    {
        return new GrapesJSTransformer();
    }
}
