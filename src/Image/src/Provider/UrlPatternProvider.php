<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Provider;

/**
 * Provider that generates URLs using a configurable pattern template.
 *
 * Example pattern: "/img/{src}?w={width}&h={height}&f={format}&q={quality}"
 *
 * This allows integration with any CDN that supports URL-based transformations
 * (Cloudflare, imgix, Cloudinary, etc.) without writing custom code.
 *
 * @author Aleksey Razbakov <aleksey@razbakov.com>
 */
class UrlPatternProvider implements ProviderInterface
{
    private string $pattern;

    public function getName(): string
    {
        return 'url_pattern';
    }

    public function configure(array $config): void
    {
        if (empty($config['pattern'])) {
            throw new \InvalidArgumentException('The "pattern" option is required for UrlPatternProvider.');
        }

        $this->pattern = $config['pattern'];
    }

    public function getImage(string $src, array $modifiers): string
    {
        $url = $this->pattern;

        $replacements = [
            '{src}' => ltrim($src, '/'),
            '{width}' => $modifiers['width'] ?? '',
            '{height}' => $modifiers['height'] ?? '',
            '{format}' => $modifiers['format'] ?? '',
            '{quality}' => $modifiers['quality'] ?? '',
            '{fit}' => $modifiers['fit'] ?? '',
            '{focal}' => $modifiers['focal'] ?? '',
            '{ratio}' => $modifiers['ratio'] ?? '',
        ];

        $url = str_replace(array_keys($replacements), array_values($replacements), $url);

        // Remove empty query parameters
        $url = preg_replace('/[?&]\w*=[&]?/', '', $url);
        $url = rtrim($url, '?');

        return $url;
    }
}
