<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Css\Engine;

/**
 * Panda's default presets, `preset-base` and `preset-panda` without their tokens, merged with a project config.
 *
 * The merge follows `mergeConfigs()` (packages/config/src/merge-config.ts) on the keys a project can set:
 * a project condition is added or replaces the default one, a project theme key replaces the default one.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class PandaConfig
{
    private const PRESET = __DIR__.'/../../resources/panda-preset.json';

    /**
     * @param array{conditions?: array<string, string|list<string>>, theme?: array<string, mixed>} $project
     *
     * @return array<string, mixed>
     */
    public static function create(array $project): array
    {
        $preset = json_decode(file_get_contents(self::PRESET), true, flags: \JSON_THROW_ON_ERROR);

        return [
            ...$preset,
            'conditions' => ($project['conditions'] ?? []) + $preset['conditions'],
            'theme' => ($project['theme'] ?? []) + $preset['theme'],
        ];
    }
}
