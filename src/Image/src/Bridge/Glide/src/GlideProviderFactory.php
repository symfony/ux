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

use League\Glide\ServerFactory as LeagueServerFactory;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Image\Exception\IncompleteDsnException;
use Symfony\UX\Image\Provider\AbstractProviderFactory;
use Symfony\UX\Image\Provider\Dsn;
use Symfony\UX\Image\Provider\ProviderFactoryInterface;
use Symfony\UX\Image\Provider\ProviderInterface;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final class GlideProviderFactory extends AbstractProviderFactory implements ProviderFactoryInterface
{
    public function create(Dsn $dsn): ProviderInterface
    {
        // The host is just a placeholder (e.g. "default"); this provider needs the DSN's path as its URL prefix.
        if (null === $urlPrefix = $dsn->getPath()) {
            throw new IncompleteDsnException('The Glide image provider requires a URL prefix, e.g. "glide://default/images".');
        }

        $options = $this->resolveGlideOptions($dsn);

        $driver = EncodableFormats::driverOf(new LeagueServerFactory(['driver' => $options['driver']])->getImageManager());

        return new GlideProvider($urlPrefix, $options['sign_key'] ?? null, EncodableFormats::filter($driver, GlideProvider::SUPPORTED_FORMATS));
    }

    /**
     * Shared with the server and signature factories, so the controller never reads an option the provider would reject.
     *
     * @return array<string, string>
     *
     * @internal
     */
    public function resolveGlideOptions(Dsn $dsn): array
    {
        $options = $this->resolveOptions($dsn);

        foreach (['source', 'cache'] as $option) {
            if (!isset($options[$option])) {
                throw new IncompleteDsnException(\sprintf('The Glide image provider requires a "%s" directory, e.g. "glide://default/images?source=/app/public&cache=/app/var/glide-cache".', $option));
            }
        }

        return $options;
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefined(['source', 'cache', 'sign_key', 'max_image_size']);
        $resolver->setDefault('driver', 'gd');
        $resolver->setAllowedValues('driver', ['gd', 'imagick']);
        $resolver->setDefault('max_age', '31536000');
        $resolver->setAllowedValues('max_age', static fn (string $maxAge): bool => ctype_digit($maxAge));
        $resolver->setDefault('visibility', 'public');
        $resolver->setAllowedValues('visibility', ['public', 'private']);
        $resolver->setNormalizer('sign_key', static fn (Options $options, string $signKey): string => '' !== $signKey ? $signKey : throw new InvalidOptionsException('The "sign_key" option must not be empty.'));
    }

    protected function getSupportedSchemes(): array
    {
        return ['glide'];
    }
}
