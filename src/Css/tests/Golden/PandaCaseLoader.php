<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Css\Tests\Golden;

final class PandaCaseLoader
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private array $configs = [];

    public function __construct(
        private readonly string $directory,
    ) {
    }

    /**
     * @return iterable<string, PandaCase>
     */
    public function cases(): iterable
    {
        $files = glob($this->directory.'/cases/*.json') ?: [];
        sort($files);

        foreach ($files as $file) {
            foreach (json_decode(file_get_contents($file), true, flags: \JSON_THROW_ON_ERROR)['cases'] as $data) {
                $case = PandaCase::fromArray($data);

                yield $case->id => $case;
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function config(string $name): array
    {
        return $this->configs[$name] ??= json_decode(
            file_get_contents($this->directory.'/configs/'.$name.'.json'),
            true,
            flags: \JSON_THROW_ON_ERROR,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function configFor(PandaCase $case): array
    {
        $config = $this->config($case->config);

        return null === $case->configPatch ? $config : self::applyConfigPatch($config, $case->configPatch);
    }

    /**
     * @param array<array-key, mixed> $config
     * @param array<array-key, mixed> $patch  null removes a key, ['$value' => ...] sets it, any other array patches it, ['$order' => [...]] lists the keys in their order
     *
     * @return array<array-key, mixed>
     */
    public static function applyConfigPatch(array $config, array $patch): array
    {
        foreach ($patch as $key => $value) {
            if ('$order' === $key) {
                continue;
            }
            if (null === $value) {
                unset($config[$key]);
            } elseif (\array_key_exists('$value', $value)) {
                $config[$key] = $value['$value'];
            } else {
                $config[$key] = self::applyConfigPatch(\is_array($config[$key] ?? null) ? $config[$key] : [], $value);
            }
        }

        if (isset($patch['$order'])) {
            $config = array_replace(array_fill_keys($patch['$order'], null), $config);
        }

        return $config;
    }
}
