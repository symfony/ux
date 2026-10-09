<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Bridge\Cloudinary;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Image\Exception\IncompleteDsnException;
use Symfony\UX\Image\Provider\AbstractProviderFactory;
use Symfony\UX\Image\Provider\Dsn;
use Symfony\UX\Image\Provider\ProviderFactoryInterface;
use Symfony\UX\Image\Provider\ProviderInterface;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class CloudinaryProviderFactory extends AbstractProviderFactory implements ProviderFactoryInterface
{
    public function create(Dsn $dsn): ProviderInterface
    {
        $options = $this->resolveOptions($dsn);

        if (null === $cloudName = $dsn->getHost()) {
            throw new IncompleteDsnException('The Cloudinary image provider requires a cloud name, e.g. "cloudinary://my-cloud".');
        }

        return new CloudinaryProvider($cloudName, $options['origin'] ?? null, $options['api_secret'] ?? null);
    }

    protected function getSupportedSchemes(): array
    {
        return ['cloudinary'];
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefined('origin')
            ->setAllowedTypes('origin', 'string')
            ->setAllowedValues('origin', static function (string $origin): bool {
                $parts = parse_url($origin);

                return \is_array($parts)
                    && \in_array($parts['scheme'] ?? null, ['http', 'https'], true)
                    && isset($parts['host'])
                    && !isset($parts['query'])
                    && !isset($parts['fragment']);
            })
            ->setDefined('api_secret')
            ->setAllowedTypes('api_secret', 'string')
            ->setAllowedValues('api_secret', static fn (string $value): bool => '' !== $value)
        ;
    }
}
