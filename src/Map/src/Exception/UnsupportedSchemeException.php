<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Map\Exception;

use Symfony\UX\Map\Renderer\Dsn;
use Symfony\UX\Map\UXMapBundle;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 */
class UnsupportedSchemeException extends InvalidArgumentException
{
    public function __construct(Dsn $dsn, ?\Throwable $previous = null)
    {
        $provider = $dsn->getScheme();
        $bridge = UXMapBundle::$bridges[$provider] ?? null;
        if ($bridge && !class_exists($bridge['renderer_factory'])) {
            parent::__construct(\sprintf('Unable to render maps via "%s" as the bridge is not installed. Try running "composer require symfony/ux-%s-map".', $provider, $provider));

            return;
        }

        parent::__construct(
            \sprintf('The renderer "%s" is not supported.', $dsn->getScheme()),
            0,
            $previous
        );
    }
}
