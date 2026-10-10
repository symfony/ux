<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DesignTokens\Generator;

use Symfony\UX\DesignTokens\TokenTree;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class JavaScriptGenerator implements GeneratorInterface
{
    /** @param array<array-key, mixed> $resolvedTokens */
    public function generate(array $resolvedTokens, array $context = []): string
    {
        $values = [];
        foreach (TokenTree::flatten($resolvedTokens) as $path => $token) {
            $values[$path] = (string) $token;
        }

        // JSON.parse keeps a "__proto__" key as a token, not as a prototype.
        $json = json_encode((object) $values, \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_THROW_ON_ERROR);
        $literal = json_encode($json, \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_THROW_ON_ERROR);

        $module = <<<JAVASCRIPT
            export const tokens = Object.freeze(JSON.parse({$literal}));

            export function token(path) {
                if (!Object.hasOwn(tokens, path)) {
                    throw new Error(`Unknown design token: "\${path}".`);
                }

                return tokens[path];
            }
            JAVASCRIPT;

        return $module."\n";
    }
}
