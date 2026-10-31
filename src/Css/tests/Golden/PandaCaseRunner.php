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

use Symfony\UX\Css\Engine\ClassNameGenerator;
use Symfony\UX\Css\Engine\Engine;
use Symfony\UX\Css\Engine\Preset\BaseTransforms;
use Symfony\UX\Css\Engine\StaticCss;
use Symfony\UX\Css\Exception\UnsupportedStyleException;

final class PandaCaseRunner
{
    /**
     * @var array<string, ClassNameGenerator>
     */
    private array $classNameGenerators = [];

    /**
     * @var array<string, Engine>
     */
    private array $engines = [];

    public function __construct(
        private readonly PandaCaseLoader $loader,
    ) {
    }

    /**
     * @throws UnsupportedStyleException when the case relies on a feature the engine does not support yet
     * @throws OutOfScopeException       when the case relies on a feature the spec keeps out of v1
     */
    public function run(PandaCase $case): string
    {
        if ('codegen-css' === $case->kind) {
            return $this->generateClassNames($case);
        }

        $config = $this->loader->configFor($case);
        if ('template-literal' === ($config['syntax'] ?? null)) {
            throw new OutOfScopeException('The template literal syntax has no Twig equivalent: css() takes a hash.');
        }
        $hash = $config['hash'] ?? false;
        if (true === $hash || (\is_array($hash) && ($hash['className'] ?? false))) {
            throw new OutOfScopeException('Hashed class names are out of v1.');
        }

        $engine = $this->engines[$case->config.':'.md5(serialize($case->configPatch))] ??= $this->createEngine($config);
        if ('token-css' === $case->kind) {
            if ([] !== ($config['themes'] ?? [])) {
                throw new OutOfScopeException('Panda themes are out of v1.');
            }

            return $engine->stylesheet()->render([], $engine->tokenCss()->nodes());
        }

        $inputs = $case->inputs;
        if ('static-css' === $case->kind) {
            $inputs = new StaticCss($engine)->styles($case->inputs[0]['css'] ?? []);
        }

        $entries = [];
        foreach ($inputs as $input) {
            if (!\is_array($input)) {
                throw new UnsupportedStyleException('Only style objects are supported.');
            }
            foreach ($engine->encoder()->encode($input) as $entry) {
                if (\in_array($entry->property, ['textStyle', 'layerStyle', 'animationStyle'], true)) {
                    throw new OutOfScopeException('Text styles and layer styles are out of v1.');
                }
                $entries[$entry->hash()] ??= $entry;
            }
        }

        return $engine->stylesheet()->render($engine->decoder()->decode(array_values($entries)));
    }

    private function generateClassNames(PandaCase $case): string
    {
        $styles = [];
        foreach ($case->inputs as $input) {
            foreach (\is_array($input) && array_is_list($input) ? $input : [$input] as $style) {
                if (\is_array($style) && [] !== $style && !array_is_list($style)) {
                    $styles[] = $style;
                }
            }
        }
        if (\count($styles) > 1) {
            throw new OutOfScopeException('Merging several style objects in one css() call is out of v1.');
        }

        $generator = $this->classNameGenerators[$case->config] ??= ClassNameGenerator::fromPandaConfig(
            $this->loader->config($case->config),
        );

        return $generator->generate($styles[0] ?? []);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function createEngine(array $config): Engine
    {
        $transforms = BaseTransforms::all();
        foreach ($config['utilities'] ?? [] as $property => $utility) {
            if ($utility['transform']['custom'] ?? false) {
                $transforms[$property] = static fn () => throw new OutOfScopeException('Utilities with custom transforms are out of v1.');
            }
        }

        return new Engine($config, $transforms);
    }
}
