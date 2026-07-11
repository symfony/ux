<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Config;

interface EditorConfigInterface
{
    public function getBridgeId(): string;

    public function getCommon(): CommonOptions;

    public function getNativeOverrides(): array;

    public function getCapabilities(): BridgeCapabilities;

    public function toNative(): array;
}
