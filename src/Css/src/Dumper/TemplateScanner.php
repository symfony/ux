<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Css\Dumper;

use Symfony\UX\Css\Twig\CssNodeVisitor;
use Twig\Environment;
use Twig\Error\Error;

/**
 * Parses a template, without compiling it to PHP, to collect the style hashes of its css() calls.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class TemplateScanner
{
    public function __construct(
        private readonly Environment $twig,
        private readonly CssNodeVisitor $nodeVisitor,
    ) {
    }

    /**
     * @return array{path: string, styles: list<array<array-key, mixed>>} the file of the template (empty when it has none) and the style hashes of its css() calls
     *
     * @throws Error when the template cannot be parsed or a css() call is invalid
     */
    public function scan(string $name): array
    {
        $source = $this->twig->getLoader()->getSourceContext($name);
        if (!str_contains($source->getCode(), 'css(')) {
            return ['path' => $source->getPath(), 'styles' => []];
        }

        $styles = [];
        $this->nodeVisitor->collectInto(static function (array $hash) use (&$styles): void {
            $styles[] = $hash;
        });

        try {
            $this->twig->parse($this->twig->tokenize($source));
        } finally {
            $this->nodeVisitor->collectInto(null);
        }

        return ['path' => $source->getPath(), 'styles' => $styles];
    }
}
