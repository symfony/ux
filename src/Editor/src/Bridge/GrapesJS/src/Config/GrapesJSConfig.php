<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Bridge\GrapesJS\Config;

use Symfony\UX\Editor\Bridge\Format\Page\AbstractPageConfig;
use Symfony\UX\Editor\Config\CommonOptions;

final class GrapesJSConfig extends AbstractPageConfig
{
    public function __construct(
        CommonOptions $common = new CommonOptions(),
        public readonly array $components = [],
        public readonly array $blocks = [],
        public readonly array $storageManager = ['type' => 'none'],
        public readonly array $deviceManager = [],
        public readonly ?string $canvasCss = null,
        array $nativeOverrides = [],
    ) {
        parent::__construct($common, $nativeOverrides);
    }

    public function getBridgeId(): string
    {
        return 'grapesjs';
    }

    protected function translateOwn(): array
    {
        $out = [];
        if ([] !== $this->components) {
            $out['components'] = $this->components;
        }
        if ([] !== $this->blocks) {
            $out['blocks'] = $this->blocks;
        }
        $out['storageManager'] = $this->storageManager;
        if ([] !== $this->deviceManager) {
            $out['deviceManager'] = $this->deviceManager;
        }
        if (null !== $this->canvasCss) {
            $out['canvas'] = ['styles' => [$this->canvasCss]];
        }

        return $out;
    }
}
