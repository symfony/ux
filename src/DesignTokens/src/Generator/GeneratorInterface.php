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

/**
 * A format of ux:design-tokens:export, registered through autoconfiguration.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
interface GeneratorInterface
{
    /** Context key: the title of the page a format produces, as a string. */
    public const TITLE = 'title';

    /** Context key: the prefix of generated CSS custom properties, as a string. */
    public const CSS_PREFIX = 'css_prefix';

    /** Context key: the tree resolved for the dark scheme, or null. */
    public const DARK_TOKENS = 'dark_tokens';

    /**
     * @param array<array-key, mixed> $resolvedTokens nested token tree from `TokenTreeBuilder`
     * @param array<string, mixed>    $context
     *
     * @return string the complete file contents, including any trailing newline
     */
    public function generate(array $resolvedTokens, array $context = []): string;
}
