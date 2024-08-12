<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Map\Bridge\Google\Renderer;

use Symfony\UX\Map\Exception\InvalidArgumentException;
use Symfony\UX\Map\Exception\UnsupportedSchemeException;
use Symfony\UX\Map\Renderer\AbstractRendererFactory;
use Symfony\UX\Map\Renderer\Dsn;
use Symfony\UX\Map\Renderer\RendererFactoryInterface;
use Symfony\UX\Map\Renderer\RendererInterface;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final class GoogleRendererFactory extends AbstractRendererFactory implements RendererFactoryInterface
{
    public function create(Dsn $dsn): RendererInterface
    {
        if (!$this->supports($dsn)) {
            throw new UnsupportedSchemeException($dsn);
        }

        $apiKey = $dsn->getUser() ?: throw new InvalidArgumentException('The Google Maps renderer requires an API key as the user part of the DSN.');

        return new GoogleRenderer(
            $this->stimulus,
            $apiKey,
            id: $dsn->getOption('id'),
            language: $dsn->getOption('language'),
            region: $dsn->getOption('region'),
            nonce: $dsn->getOption('nonce'),
            retries: $dsn->getOption('retries'),
            url: $dsn->getOption('url'),
            version: $dsn->getOption('version', 'weekly'),
            libraries: ['maps', 'marker', ...$dsn->getOption('libraries', [])],
        );
    }

    protected function getSupportedSchemes(): array
    {
        return ['google'];
    }
}
