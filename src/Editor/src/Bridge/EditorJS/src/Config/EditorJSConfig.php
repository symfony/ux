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

use Symfony\UX\Editor\Bridge\Format\Block\AbstractBlockConfig;
use Symfony\UX\Editor\Config\CommonOptions;

final class EditorJSConfig extends AbstractBlockConfig
{
    /**
     * @param array<string, ToolDefinition> $tools
     */
    public function __construct(
        CommonOptions $common = new CommonOptions(),
        public readonly array $tools = [],
        public readonly ?string $defaultBlock = 'paragraph',
        public readonly ?int $minHeight = null,
        public readonly ?string $logLevel = null,
        array $nativeOverrides = [],
    ) {
        parent::__construct($common, $nativeOverrides);
    }

    public function getBridgeId(): string
    {
        return 'editorjs';
    }

    protected function translateOwn(): array
    {
        $out = [];
        if ([] !== $this->tools) {
            $out['tools'] = array_map(static fn (ToolDefinition $t): array => $t->toArray(), $this->tools);
        }
        if (null !== $this->defaultBlock) {
            $out['defaultBlock'] = $this->defaultBlock;
        }
        if (null !== $this->minHeight) {
            $out['minHeight'] = $this->minHeight;
        }
        if (null !== $this->logLevel) {
            $out['logLevel'] = $this->logLevel;
        }

        return $out;
    }
}
