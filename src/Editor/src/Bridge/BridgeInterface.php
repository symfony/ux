<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Bridge;

use Symfony\UX\Editor\Config\BridgeCapabilities;
use Symfony\UX\Editor\Config\EditorConfigInterface;
use Symfony\UX\Editor\Form\DataTransformer\EditorContentTransformerInterface;

interface BridgeInterface
{
    public function getId(): string;

    public function getControllerName(): string;

    public function getDefaultConfig(): EditorConfigInterface;

    public function getCapabilities(): BridgeCapabilities;

    public function createTransformer(): EditorContentTransformerInterface;
}
