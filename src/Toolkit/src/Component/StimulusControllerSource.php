<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Component;

/**
 * Reads a Stimulus controller's documented + declared surface from its source: the
 * `@value`/`@target`/`@action` docblock tags and the `static values`/`static targets`
 * declarations. Shared by {@see StimulusControllerDocParser} (rendering) and
 * {@see \Symfony\UX\Toolkit\Kit\Lint\Checker\StimulusControllerDocChecker} (linting) so both
 * scan a controller identically.
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class StimulusControllerSource
{
    private const RE_DOCBLOCK = '#/\*\*(?P<body>.*?)\*/#s';
    // The description ends only at the next annotation *line* (\R\h*@), so an `@` mid-sentence isn't a boundary.
    private const RE_TAG = '/@(?P<tag>value|target|action)\h+(?P<name>[A-Za-z_$][\w$]*)(?P<description>(?:(?!\R\h*@[a-zA-Z]).)*)/s';
    private const RE_VALUES = '/static\s+values\s*=\s*\{(?P<body>(?:[^{}]|\{[^{}]*\})*)\}/s';
    private const RE_VALUE_ENTRY = '/(?P<name>[A-Za-z_$][\w$]*)\s*:\s*(?P<type>\{[^{}]*\}|[A-Za-z_$][\w$]*)/';
    private const RE_TARGETS = '/static\s+targets\s*=\s*\[(?P<body>[^\]]*)\]/s';
    private const RE_STRING = '/[\'"](?P<value>[^\'"]+)[\'"]/';

    public function __construct(private readonly string $source)
    {
    }

    /**
     * Docblock tag descriptions, keyed by name within each tag type.
     *
     * @return array{value: array<string, string>, target: array<string, string>, action: array<string, string>}
     */
    public function tags(): array
    {
        $tags = ['value' => [], 'target' => [], 'action' => []];

        if (!preg_match_all(self::RE_DOCBLOCK, $this->source, $blocks)) {
            return $tags;
        }

        // Parse each docblock independently so a tag's description is bounded by its own block and
        // cannot swallow trailing prose from an unrelated method docblock elsewhere in the file.
        foreach ($blocks['body'] as $rawBody) {
            $body = preg_replace('/^\h*\*\h?/m', '', $rawBody);
            if (!preg_match_all(self::RE_TAG, $body, $matches, \PREG_SET_ORDER)) {
                continue;
            }

            foreach ($matches as $match) {
                $tags[$match['tag']][$match['name']] = trim(preg_replace('/\s+/', ' ', $match['description']));
            }
        }

        return $tags;
    }

    /**
     * Values declared in `static values`, with type/default read from the code.
     *
     * @return list<array{name: string, type: string, default: ?string}>
     */
    public function values(): array
    {
        if (!preg_match(self::RE_VALUES, $this->source, $matches)) {
            return [];
        }

        if (!preg_match_all(self::RE_VALUE_ENTRY, $matches['body'], $entries, \PREG_SET_ORDER)) {
            return [];
        }

        $values = [];
        foreach ($entries as $entry) {
            [$type, $default] = $this->normalizeValueType($entry['type']);
            $values[] = ['name' => $entry['name'], 'type' => $type, 'default' => $default];
        }

        return $values;
    }

    /**
     * Target names declared in `static targets`.
     *
     * @return list<string>
     */
    public function targets(): array
    {
        if (!preg_match(self::RE_TARGETS, $this->source, $matches)) {
            return [];
        }

        if (!preg_match_all(self::RE_STRING, $matches['body'], $strings)) {
            return [];
        }

        return $strings['value'];
    }

    /**
     * A value type is either shorthand (`autoClose: Number`) or an object
     * (`autoClose: { type: Number, default: 0 }`); both yield a type and an optional default.
     *
     * @return array{0: string, 1: ?string}
     */
    private function normalizeValueType(string $type): array
    {
        if (!str_starts_with($type, '{')) {
            return [$type, null];
        }

        $resolvedType = preg_match('/type\s*:\s*(?P<type>[A-Za-z_$][\w$]*)/', $type, $m) ? $m['type'] : 'mixed';
        $default = preg_match('/default\s*:\s*(?P<default>[^,}]+)/', $type, $m) ? trim($m['default']) : null;

        return [$resolvedType, $default];
    }
}
