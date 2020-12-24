<?php

/*
 * This file is part of the Symfony UX Turbo package.
 *
 * (c) Kévin Dunglas <kevin@dunglas.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Symfony\UX\Turbo;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @author Kévin Dunglas <kevin@dunglas.fr>
 */
final class TurboBundle extends Bundle
{
    public const STREAM_FORMAT = 'turbo_stream';
    public const STREAM_MEDIA_TYPE = 'text/html; turbo-stream';
}
