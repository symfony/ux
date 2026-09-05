<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Exception;

use Symfony\UX\Image\Provider\Dsn;
use Symfony\UX\Image\UXImageBundle;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final class UnsupportedSchemeException extends InvalidArgumentException
{
    public function __construct(Dsn $dsn, ?\Throwable $previous = null)
    {
        $provider = $dsn->getScheme();
        $bridge = UXImageBundle::$bridges[$provider] ?? null;
        if ($bridge && !class_exists($bridge['factory'])) {
            parent::__construct(\sprintf('Unable to generate images via "%s" as the bridge is not installed. Try running "composer require symfony/ux-%s-image".', $provider, $provider));

            return;
        }

        parent::__construct(
            \sprintf('The image provider "%s" is not supported.', $dsn->getScheme()),
            0,
            $previous
        );
    }
}
