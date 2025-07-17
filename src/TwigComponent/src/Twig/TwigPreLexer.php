<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\Twig;

use Twig\Error\SyntaxError;
use Twig\Lexer;

/**
 * Rewrites <twig:component> syntaxes to {% component %} syntaxes.
 */
class TwigPreLexer
{
    private string $input;
    private int $length;
    private int $position = 0;
    private int $line;
    /**
     * @var array<array{name: string, hasDefaultBlock: bool}>
     */
    private array $currentComponents = [];

    public function __construct(int $startingLine = 1)
    {
        $this->line = $startingLine;
    }

    public function preLexComponents(string $input): string
    {
        if (!str_contains($input, '<twig:')) {
            return $input;
        }

        $this->input = $input = str_replace(["\r\n", "\r"], "\n", $input);
        $this->length = \strlen($input);
        $output = '';

        $inTwigEmbed = false;

        while ($this->position < $this->length) {
            // ignore content inside verbatim block #947
            if ($this->consume('{% verbatim %}')) {
                $output .= '{% verbatim %}';
                $output .= $this->consumeUntil('{% endverbatim %}');
                $this->consume('{% endverbatim %}');
                $output .= '{% endverbatim %}';

                if ($this->position === $this->length) {
                    break;
                }
            }

            // ignore content inside twig comments, see #838
            if ($this->consume('{#')) {
                $output .= '{#';
                $output .= $this->consumeUntil('#}');
                $this->consume('#}');
                $output .= '#}';

                if ($this->position === $this->length) {
                    break;
                }
            }

            if ($this->consume('{% embed')) {
                $inTwigEmbed = true;
                $output .= '{% embed';
                $output .= $this->consumeUntil('%}');

                continue;
            }

            if ($this->consume('{% endembed %}')) {
                $inTwigEmbed = false;
                $output .= '{% endembed %}';

                continue;
            }

            $isTwigHtmlOpening = $this->consume('<twig:');
            $isTraditionalBlockOpening = false;

            if ($isTwigHtmlOpening || (0 !== \count($this->currentComponents) && $isTraditionalBlockOpening = $this->consume('{% block'))) {
                $componentName = $isTraditionalBlockOpening ? 'block' : $this->consumeComponentName();

                if ('block' === $componentName) {
                    // if we're already inside the "default" block, let's close it
                    if (!empty($this->currentComponents) && $this->currentComponents[\count($this->currentComponents) - 1]['hasDefaultBlock'] && !$inTwigEmbed) {
                        $output .= '{% endblock %}';

                        $this->currentComponents[\count($this->currentComponents) - 1]['hasDefaultBlock'] = false;
                    }

                    if ($isTraditionalBlockOpening) {
                        // add what we've consumed so far
                        $output .= '{% block';
                        $output .= $stringUntilClosingTag = $this->consumeUntil('%}');

                        // If the last-consumed string does not match the Twig's block name regex, we assume the block is self-closing
                        $isBlockSelfClosing = '' !== preg_replace(Lexer::REGEX_NAME, '', trim($stringUntilClosingTag));

                        if ($isBlockSelfClosing && $this->consume('%}')) {
                            $output .= '%}';
                        } else {
                            $output .= $this->consumeUntilEndBlock();
                        }

                        continue;
                    }

                    $output .= $this->consumeBlock($componentName);

                    continue;
                }

                // if we're already inside a component,
                // *and* we've just found a new component, then we should try to
                // open the default block
                if (!empty($this->currentComponents)
                    && !$this->currentComponents[\count($this->currentComponents) - 1]['hasDefaultBlock']) {
                    $output .= '{% block content %}';
                    $this->currentComponents[\count($this->currentComponents) - 1]['hasDefaultBlock'] = true;
                }

                $attributes = $this->consumeAttributes($componentName);
                $isSelfClosing = $this->consume('/>');
                if (!$isSelfClosing) {
                    $this->consume('>');
                    $this->currentComponents[] = ['name' => $componentName, 'hasDefaultBlock' => false];
                }

                if ($isSelfClosing) {
                    // use the simpler component() format, so that the system doesn't think
                    // this is an "embedded" component with blocks
                    // see https://github.com/symfony/ux/issues/810
                    $output .= "{{ component('{$componentName}'".($attributes ? ", { {$attributes} }" : '').') }}';
                } else {
                    $output .= "{% component '{$componentName}'".($attributes ? " with { {$attributes} }" : '').' %}';
                }

                continue;
            }

            if (!empty($this->currentComponents) && $this->check('</twig:')) {
                $this->consume('</twig:');
                $closingComponentName = $this->consumeComponentName();
                $this->consume('>');

                $lastComponent = array_pop($this->currentComponents);
                $lastComponentName = $lastComponent['name'];

                if ($closingComponentName !== $lastComponentName) {
                    throw new SyntaxError("Expected closing tag '</twig:{$lastComponentName}>' but found '</twig:{$closingComponentName}>'.", $this->line);
                }

                // we've reached the end of this component. If we're inside the
                // default block, let's close it
                if ($lastComponent['hasDefaultBlock']) {
                    $output .= '{% endblock %}';
                }

                $output .= '{% endcomponent %}';

                continue;
            }

            $char = $this->input[$this->position];
            if ("\n" === $char) {
                ++$this->line;
            }

            // handle adding a default block if we find non-whitespace outside of a block
            if (!empty($this->currentComponents)
                && !$this->currentComponents[\count($this->currentComponents) - 1]['hasDefaultBlock']
                && preg_match('/\S/', $char)
                && !$this->check('{% block')
            ) {
                $this->currentComponents[\count($this->currentComponents) - 1]['hasDefaultBlock'] = true;
                $output .= '{% block content %}';
            }

            $output .= $char;
            $this->consumeChar();
        }

        if (!empty($this->currentComponents)) {
            $lastComponent = array_pop($this->currentComponents)['name'];
            throw new SyntaxError(\sprintf('Expected closing tag "</twig:%s>" not found.', $lastComponent), $this->line);
        }

        return $output;
    }

    private function consumeComponentName(?string $customExceptionMessage = null): string
    {
        if (preg_match('/\G[A-Za-z0-9_:@\-.]+/', $this->input, $matches, 0, $this->position)) {
            $componentName = $matches[0];
            $this->position += \strlen($componentName);

            return $componentName;
        }

        throw new SyntaxError($customExceptionMessage ?? 'Expected component name when resolving the "<twig:" syntax.', $this->line);
    }

    private function consumeAttributes(string $componentName): string
    {
        $attributes = [];

        while ($this->position < $this->length && !$this->check('>') && !$this->check('/>')) {
            $this->consumeWhitespace();
            if ($this->check('>') || $this->check('/>')) {
                break;
            }

            if ($this->check('{{...') || $this->check('{{ ...')) {
                $this->consume('{{...');
                $this->consume('{{ ...');
                $attributes[] = '...'.trim($this->consumeUntil('}}'));
                $this->consume('}}');

                continue;
            }

            $isAttributeDynamic = false;

            // :someProp="dynamicVar"
            $this->consumeWhitespace();
            if ($this->check(':')) {
                $this->consume(':');
                $isAttributeDynamic = true;
            }

            $message = \sprintf('Expected attribute name when parsing the "<twig:%s" syntax.', $componentName);
            // was called 'consumeAttributeName'
            $key = $this->consumeComponentName($message);

            // <twig:component someProp> -> someProp: true
            if (!$this->check('=')) {
                $this->consumeWhitespace();
                // don't allow "<twig:component :someProp>"
                if ($isAttributeDynamic) {
                    throw new SyntaxError(\sprintf('Expected "=" after ":%s" when parsing the "<twig:%s" syntax.', $key, $componentName), $this->line);
                }

                $attributes[] = \sprintf('%s: true', preg_match('/[-:@]/', $key) ? "'$key'" : $key);
                $this->consumeWhitespace();
                continue;
            }

            $this->expectAndConsumeChar('=');
            $quote = $this->consumeChar(["'", '"']);

            if ($isAttributeDynamic) {
                // :someProp="dynamicVar"
                $attributeValue = $this->consumeUntil($quote);
            } else {
                $attributeValue = $this->consumeAttributeValue($quote);
            }

            $attributes[] = \sprintf('%s: %s', preg_match('/[-:@]/', $key) ? "'$key'" : $key, '' === $attributeValue ? "''" : $attributeValue);

            $this->expectAndConsumeChar($quote);
            $this->consumeWhitespace();
        }

        return implode(', ', $attributes);
    }

    /**
     * If the next character(s) exactly matches the given string, then
     * consume it (move forward) and return true.
     */
    private function consume(string $string): bool
    {
        if (str_starts_with(substr($this->input, $this->position), $string)) {
            $this->position += \strlen($string);

            return true;
        }

        return false;
    }

    private function consumeChar($validChars = null): string
    {
        if ($this->position >= $this->length) {
            throw new SyntaxError('Unexpected end of input.', $this->line);
        }

        $char = $this->input[$this->position];

        if (null !== $validChars && !\in_array($char, (array) $validChars, true)) {
            throw new SyntaxError('Expected one of [.'.implode('', (array) $validChars)."] but found '{$char}'.", $this->line);
        }

        ++$this->position;

        return $char;
    }

    /**
     * Moves the position forward until it finds $endString.
     *
     * Any string consumed *before* finding that string is returned.
     * The position is moved forward to just *before* $endString.
     */
    private function consumeUntil(string $endString): string
    {
        if (false === $endPosition = strpos($this->input, $endString, $this->position)) {
            $start = $this->position;
            $this->position = $this->length;

            return substr($this->input, $start);
        }

        $content = substr($this->input, $this->position, $endPosition - $this->position);
        $this->line += substr_count($content, "\n");
        $this->position = $endPosition;

        return $content;
    }

    private function consumeWhitespace(): void
    {
        $whitespace = substr($this->input, $this->position, strspn($this->input, " \t\n\r\0\x0B", $this->position));
        $this->line += substr_count($whitespace, "\n");
        $this->position += \strlen($whitespace);

        if ($this->check('#')) {
            $this->consume('#');
            $this->consumeUntil("\n");
            $this->consumeWhitespace();
        }
    }

    /**
     * Checks that the next character is the one given and consumes it.
     */
    private function expectAndConsumeChar(string $char): void
    {
        if (1 !== \strlen($char)) {
            throw new \InvalidArgumentException('Expected a single character.');
        }

        if ($this->position >= $this->length) {
            throw new SyntaxError("Expected '{$char}' but reached the end of the file.", $this->line);
        }

        if ($this->input[$this->position] !== $char) {
            throw new SyntaxError("Expected '{$char}' but found '{$this->input[$this->position]}'.", $this->line);
        }

        ++$this->position;
    }

    private function check(string $chars): bool
    {
        return $this->position + \strlen($chars) <= $this->length
            && 0 === substr_compare($this->input, $chars, $this->position, \strlen($chars));
    }

    private function consumeBlock(string $componentName): string
    {
        $attributes = $this->consumeAttributes($componentName);
        $this->consume('>');

        $blockName = '';
        foreach (explode(', ', $attributes) as $attr) {
            [$key, $value] = explode(': ', $attr);
            if ('name' === $key) {
                $blockName = trim($value, "'");
                break;
            }
        }

        if (empty($blockName)) {
            throw new SyntaxError('Expected block name.', $this->line);
        }

        $output = "{% block {$blockName} %}";

        $closingTag = '</twig:block>';
        if (false === strpos($this->input, $closingTag, $this->position)) {
            throw new SyntaxError("Expected closing tag '{$closingTag}' for block '{$blockName}'.", $this->line);
        }
        $blockContents = $this->consumeUntilEndBlock();

        $subLexer = new self($this->line);
        $output .= $subLexer->preLexComponents($blockContents);

        $this->consume($closingTag);
        $output .= '{% endblock %}';

        return $output;
    }

    private function consumeUntilEndBlock(): string
    {
        $start = $this->position;

        $depth = 1;
        $inComment = false;
        while ($this->position < $this->length) {
            if ($inComment && '#}' === substr($this->input, $this->position, 2)) {
                $inComment = false;
            }
            if (!$inComment && '{#' === substr($this->input, $this->position, 2)) {
                $inComment = true;
            }

            if (!$inComment && '</twig:block>' === substr($this->input, $this->position, 13)) {
                if (1 === $depth) {
                    break;
                } else {
                    --$depth;
                }
            }

            if (!$inComment && '{% endblock %}' === substr($this->input, $this->position, 14)) {
                if (1 === $depth) {
                    // in this case, we want to advance ALL the way beyond the endblock
                    // strlen('{% endblock %}') = 14
                    $this->position += 14;
                    break;
                } else {
                    --$depth;
                }
            }

            if (!$inComment && '<twig:block' === substr($this->input, $this->position, 11)) {
                ++$depth;
            }

            if (!$inComment && '{% block' === substr($this->input, $this->position, 8)) {
                ++$depth;
            }

            if ("\n" === $this->input[$this->position]) {
                ++$this->line;
            }
            ++$this->position;
        }

        return substr($this->input, $start, $this->position - $start);
    }

    private function consumeAttributeValue(string $quote): string
    {
        $parts = [];
        $currentPart = '';
        while ($this->position < $this->length) {
            if ($this->check($quote)) {
                break;
            }

            if ("\n" === $this->input[$this->position]) {
                ++$this->line;
            }

            if ($this->check('{{')) {
                // mark any previous static text as complete: push into parts
                if ('' !== $currentPart) {
                    $parts[] = \sprintf("'%s'", str_replace("'", "\'", $currentPart));
                    $currentPart = '';
                }

                // consume the entire {{ }} block
                $this->consume('{{');
                $this->consumeWhitespace();
                $parts[] = \sprintf('(%s)', rtrim($this->consumeUntil('}}')));
                $this->expectAndConsumeChar('}');
                $this->expectAndConsumeChar('}');

                continue;
            }

            $currentPart .= $this->input[$this->position];
            ++$this->position;
        }

        if ('' !== $currentPart) {
            $parts[] = \sprintf("'%s'", str_replace("'", "\'", $currentPart));
        }

        return implode('~', $parts);
    }
}
