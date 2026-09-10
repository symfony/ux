<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Bridge\KeyCdn;

use Symfony\UX\Image\Exception\IncompleteDsnException;
use Symfony\UX\Image\Provider\AbstractProviderFactory;
use Symfony\UX\Image\Provider\Dsn;
use Symfony\UX\Image\Provider\ProviderFactoryInterface;
use Symfony\UX\Image\Provider\ProviderInterface;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final class KeyCdnProviderFactory extends AbstractProviderFactory implements ProviderFactoryInterface
{
    public function create(Dsn $dsn): ProviderInterface
    {
        if (null === $host = $dsn->getHost()) {
            throw new IncompleteDsnException('The KeyCDN image provider requires a host, e.g. "keycdn://myzone.kxcdn.com".');
        }

        return new KeyCdnProvider($host);
    }

    protected function getSupportedSchemes(): array
    {
        return ['keycdn'];
    }
}
