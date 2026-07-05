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

use Symfony\UX\TwigComponent\Twig\PropsNode;
use Symfony\UX\TwigComponent\Twig\PropsTokenParser;
use Twig\Environment;
use Twig\Error\Error as TwigError;
use Twig\Loader\ArrayLoader;
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Node;
use Twig\Source;

/**
 * Extracts the {@see PropsDeclaration} from a Twig component's `{%- props ... -%}` tag.
 *
 * The tag is parsed with Twig itself (through {@see PropsTokenParser}/{@see PropsNode}), so
 * prop names and their default expressions come straight from the real Twig grammar rather
 * than a hand-rolled parser. Each default is rendered back to its canonical documentation
 * form so it can populate the API reference.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class PropsDeclarationParser
{
    private const RE_PROPS = '/\{%-?\s*props\b.*?-?%\}/s';

    private readonly Environment $twig;

    public function __construct(?Environment $twig = null)
    {
        $this->twig = $twig ?? self::createIsolatedEnvironment();
    }

    private static function createIsolatedEnvironment(): Environment
    {
        $twig = new Environment(new ArrayLoader());
        $twig->addTokenParser(new PropsTokenParser());

        return $twig;
    }

    public function parse(string $source): ?PropsDeclaration
    {
        if (!preg_match(self::RE_PROPS, $source, $matches)) {
            return null;
        }

        try {
            $module = $this->twig->parse($this->twig->tokenize(new Source($matches[0], 'props')));
        } catch (TwigError) {
            return null;
        }

        if (null === $propsNode = $this->findPropsNode($module)) {
            return null;
        }

        $props = [];
        foreach ($propsNode->getAttribute('names') as $name) {
            $props[] = $propsNode->hasNode($name)
                ? new DeclaredProp($name, true, $this->renderDefault($propsNode->getNode($name)))
                : new DeclaredProp($name, false);
        }

        return new PropsDeclaration($props);
    }

    private function findPropsNode(Node $node): ?PropsNode
    {
        if ($node instanceof PropsNode) {
            return $node;
        }

        foreach ($node as $child) {
            if ($child instanceof Node && null !== $found = $this->findPropsNode($child)) {
                return $found;
            }
        }

        return null;
    }

    /**
     * Exotic default expressions (function calls, concatenations, ...) cannot be rendered to a
     * canonical literal; they are documentation-only, so we degrade to null rather than fail.
     */
    private function renderDefault(Node $node): ?string
    {
        try {
            return $this->render($node);
        } catch (\RuntimeException) {
            return null;
        }
    }

    private function render(Node $node): string
    {
        if ($node instanceof ConstantExpression) {
            $value = $node->getAttribute('value');

            return match (true) {
                \is_string($value) => "'".str_replace("'", "\\'", $value)."'",
                \is_bool($value) => $value ? 'true' : 'false',
                null === $value => 'null',
                default => (string) $value,
            };
        }

        if ($node instanceof ArrayExpression) {
            $pairs = $node->getKeyValuePairs();
            if ([] === $pairs) {
                return '[]';
            }

            if ($node->isSequence()) {
                return '['.implode(', ', array_map(fn (array $pair) => $this->render($pair['value']), $pairs)).']';
            }

            $parts = [];
            foreach ($pairs as $pair) {
                $parts[] = $this->renderKey($pair['key']).': '.$this->render($pair['value']);
            }

            return '{'.implode(', ', $parts).'}';
        }

        throw new \RuntimeException(\sprintf('Unsupported default expression node "%s".', $node::class));
    }

    private function renderKey(Node $node): string
    {
        if ($node instanceof ConstantExpression) {
            $value = $node->getAttribute('value');
            if (\is_string($value) && preg_match('/^[a-zA-Z_]\w*$/', $value)) {
                return $value;
            }
        }

        return $this->render($node);
    }
}
