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
 * Port of `StyleDecoder::getAtomic()` (packages/core/src/style-decoder.ts).
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class StyleDecoder
{
    private const IMPORTANT = '/'.JsValue::WHITESPACE.'*!(important)?/iu';

    public function __construct(
        private readonly Utilities $utilities,
        private readonly Conditions $conditions,
        private readonly string $prefix = '',
    ) {
    }

    /**
     * @param list<StyleEntry> $entries
     *
     * @return list<AtomicStyle>
     */
    public function decode(array $entries): array
    {
        $styles = [];
        foreach ($entries as $entry) {
            $styles[] = $this->decodeEntry($entry);
        }

        return $styles;
    }

    private function decodeEntry(StyleEntry $entry): AtomicStyle
    {
        $value = JsValue::parse($entry->value);
        $important = \is_string($value) && str_contains($value, '!');
        if (\is_string($value)) {
            $value = JsValue::trim(preg_replace(self::IMPORTANT, '', $value, 1));
        }

        $transformed = $this->utilities->transform($entry->property, $value);
        $styles = $important ? self::markImportant($transformed['styles']) : $transformed['styles'];

        $conditionClasses = $this->conditions->finalize($entry->conditions);
        $htmlClass = implode(':', [...$conditionClasses, $this->formatClassName($transformed['className'])]);
        $className = CssEscaper::escape($htmlClass);
        $selector = '.'.$className.($important ? '\\!' : '');

        if ([] === $entry->conditions) {
            return new AtomicStyle(
                $entry,
                $className,
                $htmlClass.($important ? '!' : ''),
                null,
                [$selector => $styles],
            );
        }

        $conditions = $this->conditions->sortDetails($entry->conditions);
        $result = [];
        foreach ($this->expandMultiBlock($conditions) as $blockConditions) {
            $path = [$selector];
            foreach ($blockConditions as $condition) {
                array_push($path, ...$this->resolveCondition($condition));
            }
            self::deepSet($result, $path, $styles);
        }

        return new AtomicStyle($entry, $className, $htmlClass.($important ? '!' : ''), $conditions, $result);
    }

    private function formatClassName(string $className): string
    {
        return '' !== $this->prefix ? $this->prefix.'-'.$className : $className;
    }

    /**
     * @param list<Condition> $conditions
     *
     * @return list<list<Condition>>
     */
    private function expandMultiBlock(array $conditions): array
    {
        $hasMultiBlock = false;
        foreach ($conditions as $condition) {
            $hasMultiBlock = $hasMultiBlock || Condition::MULTI_BLOCK === $condition->type;
        }
        if (!$hasMultiBlock) {
            return [$conditions];
        }

        $combinations = [[]];
        foreach ($conditions as $condition) {
            if (Condition::MULTI_BLOCK === $condition->type) {
                $alternatives = array_map(
                    static fn (Condition $block): array => array_values(array_filter($block->value)),
                    $condition->value,
                );
            } else {
                $alternatives = [[$condition]];
            }
            $next = [];
            foreach ($combinations as $partial) {
                foreach ($alternatives as $choice) {
                    $next[] = [...$partial, ...$choice];
                }
            }
            $combinations = $next;
        }

        return array_map(static function (array $combination): array {
            $indexed = array_map(null, $combination, array_keys($combination));
            usort($indexed, static function (array $a, array $b): int {
                if ($a[0]->isAtRule() !== $b[0]->isAtRule()) {
                    return $a[0]->isAtRule() ? -1 : 1;
                }
                if ($a[0]->isPseudoElement() !== $b[0]->isPseudoElement()) {
                    return $a[0]->isPseudoElement() ? 1 : -1;
                }

                return $a[1] <=> $b[1];
            });

            return array_column($indexed, 0);
        }, $combinations);
    }

    /**
     * @return list<string>
     */
    private function resolveCondition(Condition $condition): array
    {
        if (Condition::MULTI_BLOCK === $condition->type) {
            return [];
        }

        $raws = \is_array($condition->raw) ? $condition->raw : [$condition->raw];

        return array_map(fn (mixed $raw): string => $this->utilities->tokens->resolveReferences((string) $raw), $raws);
    }

    /**
     * @param array<string, mixed> $target
     * @param list<string>         $path
     * @param array<string, mixed> $value
     */
    private static function deepSet(array &$target, array $path, array $value): void
    {
        $current = &$target;
        foreach ($path as $index => $key) {
            $current[$key] ??= [];
            if ($index === \count($path) - 1) {
                $current[$key] = \is_array($current[$key]) ? array_replace_recursive($current[$key], $value) : $value;
            } else {
                $current = &$current[$key];
            }
        }
    }

    /**
     * @param array<string, mixed> $styles
     *
     * @return array<string, mixed>
     */
    private static function markImportant(array $styles): array
    {
        foreach ($styles as $key => $value) {
            if (\is_string($value) || \is_int($value) || \is_float($value)) {
                $styles[$key] = JsValue::toString($value).' !important';
            } elseif (\is_array($value)) {
                $styles[$key] = self::markImportant($value);
            }
        }

        return $styles;
    }
}
