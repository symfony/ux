<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Bridge\Format\Wysiwyg;

use Symfony\UX\Editor\Config\AbstractEditorConfig;
use Symfony\UX\Editor\Config\BridgeCapabilities;
use Symfony\UX\Editor\Config\CommonOptions;

abstract class AbstractWysiwygConfig extends AbstractEditorConfig
{
    public function getCapabilities(): BridgeCapabilities
    {
        return WysiwygCapabilities::default();
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
        if (null !== $c->language) {
            $out['language'] = $c->language;
        }
        if ([] !== $c->plugins) {
            $out['plugins'] = $c->plugins;
        }
        if (null !== $c->theme) {
            $out['theme'] = $c->theme;
        }
        if (null !== $c->toolbar) {
            $out['toolbar'] = $c->toolbar;
        }
        if (null !== $c->height) {
            $out['height'] = $c->height;
        }

        return $out;
    }
}
