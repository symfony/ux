<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Bridge\Ipx;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Image\Exception\IncompleteDsnException;
use Symfony\UX\Image\Provider\AbstractProviderFactory;
use Symfony\UX\Image\Provider\Dsn;
use Symfony\UX\Image\Provider\ProviderFactoryInterface;
use Symfony\UX\Image\Provider\ProviderInterface;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final class IpxProviderFactory extends AbstractProviderFactory implements ProviderFactoryInterface
{
    public function create(Dsn $dsn): ProviderInterface
    {
        $options = $this->resolveOptions($dsn);

        if (null === $host = $dsn->getHost()) {
            throw new IncompleteDsnException('The IPX image provider requires a host, e.g. "ipx://images.example.com".');
        }

        return new IpxProvider($host, $dsn->getPort(), $dsn->getPath() ?? '', 'true' === $options['secure']);
    }

    protected function getSupportedSchemes(): array
    {
        return ['ipx'];
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefault('secure', 'true')
            ->setAllowedValues('secure', ['true', 'false'])
        ;
    }
}
