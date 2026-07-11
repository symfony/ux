<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Bridge\Format\Page;

use Symfony\UX\Editor\Config\AbstractEditorConfig;
use Symfony\UX\Editor\Config\BridgeCapabilities;
use Symfony\UX\Editor\Config\CommonOptions;

abstract class AbstractPageConfig extends AbstractEditorConfig
{
    public function getCapabilities(): BridgeCapabilities
    {
        return PageCapabilities::default();
    }

    protected function translateCommon(CommonOptions $c): array
    {
        $out = [];
        if (null !== $c->theme) {
            $out['theme'] = $c->theme;
        }
        if (null !== $c->language) {
            $out['language'] = $c->language;
        }

        return $out;
    }
}
