<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Bridge\Imgix;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Image\Exception\IncompleteDsnException;
use Symfony\UX\Image\Provider\AbstractProviderFactory;
use Symfony\UX\Image\Provider\Dsn;
use Symfony\UX\Image\Provider\ProviderFactoryInterface;
use Symfony\UX\Image\Provider\ProviderInterface;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ImgixProviderFactory extends AbstractProviderFactory implements ProviderFactoryInterface
{
    public function create(Dsn $dsn): ProviderInterface
    {
        $options = $this->resolveOptions($dsn);

        if (null === $host = $dsn->getHost()) {
            throw new IncompleteDsnException('The imgix image provider requires a source domain, e.g. "imgix://my-source.imgix.net".');
        }

        return new ImgixProvider($host, $options['sign_key'] ?? null);
    }

    protected function getSupportedSchemes(): array
    {
        return ['imgix'];
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
