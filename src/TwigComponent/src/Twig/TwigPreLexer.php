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

/**
 * Rewrites <twig:component> syntaxes to {% component %} syntaxes.
 */
class TwigPreLexer
{
    private string $input;
    private int $length;
    private int $position = 0;
    private int $line;
    /** @var array<string: name, bool: hasDefaultBlock> */
    private array $currentComponents = [];

    public function __construct(int $startingLine = 1)
    {
        $this->line = $startingLine;
    }

    public function preLexComponents(string $input): string
    {
        $this->input = $input;
        $this->length = \strlen($input);
        $output = '';

        while ($this->position < $this->length) {
            if ($this->consume('<twig:')) {
                $componentName = $this->consumeComponentName();

                if ('block' === $componentName) {
                    // if we're already inside the "default" block, let's close it
                    if (!empty($this->currentComponents) && $this->currentComponents[\count($this->currentComponents) - 1]['hasDefaultBlock']) {
                        $output .= '{% endblock %}';

                        $this->currentComponents[\count($this->currentComponents) - 1]['hasDefaultBlock'] = false;
                    }

                    $output .= $this->consumeBlock($componentName);

                    continue;
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
                    throw new SyntaxError("Expected closing tag '</twig:{$lastComponentName}>' but found '</twig:{$closingComponentName}>'", $this->line);
                }

                // we've reached the end of this component. If we're inside the
                // default block, let's close it
                if ($lastComponent['hasDefaultBlock']) {
                    $output .= '{% endblock %}';
                }

                $output .= '{% endcomponent %}';

                continue;
            }

            $char = $this->consumeChar();
            if ("\n" === $char) {
                ++$this->line;
            }

            // handle adding a default block if needed
            if (!empty($this->currentComponents)
                && !$this->currentComponents[\count($this->currentComponents) - 1]['hasDefaultBlock']
                && preg_match('/\S/', $char)
            ) {
                $this->currentComponents[\count($this->currentComponents) - 1]['hasDefaultBlock'] = true;
                $output .= '{% block content %}';
            }

            $output .= $char;
        }

        if (!empty($this->currentComponents)) {
            $lastComponent = array_pop($this->currentComponents)['name'];
            throw new SyntaxError(sprintf('Expected closing tag "</twig:%s>" not found.', $lastComponent), $this->line);
        }

        return $output;
    }

    private function consumeComponentName(string $customExceptionMessage = null): string
    {
        $start = $this->position;
        while ($this->position < $this->length && preg_match('/[A-Za-z0-9_:@\-.]/', $this->input[$this->position])) {
            ++$this->position;
        }

        $componentName = substr($this->input, $start, $this->position - $start);

        if (empty($componentName)) {
            $exceptionMessage = $customExceptionMessage;
            if (null == $exceptionMessage) {
                $exceptionMessage = 'Expected component name when resolving the "<twig:" syntax.';
            }
            throw new SyntaxError($exceptionMessage, $this->line);
        }

        return $componentName;
    }

    private function consumeAttributeName(string $componentName): string
    {
        $message = sprintf('Expected attribute name when parsing the "<twig:%s" syntax.', $componentName);

        return $this->consumeComponentName($message);
    }

    private function consumeAttributes(string $componentName): string
    {
        $attributes = [];

        while ($this->position < $this->length && !$this->check('>') && !$this->check('/>')) {
            $this->consumeWhitespace();
            if ($this->check('>') || $this->check('/>')) {
                break;
            }

            $isAttributeDynamic = false;

            // :someProp="dynamicVar"
            if ($this->check(':')) {
                $this->consume(':');
                $isAttributeDynamic = true;
            }

            $key = $this->consumeAttributeName($componentName);

            // <twig:component someProp> -> someProp: true
            if (!$this->check('=')) {
                $attributes[] = sprintf('%s: true', $key);
                $this->consumeWhitespace();
                continue;
            }

            $this->expectAndConsumeChar('=');
            $quote = $this->consumeChar(["'", '"']);

            // someProp="{{ dynamicVar }}"
            if ($this->consume('{{')) {
                $this->consumeWhitespace();
                $attributeValue = rtrim($this->consumeUntil('}'));
                $this->expectAndConsumeChar('}');
                $this->expectAndConsumeChar('}');
                $this->consumeUntil($quote);
                $isAttributeDynamic = true;
            } else {
                $attributeValue = $this->consumeUntil($quote);
            }
            $this->expectAndConsumeChar($quote);

            if ($isAttributeDynamic) {
                $attributes[] = sprintf('%s: %s', $key, $attributeValue);
            } else {
                $attributes[] = sprintf("%s: '%s'", $key, str_replace("'", "\'", $attributeValue));
            }

            $this->consumeWhitespace();
        }

        return implode(', ', $attributes);
    }

    private function consume(string $string): bool
    {
        if (substr($this->input, $this->position, \strlen($string)) === $string) {
            $this->position += \strlen($string);

            return true;
        }

        return false;
    }

    private function consumeChar($validChars = null): string
    {
        if ($this->position >= $this->length) {
            throw new SyntaxError('Unexpected end of input', $this->line);
        }

        $char = $this->input[$this->position];

        if (null !== $validChars && !\in_array($char, (array) $validChars, true)) {
            throw new SyntaxError('Expected one of ['.implode('', (array) $validChars)."] but found '{$char}'.", $this->line);
        }

        ++$this->position;

        return $char;
    }

    private function consumeUntil(string $endString): string
    {
        $start = $this->position;
        $endCharLength = \strlen($endString);

        while ($this->position < $this->length) {
            if (substr($this->input, $this->position, $endCharLength) === $endString) {
                break;
            }

            if ("\n" === $this->input[$this->position]) {
                ++$this->line;
            }
            ++$this->position;
        }

        return substr($this->input, $start, $this->position - $start);
    }

    private function consumeWhitespace(): void
    {
        while ($this->position < $this->length && preg_match('/\s/', $this->input[$this->position])) {
            if ("\n" === $this->input[$this->position]) {
                ++$this->line;
            }
            ++$this->position;
        }
    }

    /**
     * Checks that the next character is the one given and consumes it.
     */
    private function expectAndConsumeChar(string $char): void
    {
        if (1 !== \strlen($char)) {
            throw new \InvalidArgumentException('Expected a single character');
        }

        if ($this->position >= $this->length || $this->input[$this->position] !== $char) {
            throw new SyntaxError("Expected '{$char}' but found '{$this->input[$this->position]}'.", $this->line);
        }
        ++$this->position;
    }

    private function check(string $chars): bool
    {
        $charsLength = \strlen($chars);
        if ($this->position + $charsLength > $this->length) {
            return false;
        }

        for ($i = 0; $i < $charsLength; ++$i) {
            if ($this->input[$this->position + $i] !== $chars[$i]) {
                return false;
            }
        }

        return true;
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
        if (!$this->doesStringEventuallyExist($closingTag)) {
            throw new SyntaxError("Expected closing tag '{$closingTag}' for block '{$blockName}'.", $this->line);
        }
        $blockContents = $this->consumeUntil($closingTag);

        $subLexer = new self($this->line);
        $output .= $subLexer->preLexComponents($blockContents);

        $this->consume($closingTag);
        $output .= '{% endblock %}';

        return $output;
    }

    private function doesStringEventuallyExist(string $needle): bool
    {
        $remainingString = substr($this->input, $this->position);

        return str_contains($remainingString, $needle);
    }
}
