<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Bridge\Glide;

use League\Glide\Server;
use League\Glide\ServerFactory as LeagueServerFactory;
use Symfony\UX\Image\Exception\InvalidArgumentException;
use Symfony\UX\Image\Provider\Dsn;

/**
 * Builds the League\Glide\Server from the provider DSN at service-instantiation time, not at
 * container-compile time -- an "%env(...)%" DSN placeholder isn't a real "glide://" string yet then.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class ServerFactory
{
    // Bounds Glide's otherwise-unbounded resize endpoint; still covers the widest default breakpoint at common ratios.
    public const int DEFAULT_MAX_IMAGE_SIZE = 25_000_000;

    public static function createFromDsn(#[\SensitiveParameter] string $dsn): Server
    {
        $options = new Dsn($dsn);

        return LeagueServerFactory::create([
            'source' => $options->getOption('source'),
            'cache' => $options->getOption('cache'),
            'max_image_size' => self::maxImageSize($options),
            'response' => new SymfonyResponseFactory(),
        ]);
    }

    private static function maxImageSize(Dsn $dsn): int
    {
        if (null === $value = $dsn->getOption('max_image_size')) {
            return self::DEFAULT_MAX_IMAGE_SIZE;
        }

        if (!ctype_digit((string) $value) || 0 === (int) $value) {
            throw new InvalidArgumentException(\sprintf('The Glide "max_image_size" DSN option must be a positive number of output pixels, "%s" given.', $value));
        }

        return (int) $value;
    }
}
