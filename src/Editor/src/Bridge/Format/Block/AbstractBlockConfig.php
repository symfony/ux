<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Bridge\Format\Block;

use Symfony\UX\Editor\Config\AbstractEditorConfig;
use Symfony\UX\Editor\Config\BridgeCapabilities;
use Symfony\UX\Editor\Config\CommonOptions;

abstract class AbstractBlockConfig extends AbstractEditorConfig
{
    public function getCapabilities(): BridgeCapabilities
    {
        return BlockCapabilities::default();
    }

    protected function translateCommon(CommonOptions $c): array
    {
        $out = [];
        if (null !== $c->placeholder) {
            $out['placeholder'] = $c->placeholder;
        }
        if ($c->readOnly) {
            $out['readOnly'] = true;
        }
        if ($c->autofocus) {
            $out['autofocus'] = true;
        }
        if (null !== $c->language) {
            $out['language'] = $c->language;
        }

        return $out;
    }
}
