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
        foreach (['source', 'cache'] as $option) {
            if (null === $dsn->getOption($option)) {
                throw new IncompleteDsnException(\sprintf('The Glide image provider requires a "%s" directory, e.g. "glide://default/images?source=/app/public/uploads&cache=/app/var/glide-cache".', $option));
            }
        }

        return new GlideProvider($urlPrefix, $dsn->getOption('sign_key'));
    }

    protected function getSupportedSchemes(): array
    {
        return ['glide'];
    }
}
