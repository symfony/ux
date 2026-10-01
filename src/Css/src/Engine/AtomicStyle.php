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
 * Port of Panda's `AtomicStyleResult`: one class and the style object that renders it.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class AtomicStyle
{
    /**
     * @param list<Condition>|null $conditions
     * @param string               $htmlClass  the class as written in the HTML `class` attribute: not escaped, with '!' when important
     * @param array<string, mixed> $result     nested style object, rooted at the class selector
     */
    public function __construct(
        public readonly StyleEntry $entry,
        public readonly string $className,
        public readonly string $htmlClass,
        public readonly ?array $conditions,
        public readonly array $result,
    ) {
    }
}
