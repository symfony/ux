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

use Twig\Environment;
use Twig\Error\Error as TwigError;
use Twig\Loader\ArrayLoader;
use Twig\Source;
use Twig\Token;

/**
 * Reads a Twig component's block documentation from its `{## <description> #}` doc comments, and
 * splits the `## <type> <description>` prop comments that {@see PropsNode} captures.
 *
 * Since Twig 3.29, a documentation comment is attached to the token that follows it, so tokenizing
 * the template (no full compilation, hence no need for the kit's Twig extensions) surfaces each
 * block's documentation on its `{% block %}` / `{{ block(...) }}` opening token. The same pass also
 * lists every rendered block, documented or not.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class ComponentDocScanner
{
    private readonly Environment $twig;

    public function __construct(?Environment $twig = null)
    {
        $this->twig = $twig ?? new Environment(new ArrayLoader());
    }

    /**
     * Every block rendered in the template, and the subset documented with a `{## <description> #}`
     * doc comment, extracted from a single tokenization.
     *
     * @return array{docs: list<array{name: string, description: string}>, used: list<string>}
     */
    public function scanBlocks(string $source): array
    {
        try {
            $stream = $this->twig->tokenize(new Source($source, 'component'));
        } catch (TwigError) {
            return ['docs' => [], 'used' => []];
        }

        $tokens = [];
        while (!$stream->isEOF()) {
            $tokens[] = $stream->next();
        }

        $docs = [];
        $used = [];
        foreach ($tokens as $i => $token) {
            if (null === $name = self::blockNameAt($tokens, $i)) {
                continue;
            }
            $used[$name] = true;
            if (null !== $documentation = $token->getDocumentation()) {
                $docs[] = ['name' => $name, 'description' => self::normalizeWhitespace($documentation)];
            }
        }

        return ['docs' => $docs, 'used' => array_keys($used)];
    }

    /**
     * Splits a `## <type> <description>` doc into its type (the first token) and description.
     *
     * @return array{0: string, 1: string}
     */
    public static function splitTypeAndDescription(string $doc): array
    {
        $parts = preg_split('/\s+/', trim($doc), 2);

        return [$parts[0] ?? '', self::normalizeWhitespace($parts[1] ?? '')];
    }

    private static function normalizeWhitespace(string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', $value));
    }

    /**
     * The block rendered in the statement opened by the documented token, or null when it renders
     * none. Covers the `{% block x %}` tag and any `block('x')` / `block(outerBlocks.x)` call in a
     * `{{ ... }}` print or a `{% ... %}` tag (e.g. `{% set y = block('x') %}`).
     *
     * @param list<Token> $tokens
     */
    private static function blockNameAt(array $tokens, int $i): ?string
    {
        $value = static fn (int $k): string => isset($tokens[$k]) ? (string) $tokens[$k]->getValue() : '';
        $isName = static fn (int $k): bool => isset($tokens[$k]) && Token::NAME_TYPE === $tokens[$k]->getType();

        $type = $tokens[$i]->getType();
        if (Token::BLOCK_START_TYPE !== $type && Token::VAR_START_TYPE !== $type) {
            return null;
        }

        // {% block x %}
        if (Token::BLOCK_START_TYPE === $type && 'block' === $value($i + 1) && $isName($i + 2)) {
            return $value($i + 2);
        }

        // The first `block('x')` / `block(outerBlocks.x)` call within this statement.
        $endType = Token::BLOCK_START_TYPE === $type ? Token::BLOCK_END_TYPE : Token::VAR_END_TYPE;
        for ($k = $i + 1, $n = \count($tokens); $k < $n && $endType !== $tokens[$k]->getType(); ++$k) {
            if ('block' === $value($k) && '(' === $value($k + 1)) {
                if ('outerBlocks' === $value($k + 2) && '.' === $value($k + 3) && $isName($k + 4)) {
                    return $value($k + 4);
                }
                if (isset($tokens[$k + 2]) && Token::STRING_TYPE === $tokens[$k + 2]->getType()) {
                    return $value($k + 2);
                }
            }
        }

        return null;
    }
}
