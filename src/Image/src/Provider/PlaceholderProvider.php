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
 * @author Aleksey Razbakov <aleksey@razbakov.com>
 */
final class PlaceholderProvider implements ProviderInterface
{
    private array $defaults = [];

    private const BASE_URL = 'https://placehold.co';

    private const KEY_MAP = [
        'width' => 'width',
        'height' => 'height',
        'background' => 'background',
        'text' => 'text',
        'text_color' => 'textColor',
        'ratio' => 'ratio',
    ];

    public function configure(array $config): void
    {
        $this->defaults = $config['defaults'] ?? [];
    }

    public function getName(): string
    {
        return 'placeholder';
    }

    public function getImage(string $src, array $modifiers): string
    {
        $params = $this->processModifiers($modifiers);

        return $this->buildUrl($params);
    }

    private function processModifiers(array $modifiers): array
    {
        $params = [];

        // Process each modifier using KEY_MAP
        foreach ($modifiers as $key => $value) {
            if (!isset(self::KEY_MAP[$key])) {
                continue;
            }

            $placeholderKey = self::KEY_MAP[$key];
            $params[$placeholderKey] = $value;
        }

        // Apply defaults for missing parameters
        foreach ($this->defaults as $key => $defaultValue) {
            if (!isset($params[self::KEY_MAP[$key]])) {
                $params[self::KEY_MAP[$key]] = $defaultValue;
            }
        }

        // Handle ratio if specified
        if (isset($params['ratio']) && null !== $params['ratio']) {
            if (preg_match('/^(\d+):(\d+)$/', $params['ratio'], $matches)) {
                $ratioWidth = (int) $matches[1];
                $ratioHeight = (int) $matches[2];
                if (!isset($params['height']) || null === $params['height']) {
                    $params['height'] = (int) ($params['width'] * $ratioHeight / $ratioWidth);
                }
            }
        }

        // Set height to width if still not specified
        if (!isset($params['height']) || null === $params['height']) {
            $params['height'] = $params['width'];
        }

        // Set default text if not specified
        if (!isset($params['text']) || null === $params['text']) {
            $params['text'] = \sprintf('%dx%d', $params['width'], $params['height']);
        }

        // Ensure background and textColor are set with defaults if needed
        $params['background'] = isset($params['background']) ? ltrim($params['background'], '#') : '000000';
        $params['textColor'] = isset($params['textColor']) ? ltrim($params['textColor'], '#') : 'FFFFFF';

        return $params;
    }

    private function buildUrl(array $params): string
    {
        return \sprintf(
            '%s/%dx%d/%s/%s?text=%s',
            self::BASE_URL,
            $params['width'],
            $params['height'],
            $params['background'],
            $params['textColor'],
            urlencode($params['text'])
        );
    }
}
