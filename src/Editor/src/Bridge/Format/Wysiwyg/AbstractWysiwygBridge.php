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

use Symfony\UX\Editor\Bridge\AbstractBridge;
use Symfony\UX\Editor\Config\BridgeCapabilities;

abstract class AbstractWysiwygBridge extends AbstractBridge
{
    public function getCapabilities(): BridgeCapabilities
    {
        return WysiwygCapabilities::default();
    }
}
