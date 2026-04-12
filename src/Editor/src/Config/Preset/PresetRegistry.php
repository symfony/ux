<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Config\Preset;

use Symfony\UX\Editor\Exception\UnknownBridgeException;

final class PresetRegistry
{
    /**
     * @var array<string, EditorPresetInterface>
     */
    private array $presets = [];

    /**
     * @param iterable<string, EditorPresetInterface> $presets
     */
    public function __construct(iterable $presets = [])
    {
        foreach ($presets as $name => $preset) {
            $this->presets[$name] = $preset;
        }
    }

    public function get(string $name): EditorPresetInterface
    {
        return $this->presets[$name] ?? throw new UnknownBridgeException(sprintf('Unknown preset "%s". Registered: %s', $name, '' !== ($list = implode(', ', array_keys($this->presets))) ? $list : '(none)'));
    }

    /**
     * @return array<string, EditorPresetInterface>
     */
    public function all(): array
    {
        return $this->presets;
    }
}
