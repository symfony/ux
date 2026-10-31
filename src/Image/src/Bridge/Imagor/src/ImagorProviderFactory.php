<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Bridge\Imagor;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Image\Exception\IncompleteDsnException;
use Symfony\UX\Image\Provider\AbstractProviderFactory;
use Symfony\UX\Image\Provider\Dsn;
use Symfony\UX\Image\Provider\ProviderFactoryInterface;
use Symfony\UX\Image\Provider\ProviderInterface;

/**
 * @author Symfony Community <https://symfony.com/contributors>
 */
final class ImagorProviderFactory extends AbstractProviderFactory implements ProviderFactoryInterface
{
    public function create(Dsn $dsn): ProviderInterface
    {
        $options = $this->resolveOptions($dsn);

        if (null === $host = $dsn->getHost()) {
            throw new IncompleteDsnException('The Imagor image provider requires a host, e.g. "imagor://images.example.com".');
        }

        $scheme = 'true' === $options['secure'] ? 'https' : 'http';
        $authority = null !== $dsn->getPort() ? $host.':'.$dsn->getPort() : $host;
        $endpoint = $scheme.'://'.$authority.($dsn->getPath() ?? '');

        return new ImagorProvider(
            $endpoint,
            $options['sign_key'] ?? null,
            $options['origin'] ?? null,
            $options['signer'],
            (int) $options['sign_truncate'],
        );
    }

    protected function getSupportedSchemes(): array
    {
        return ['imagor'];
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefault('secure', 'true')
            ->setAllowedValues('secure', ['true', 'false'])
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
            ->setDefined('sign_key')
            ->setAllowedTypes('sign_key', 'string')
            ->setAllowedValues('sign_key', static fn (string $value): bool => '' !== $value)
            ->setDefault('signer', 'sha1')
            ->setAllowedValues('signer', ['sha1', 'sha256', 'sha512'])
            // Imagor reads a path segment as a signature only from 17 characters; 88 is a whole SHA-512 one
            ->setDefault('sign_truncate', '0')
            ->setAllowedValues('sign_truncate', static function (string $value): bool {
                return '0' === $value || (ctype_digit($value) && (int) $value >= 17 && (int) $value <= 88);
            })
        ;
    }
}
