<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image;

use Symfony\UX\Image\Exception\InvalidArgumentException;
use Symfony\UX\Image\Twig\RenderOptionsFactory;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class ImagePresets
{
    private const SCALAR_OPTIONS = ['width', 'height', 'fit', 'format', 'quality'];

    /**
     * @var array<string, array<string, mixed>>
     */
    private readonly array $presets;

    /**
     * @param array<string, array{width?: int, height?: int, fit?: Fit|string, format?: string, quality?: int, operations?: array<string, array<string, scalar>>}> $presets
     */
    public function __construct(array $presets)
    {
        foreach ($presets as $name => $preset) {
            self::assertValid($name, $preset);

            if (isset($preset['fit']) && !$preset['fit'] instanceof Fit) {
                $presets[$name]['fit'] = RenderOptionsFactory::fit($preset['fit']);
            }
        }

        $this->presets = $presets;
    }

    /**
     * @param array{width: ?int, height: ?int, fit: ?Fit, format: ?string, quality: ?int, operations: array<string, array<string, scalar>>} $options
     *
     * @return array{width: ?int, height: ?int, fit: ?Fit, format: ?string, quality: ?int, operations: array<string, array<string, scalar>>}
     */
    public function merge(string $name, array $options): array
    {
        $preset = $this->presets[$name] ?? throw $this->unknownPreset($name);

        foreach (self::SCALAR_OPTIONS as $option) {
            $options[$option] ??= $preset[$option] ?? null;
        }
        $given = self::withoutNulls($options['operations']);
        $options['operations'] = [] === $given ? $preset['operations'] ?? [] : array_replace_recursive($preset['operations'] ?? [], $given);

        return $options;
    }

    private static function assertValid(string $name, mixed $preset): void
    {
        if (!\is_array($preset)) {
            throw new InvalidArgumentException(\sprintf('The "%s" image preset must be an array, "%s" given.', $name, get_debug_type($preset)));
        }

        $options = [...self::SCALAR_OPTIONS, 'operations'];
        if ([] !== $unknown = array_diff(array_keys($preset), $options)) {
            throw new InvalidArgumentException(\sprintf('Unknown option "%s" in the "%s" image preset: expected one of "%s".', implode('", "', $unknown), $name, implode('", "', $options)));
        }

        foreach (['width' => 'int', 'height' => 'int', 'format' => 'string', 'quality' => 'int'] as $option => $type) {
            if (isset($preset[$option]) && $type !== get_debug_type($preset[$option])) {
                throw new InvalidArgumentException(\sprintf('The "%s" option of the "%s" image preset must be of type "%s", "%s" given.', $option, $name, $type, get_debug_type($preset[$option])));
            }
        }

        $operations = $preset['operations'] ?? [];
        if (!\is_array($operations) || [] !== array_filter($operations, static fn (mixed $map): bool => !\is_array($map))) {
            throw new InvalidArgumentException(\sprintf('The "operations" option of the "%s" image preset must map provider names to operation maps.', $name));
        }
    }

    private function unknownPreset(string $name): InvalidArgumentException
    {
        if ([] === $this->presets) {
            return new InvalidArgumentException(\sprintf('The image preset "%s" does not exist, no preset is defined under "ux_image.presets".', $name));
        }

        $defined = implode('", "', array_keys($this->presets));

        return new InvalidArgumentException(\sprintf('The image preset "%s" does not exist (defined: "%s").', $name, $defined));
    }

    /**
     * @param array<string, array<string, scalar|null>|null> $operations
     *
     * @return array<string, array<string, scalar>>
     */
    private static function withoutNulls(array $operations): array
    {
        foreach ($operations as $provider => $values) {
            if (\is_array($values)) {
                $operations[$provider] = array_filter($values, static fn ($value): bool => null !== $value);
            }
        }

        return array_filter($operations, static fn ($values): bool => null !== $values);
    }
}
