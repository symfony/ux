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

use Liip\ImagineBundle\Imagine\Cache\SignerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @author Aleksey Razbakov <aleksey@razbakov.com>
 */
final class LiipImagineProvider implements ProviderInterface
{
    private string $defaultFilter = 'default';
    private array $defaults = [];

    private UrlGeneratorInterface $urlGenerator;
    private ?SignerInterface $signer;

    private const KEY_MAP = [
        'width' => 'size',
        'height' => 'size',
        'quality' => 'quality',
        'fit' => 'mode',
        'background' => 'background',
        'format' => 'format',
        'ratio' => 'ratio',
    ];

    private const VALUE_MAP = [
        'fit' => [
            'fill' => 'outbound',
            'inside' => 'inset',
            'outside' => 'outbound',
            'cover' => 'outbound',
            'contain' => 'inset',
        ],
        'format' => [
            'jpeg' => 'jpg',
            'auto' => 'jpg',
        ],
    ];

    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        ?SignerInterface $signer = null,
    ) {
        $this->urlGenerator = $urlGenerator;
        $this->signer = $signer;
    }

    public function configure(array $config): void
    {
        $this->defaultFilter = $config['default_filter'] ?? 'default';
        $this->defaults = $config['defaults'] ?? [];
    }

    public function getName(): string
    {
        return 'liip_imagine';
    }

    public function getImage(string $src, array $modifiers): string
    {
        $src = ltrim($src, '/');
        $filterName = $this->defaultFilter;

        return $this->generateSecureUrl($src, $filterName, $modifiers);
    }

    private function generateSecureUrl(string $path, string $filter, array $modifiers): string
    {
        if (null === $this->signer) {
            throw new \LogicException('LiipImagineBundle is not installed. Please install it first: composer require liip/imagine-bundle');
        }

        $runtimeConfig = $this->buildRuntimeConfig($modifiers);
        $hash = $this->signer->sign($path, $runtimeConfig);

        return $this->urlGenerator->generate('liip_imagine_filter', [
            'filter' => $filter,
            'path' => $path,
            'hash' => $hash,
            'filters' => $runtimeConfig,
        ], UrlGeneratorInterface::ABSOLUTE_PATH);
    }

    private function buildRuntimeConfig(array $modifiers): array
    {
        $runtimeConfig = [];
        $modifiers = array_merge($this->defaults, $modifiers);

        foreach ($modifiers as $key => $value) {
            if (!isset(self::KEY_MAP[$key])) {
                continue;
            }

            $liipKey = self::KEY_MAP[$key];

            if ('width' === $key || 'height' === $key) {
                $runtimeConfig['size'] = $runtimeConfig['size'] ?? [
                    'width' => null,
                    'height' => null,
                ];
                $runtimeConfig['size'][$key] = $value;
                continue;
            }

            // Handle value mappings
            if (isset(self::VALUE_MAP[$key][$value])) {
                $value = self::VALUE_MAP[$key][$value];
            }

            // Handle ratio parsing
            if ('ratio' === $key && preg_match('/^(\d+):(\d+)$/', $value, $matches)) {
                $value = [
                    'width' => (int) $matches[1],
                    'height' => (int) $matches[2],
                ];
            }

            $runtimeConfig[$liipKey] = $value;
        }

        return $runtimeConfig;
    }
}
