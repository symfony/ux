<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Turbo\Stream;

use Symfony\Component\HttpFoundation\Response;

/**
 * A response in the Turbo Stream format.
 *
 * @author Kévin Dunglas <kevin@dunglas.fr>
 *
 * @experimental
 */
final class TurboStreamResponse extends Response
{
    public const STREAM_FORMAT = 'turbo_stream';
    public const STREAM_MEDIA_TYPE = 'text/vnd.turbo-stream.html';
}
