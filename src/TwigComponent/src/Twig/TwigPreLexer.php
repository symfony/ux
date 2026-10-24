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
    // Twig\Lexer::REGEX_NAME can not be reused for composing the patterns we need
    private const NAME = '[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*';
    private const TAG_NAME_REGEX = '/\{%[-~]?\s*('.self::NAME.')/A';
    // a tag carrying at most one name after its own, such as {% block foo %} or {% endblock %}
    private const BARE_TAG_REGEX = '/^\{%[-~]?\s*('.self::NAME.')(?:\s+'.self::NAME.')?\s*[-~]?%\}$/D';
    private const VERBATIM_REGEX = '/\G\{%[-~]?\s*verbatim\s*[-~]?%\}/';
    private const ENDVERBATIM_REGEX = '/\{%[-~]?\s*endverbatim\s*[-~]?%\}/';
    private const COMPONENT_NAME_CHAR = '[A-Za-z0-9_:@\-.]';
    private const SLOT_OPENING_REGEX = '/\G<twig:block(?!'.self::COMPONENT_NAME_CHAR.')/';
    private const SLOT_CLOSING_TAG = '</twig:block>';

    private const COMPONENT = 'component';
    private const SLOT = 'slot';
    private const BLOCK = 'block';
    private const EMBED = 'embed';

    private string $input;
    private int $length;
    private int $position;
    private string $output;
    /**
     * The constructs the cursor is in, innermost last.
     *
     * @var list<array{type: string, name: string, hasDefaultBlock: bool}>
     */
    private array $frames;

    public function __construct(
        private readonly int $startingLine = 1,
    ) {
    }

    public function preLexComponents(string $input): string
    {
        if (!str_contains($input, '<twig:')) {
            return $input;
        }

        $this->input = str_replace(["\r\n", "\r"], "\n", $input);
        $this->length = \strlen($this->input);
        $this->position = 0;
        $this->output = '';
        $this->frames = [];

        while ($this->position < $this->length) {
            $char = $this->input[$this->position];

            if ('{' === $char) {
                $this->lexCurlyBrace();
            } elseif ('<' === $char) {
                $this->lexAngleBracket();
            } else {
                $this->lexText();
            }
        }

        foreach (array_reverse($this->frames) as $frame) {
            if (self::COMPONENT === $frame['type']) {
                throw new SyntaxError(\sprintf('Expected closing tag "</twig:%s>" not found.', $frame['name']), $this->currentLine());
            }
        }

        foreach ($this->frames as $frame) {
            if (self::SLOT === $frame['type']) {
                $this->output .= '{% endblock %}';
            }
        }

        return $this->output;
    }

    private function lexCurlyBrace(): void
    {
        if ($this->check('{#')) {
            $this->output .= $this->readComment();

            return;
        }

        if ($this->check('{{')) {
            $this->openDefaultBlock();
            $this->output .= $this->readTwigCode('{{', '}}');

            return;
        }

        if ($this->check('{%')) {
            $this->lexTag();

            return;
        }

        $this->lexText();
    }

    private function lexAngleBracket(): void
    {
        if ($this->consume('<twig:')) {
            $this->lexComponentOpening();

            return;
        }

        $frameType = $this->enclosingFrameType();

        if (self::COMPONENT === $frameType && $this->consume('</twig:')) {
            $this->lexComponentClosing();

            return;
        }

        if (self::SLOT === $frameType && $this->consume(self::SLOT_CLOSING_TAG)) {
            $this->popEnclosingFrame();
            $this->output .= '{% endblock %}';

            return;
        }

        $this->lexText();
    }

    private function lexText(): void
    {
        $length = 1 + strcspn($this->input, '{<', $this->position + 1);
        $text = substr($this->input, $this->position, $length);
        $this->position += $length;

        if ($this->needsDefaultBlock()) {
            $whitespace = strspn($text, " \t\n\v\f\r");

            if ($whitespace < $length) {
                $this->output .= substr($text, 0, $whitespace);
                $this->openDefaultBlock();
                $text = substr($text, $whitespace);
            }
        }

        $this->output .= $text;
    }

    private function lexTag(): void
    {
        if (null !== $verbatim = $this->readVerbatim()) {
            $this->openDefaultBlock();
            $this->output .= $verbatim;

            return;
        }

        $tag = $this->readTwigCode('{%', '%}');
        $name = preg_match(self::TAG_NAME_REGEX, $tag, $matches) ? $matches[1] : null;
        $isBare = 1 === preg_match(self::BARE_TAG_REGEX, $tag);

        // a block written in a component overrides a block of that component, its body is kept as is
        if ('block' === $name && self::COMPONENT === $this->enclosingFrameType()) {
            $this->closeDefaultBlock();
            $this->output .= $tag;

            if ($isBare) {
                $this->output .= $this->readBlockBody();
            }

            return;
        }

        $this->openDefaultBlock();
        $this->output .= $tag;

        if ('block' === $name && $isBare) {
            $this->pushFrame(self::BLOCK);
        } elseif ('embed' === $name) {
            $this->pushFrame(self::EMBED);
        } elseif ($isBare && ('endblock' === $name || 'endembed' === $name)) {
            $closedFrameType = 'endblock' === $name ? self::BLOCK : self::EMBED;

            if ($closedFrameType === $this->currentFrameType()) {
                array_pop($this->frames);
            }
        }
    }

    private function lexComponentOpening(): void
    {
        $componentName = $this->consumeComponentName();

        if ('block' === $componentName) {
            $this->closeDefaultBlock();
            $this->lexSlotOpening();

            return;
        }

        $this->openDefaultBlock();

        // Handle <twig:component is="..." /> and <twig:component is="{{ ... }}" />
        $isDynamicComponent = false;
        $componentExpression = $componentName;
        if ('component' === strtolower($componentName)) {
            $resolved = $this->consumeDynamicComponentName();
            if (null !== $resolved) {
                $componentExpression = $resolved['expression'];
                $isDynamicComponent = $resolved['isDynamic'];
            }
        }

        $attributes = $this->consumeAttributes($componentName);
        $isSelfClosing = $this->consume('/>');
        if (!$isSelfClosing) {
            $this->consume('>');
            $this->pushFrame(self::COMPONENT, $componentName);
        }

        if ($isDynamicComponent) {
            if ($isSelfClosing) {
                $this->output .= "{{ component({$componentExpression}".($attributes ? ", { {$attributes} }" : '').') }}';
            } else {
                $this->output .= "{% component ({$componentExpression})".($attributes ? " with { {$attributes} }" : '').' %}';
            }
        } elseif ($isSelfClosing) {
            // use the simpler component() format, so that the system doesn't think
            // this is an "embedded" component with blocks
            // see https://github.com/symfony/ux/issues/810
            $this->output .= "{{ component('{$componentExpression}'".($attributes ? ", { {$attributes} }" : '').') }}';
        } else {
            $this->output .= "{% component '{$componentExpression}'".($attributes ? " with { {$attributes} }" : '').' %}';
        }
    }

    private function lexComponentClosing(): void
    {
        $closingComponentName = $this->consumeComponentName();
        $this->consume('>');

        $component = $this->popEnclosingFrame();

        if ($closingComponentName !== $component['name']) {
            throw new SyntaxError("Expected closing tag '</twig:{$component['name']}>' but found '</twig:{$closingComponentName}>'.", $this->currentLine());
        }

        if ($component['hasDefaultBlock']) {
            $this->output .= '{% endblock %}';
        }

        $this->output .= '{% endcomponent %}';
    }

    private function lexSlotOpening(): void
    {
        $attributes = $this->consumeAttributes('block');
        $this->consume('>');

        $blockName = '';
        foreach (explode(', ', $attributes) as $attr) {
            $parts = explode(': ', $attr);
            if ('name' === $parts[0] && isset($parts[1])) {
                $blockName = trim($parts[1], "'");
                break;
            }
        }

        if (empty($blockName)) {
            throw new SyntaxError('Expected block name.', $this->currentLine());
        }

        if (false === strpos($this->input, self::SLOT_CLOSING_TAG, $this->position)) {
            throw new SyntaxError(\sprintf("Expected closing tag '%s' for block '%s'.", self::SLOT_CLOSING_TAG, $blockName), $this->currentLine());
        }

        $this->output .= "{% block {$blockName} %}";
        $this->pushFrame(self::SLOT);
    }

    private function pushFrame(string $type, string $name = ''): void
    {
        $this->frames[] = ['type' => $type, 'name' => $name, 'hasDefaultBlock' => false];
    }

    private function currentFrameType(): ?string
    {
        return [] === $this->frames ? null : $this->frames[array_key_last($this->frames)]['type'];
    }

    /**
     * Returns the type of the innermost frame that is not an embed.
     */
    private function enclosingFrameType(): ?string
    {
        for ($i = \count($this->frames) - 1; $i >= 0; --$i) {
            if (self::EMBED !== $this->frames[$i]['type']) {
                return $this->frames[$i]['type'];
            }
        }

        return null;
    }

    /**
     * Pops the innermost frame that is not an embed, along with the unclosed embeds above it.
     *
     * @return array{type: string, name: string, hasDefaultBlock: bool}
     */
    private function popEnclosingFrame(): array
    {
        do {
            $frame = array_pop($this->frames);
        } while (self::EMBED === $frame['type']);

        return $frame;
    }

    private function needsDefaultBlock(): bool
    {
        return self::COMPONENT === $this->currentFrameType()
            && !$this->frames[array_key_last($this->frames)]['hasDefaultBlock'];
    }

    private function openDefaultBlock(): void
    {
        if ($this->needsDefaultBlock()) {
            $this->output .= '{% block content %}';
            $this->frames[array_key_last($this->frames)]['hasDefaultBlock'] = true;
        }
    }

    private function closeDefaultBlock(): void
    {
        if (self::COMPONENT !== $this->currentFrameType()) {
            return;
        }

        $top = array_key_last($this->frames);

        if ($this->frames[$top]['hasDefaultBlock']) {
            $this->output .= '{% endblock %}';
            $this->frames[$top]['hasDefaultBlock'] = false;
        }
    }

    private function currentLine(): int
    {
        return $this->startingLine + substr_count($this->input, "\n", 0, $this->position);
    }

    /**
     * Reads the body of a block up to its endblock included, or up to the closing tag of the slot it sits in.
     */
    private function readBlockBody(): string
    {
        $start = $this->position;
        $depth = 1;

        while (($this->position += strcspn($this->input, '{<', $this->position)) < $this->length) {
            if ($this->check('{#')) {
                $this->readComment();
            } elseif ($this->check('{{')) {
                $this->readTwigCode('{{', '}}');
            } elseif ($this->check('{%')) {
                if (null !== $this->readVerbatim()) {
                    continue;
                }

                $tag = $this->readTwigCode('{%', '%}');

                if (!preg_match(self::BARE_TAG_REGEX, $tag, $matches)) {
                    continue;
                }

                if ('block' === $matches[1]) {
                    ++$depth;
                } elseif ('endblock' === $matches[1] && 0 === --$depth) {
                    break;
                }
            } elseif ($this->check(self::SLOT_CLOSING_TAG)) {
                if (1 === $depth) {
                    break;
                }

                --$depth;
                $this->consume(self::SLOT_CLOSING_TAG);
            } else {
                if (preg_match(self::SLOT_OPENING_REGEX, $this->input, $matches, 0, $this->position)) {
                    ++$depth;
                }

                ++$this->position;
            }
        }

        return substr($this->input, $start, $this->position - $start);
    }

    private function readComment(): string
    {
        $start = $this->position;
        $end = strpos($this->input, '#}', $start + 2);

        if (false === $end) {
            $this->position = $this->length;

            return substr($this->input, $start).'#}';
        }

        $this->position = $end + 2;

        return substr($this->input, $start, $this->position - $start);
    }

    /**
     * Reads a whole verbatim block, opening tag included, or returns null if there is none at the cursor.
     */
    private function readVerbatim(): ?string
    {
        if (!preg_match(self::VERBATIM_REGEX, $this->input, $matches, 0, $this->position)) {
            return null;
        }

        $bodyStart = $this->position + \strlen($matches[0]);

        if (preg_match(self::ENDVERBATIM_REGEX, $this->input, $matches, \PREG_OFFSET_CAPTURE, $bodyStart)) {
            $end = $matches[0][1] + \strlen($matches[0][0]);
        } else {
            $end = $this->length;
        }

        $verbatim = substr($this->input, $this->position, $end - $this->position);
        $this->position = $end;

        return $verbatim;
    }

    /**
     * Reads a Twig output expression or tag, from its opening delimiter to its closing one.
     */
    private function readTwigCode(string $openingDelimiter, string $closingDelimiter): string
    {
        $this->position += 2;
        $code = $openingDelimiter.$this->consumeTwigExpressionContent($closingDelimiter);

        if ($this->consume($closingDelimiter)) {
            $code .= $closingDelimiter;
        }

        return $code;
    }

    private function consumeComponentName(?string $customExceptionMessage = null): string
    {
        if (preg_match('/\G'.self::COMPONENT_NAME_CHAR.'+/', $this->input, $matches, 0, $this->position)) {
            $componentName = $matches[0];
            $this->position += \strlen($componentName);

            return $componentName;
        }

        throw new SyntaxError($customExceptionMessage ?? 'Expected component name when resolving the "<twig:" syntax.', $this->currentLine());
    }

    /**
     * @return array{expression: string, isDynamic: bool}|null
     */
    private function consumeDynamicComponentName(): ?array
    {
        $savedPosition = $this->position;

        $this->consumeWhitespace();

        $isDynamic = false;

        if ($this->consume(':is=')) {
            $isDynamic = true;
        } elseif ($this->consume('is=')) {
            $isDynamic = false;
        } else {
            $this->position = $savedPosition;

            return null;
        }

        $quote = $this->consumeChar(["'", '"']);
        $componentExpression = $this->consumeUntil($quote);
        $this->expectAndConsumeChar($quote);

        if ('' === $componentExpression) {
            throw new SyntaxError(\sprintf('The "%s" attribute of "<twig:component>" must not be empty', $isDynamic ? ':is' : 'is'), $this->currentLine());
        }

        if (!$isDynamic && str_starts_with($componentExpression, '{{') && str_ends_with($componentExpression, '}}')) {
            $componentExpression = trim(substr($componentExpression, 2, -2));
            if ('' === $componentExpression) {
                throw new SyntaxError('The "is" attribute of "<twig:component>" must not be empty.', $this->currentLine());
            }
            $isDynamic = true;
        }

        return ['expression' => $componentExpression, 'isDynamic' => $isDynamic];
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
                    throw new SyntaxError(\sprintf('Expected "=" after ":%s" when parsing the "<twig:%s" syntax.', $key, $componentName), $this->currentLine());
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
        $length = \strlen($string);

        if ($this->position + $length <= $this->length && 0 === substr_compare($this->input, $string, $this->position, $length)) {
            $this->position += $length;

            return true;
        }

        return false;
    }

    private function consumeChar($validChars = null): string
    {
        if ($this->position >= $this->length) {
            throw new SyntaxError('Unexpected end of input.', $this->currentLine());
        }

        $char = $this->input[$this->position];

        if (null !== $validChars && !\in_array($char, (array) $validChars, true)) {
            throw new SyntaxError('Expected one of [.'.implode('', (array) $validChars)."] but found '{$char}'.", $this->currentLine());
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
        $this->position = $endPosition;

        return $content;
    }

    private function consumeWhitespace(): void
    {
        $this->position += strspn($this->input, " \t\n\r\0\x0B", $this->position);

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
            throw new SyntaxError("Expected '{$char}' but reached the end of the file.", $this->currentLine());
        }

        if ($this->input[$this->position] !== $char) {
            throw new SyntaxError("Expected '{$char}' but found '{$this->input[$this->position]}'.", $this->currentLine());
        }

        ++$this->position;
    }

    private function check(string $chars): bool
    {
        return $this->position + \strlen($chars) <= $this->length
            && 0 === substr_compare($this->input, $chars, $this->position, \strlen($chars));
    }

    /**
     * Consumes the body of a Twig output expression or tag (between `{{` and `}}`, or `{%` and `%}`).
     *
     * String literals and `#` inline comments are skipped, so a closing delimiter inside them does not end the expression early.
     */
    private function consumeTwigExpressionContent(string $closingDelimiter): string
    {
        $start = $this->position;
        $stopChars = "\"'#".$closingDelimiter[0];

        while (($this->position += strcspn($this->input, $stopChars, $this->position)) < $this->length) {
            if ($this->check($closingDelimiter)) {
                return substr($this->input, $start, $this->position - $start);
            }

            $char = $this->input[$this->position];

            if ("'" === $char || '"' === $char) {
                $this->skipStringLiteral($char);
                continue;
            }

            if ('#' === $char) {
                $this->consumeUntil("\n");
                continue;
            }

            ++$this->position;
        }

        return substr($this->input, $start);
    }

    private function skipStringLiteral(string $quote): void
    {
        ++$this->position; // opening quote

        while ($this->position < $this->length) {
            $char = $this->input[$this->position];

            if ('\\' === $char && $this->position + 1 < $this->length) {
                $this->position += 2;
                continue;
            }

            if ($char === $quote) {
                ++$this->position;

                return;
            }

            ++$this->position;
        }
    }

    private function consumeAttributeValue(string $quote): string
    {
        $parts = [];
        $currentPart = '';
        while ($this->position < $this->length) {
            if ($this->check($quote)) {
                break;
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
