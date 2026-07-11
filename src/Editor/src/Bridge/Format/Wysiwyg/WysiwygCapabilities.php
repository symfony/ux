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

use Symfony\UX\Editor\Config\BridgeCapabilities;

final class WysiwygCapabilities
{
    public static function default(): BridgeCapabilities
    {
        return new BridgeCapabilities(
            supportsToolbar: true,
            supportsPlugins: true,
            supportsTheme: true,
            supportsLanguage: true,
            supportedFormats: ['html'],
        );
    }
}
