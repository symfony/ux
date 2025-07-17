<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Turbo\Request;

use Symfony\Component\HttpFoundation\Request;
use Symfony\UX\Turbo\TurboBundle;

/**
 * Registers the Turbo request format for all requests.
 *
 * @author Alexander Hofbauer <a.hofbauer@fify.at>
 */
final class RequestListener
{
    public function __invoke(): void
    {
        (new Request())->setFormat(TurboBundle::STREAM_FORMAT, TurboBundle::STREAM_MEDIA_TYPE);
    }
}
