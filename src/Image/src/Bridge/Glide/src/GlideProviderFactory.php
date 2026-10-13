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

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Image\Exception\IncompleteDsnException;
use Symfony\UX\Image\Provider\AbstractProviderFactory;
use Symfony\UX\Image\Provider\Dsn;
use Symfony\UX\Image\Provider\ProviderFactoryInterface;
use Symfony\UX\Image\Provider\ProviderInterface;

/**
 * @author Romain Monteil <monteil.romain@gmail.com>
 */
final class GlideProviderFactory extends AbstractProviderFactory implements ProviderFactoryInterface
{
    public function create(Dsn $dsn): ProviderInterface
    {
        $options = $this->resolveOptions($dsn);

        if (null === $host = $dsn->getHost()) {
            throw new IncompleteDsnException('The Glide image provider requires a host, e.g. "glide://default/img" or "glide://images.example.com/img".');
        }

        return new GlideProvider(
            'default' === $host ? null : $host,
            rtrim($dsn->getPath() ?? '', '/'),
            $options['sign_key'] ?? null,
        );
    }

    protected function getSupportedSchemes(): array
    {
        return ['glide'];
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefined('sign_key')
            ->setAllowedTypes('sign_key', 'string')
            ->setAllowedValues('sign_key', static fn (string $value): bool => '' !== $value)
        ;
    }
}
