<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Attribute;

/**
 * An attribute to register a LiveAction method.
 *
 * @see https://symfony.com/bundles/ux-live-component/current/index.html#actions
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
class LiveAction
{
}
