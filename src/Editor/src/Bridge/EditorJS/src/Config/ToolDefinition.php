<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Bridge\EditorJS\Config;

final class ToolDefinition
{
    public function __construct(
        public readonly string $class,
        public readonly array $config = [],
        public readonly bool $inlineToolbar = true,
        public readonly ?string $shortcut = null,
    ) {
    }

    public function toArray(): array
    {
        $out = ['class' => $this->class, 'inlineToolbar' => $this->inlineToolbar];
        if ([] !== $this->config) {
            $out['config'] = $this->config;
        }
        if (null !== $this->shortcut) {
            $out['shortcut'] = $this->shortcut;
        }

        return $out;
    }
}
