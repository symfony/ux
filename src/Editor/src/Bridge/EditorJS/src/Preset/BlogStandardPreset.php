<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Bridge\EditorJS\Preset;

use Symfony\UX\Editor\Bridge\EditorJS\Config\EditorJSConfig;
use Symfony\UX\Editor\Bridge\EditorJS\Config\ToolDefinition;
use Symfony\UX\Editor\Config\CommonOptions;
use Symfony\UX\Editor\Config\EditorConfigInterface;
use Symfony\UX\Editor\Config\Preset\EditorPresetInterface;

final class BlogStandardPreset implements EditorPresetInterface
{
    public function build(): EditorConfigInterface
    {
        return new EditorJSConfig(
            common: new CommonOptions(placeholder: 'Tell your story…'),
            tools: [
                'paragraph' => new ToolDefinition('Paragraph', ['preserveBlank' => true]),
                'header' => new ToolDefinition('Header', ['levels' => [2, 3, 4], 'defaultLevel' => 2]),
                'list' => new ToolDefinition('List'),
                'image' => new ToolDefinition('Image'),
                'quote' => new ToolDefinition('Quote'),
            ],
        );
    }
}
