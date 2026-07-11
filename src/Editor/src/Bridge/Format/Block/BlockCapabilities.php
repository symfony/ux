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

use Symfony\UX\Editor\Config\BridgeCapabilities;

final class BlockCapabilities
{
    public static function default(): BridgeCapabilities
    {
        return new BridgeCapabilities(
            supportsToolbar: false,
            supportsPlugins: true,
            supportsTheme: false,
            supportsLanguage: true,
            supportedFormats: ['blocks'],
        );
    }
}
