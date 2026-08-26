<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\UX\StimulusBundle\Dto\StimulusAttributes;
use Symfony\UX\TwigComponent\ComponentAttributes;
use Twig\Environment;
use Twig\Extra\Html\HtmlAttr\AttributeValueInterface;
use Twig\Extra\Html\HtmlAttr\InlineStyle;
use Twig\Extra\Html\HtmlAttr\MergeableInterface;
use Twig\Extra\Html\HtmlExtension;
use Twig\Loader\ArrayLoader;
use Twig\Runtime\EscaperRuntime;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ComponentAttributesTest extends TestCase
{
    public function testCanConvertToString()
    {
        $attributes = new ComponentAttributes([
            'class' => 'foo',
            'style' => new class {
                public function __toString(): string
                {
                    return 'color:black;';
                }
            },
            'value' => '',
            'autofocus' => true,
        ], new EscaperRuntime());

        $this->assertSame(' class="foo" style="color:black;" value="" autofocus=""', (string) $attributes);
    }

    public function testNullIsOmittedNotThrown()
    {
        $attributes = new ComponentAttributes(['x' => null, 'class' => 'foo'], new EscaperRuntime());
        $this->assertSame(' class="foo"', (string) $attributes);
    }

    public function testBooleanTrueRendersEmptyValue()
    {
        $attributes = new ComponentAttributes(['autofocus' => true], new EscaperRuntime());
        $this->assertSame(' autofocus=""', (string) $attributes);
    }

    public function testAriaFalseRendersFalseString()
    {
        $attributes = new ComponentAttributes(['aria-expanded' => false], new EscaperRuntime());
        $this->assertSame(' aria-expanded="false"', (string) $attributes);
    }

    public function testDataTrueRendersTrueString()
    {
        $attributes = new ComponentAttributes(['data-open' => true], new EscaperRuntime());
        $this->assertSame(' data-open="true"', (string) $attributes);
    }

    public function testArrayValueRendersTokenList()
    {
        $attributes = new ComponentAttributes(['class' => ['a', 'b']], new EscaperRuntime());
        $this->assertSame(' class="a b"', (string) $attributes);
    }

    public function testEmptyRendersEmptyString()
    {
        $attributes = new ComponentAttributes([], new EscaperRuntime());
        $this->assertSame('', (string) $attributes);
    }

    #[DataProvider('provideParityCases')]
    public function testOutputMatchesHtmlAttr(array $attributes)
    {
        $env = new Environment(new ArrayLoader());
        $expected = HtmlExtension::htmlAttr($env, $attributes);
        $expected = '' === $expected ? '' : ' '.$expected;

        $this->assertSame($expected, (string) new ComponentAttributes($attributes, new EscaperRuntime()));
    }

    public static function provideParityCases(): iterable
    {
        yield 'scalars' => [['class' => 'a b', 'id' => 'x', 'tabindex' => 0]];
        yield 'booleans' => [['autofocus' => true, 'disabled' => false, 'data-open' => true]];
        yield 'aria' => [['aria-expanded' => false, 'aria-hidden' => true]];
        yield 'null and iterable' => [['x' => null, 'class' => ['a', 'b']]];
        yield 'special keys' => [['@click' => 'go', ':class' => 'c', 'x-on:click' => 'h']];
    }

    public function testCanSetDefaults()
    {
        $attributes = new ComponentAttributes(['class' => 'foo', 'style' => 'color:black;'], new EscaperRuntime());

        $this->assertSame(
            ['class' => 'bar foo', 'style' => 'color:black;'],
            $attributes->defaults(['class' => 'bar', 'style' => 'font-size: 10;'])->all()
        );
        $this->assertSame(
            ' class="bar foo" style="color:black;"',
            (string) $attributes->defaults(['class' => 'bar', 'style' => 'font-size: 10;'])
        );

        $this->assertSame(['class' => 'foo'], new ComponentAttributes([], new EscaperRuntime())->defaults(['class' => 'foo'])->all());
    }

    public function testDefaultsMergesMergeableDefaultViaAppendFrom()
    {
        if (!interface_exists(MergeableInterface::class)) {
            $this->markTestSkipped('Requires twig/html-extra >= 3.24.');
        }

        // The component default is mergeable, the caller passed a plain "class".
        $attributes = new ComponentAttributes(['class' => 'override'], new EscaperRuntime());

        $merged = $attributes->defaults(['class' => $this->mergeableClasses('base-1', 'base-2')]);

        // default (base) is on the left, caller (override) is appended on the right
        $this->assertSame(' class="base-1 base-2 override"', (string) $merged);
        $this->assertSame('base-1 base-2 override', $merged->render('class'));
    }

    public function testDefaultsMergesMergeableCallerViaMergeInto()
    {
        if (!interface_exists(MergeableInterface::class)) {
            $this->markTestSkipped('Requires twig/html-extra >= 3.24.');
        }

        // The caller passed a mergeable "class", the component default is a plain string.
        $attributes = new ComponentAttributes(['class' => $this->mergeableClasses('override')], new EscaperRuntime());

        $merged = $attributes->defaults(['class' => 'base']);

        $this->assertSame(' class="base override"', (string) $merged);
    }

    public function testRenderReturnsNullWhenMergeableValueResolvesToNull()
    {
        if (!interface_exists(MergeableInterface::class)) {
            $this->markTestSkipped('Requires twig/html-extra >= 3.24.');
        }

        $attributes = new ComponentAttributes(['class' => $this->mergeableClasses()], new EscaperRuntime());

        $this->assertNull($attributes->render('class'));
    }

    public function testDefaultsMergesMergeableDefaultOnNonSpecialKey()
    {
        if (!interface_exists(MergeableInterface::class)) {
            $this->markTestSkipped('Requires twig/html-extra >= 3.24.');
        }

        // "data-foo" is not a special key, yet a mergeable default is merged
        // instead of being overwritten by the caller's plain value.
        $attributes = new ComponentAttributes(['data-foo' => 'caller'], new EscaperRuntime());

        $merged = $attributes->defaults(['data-foo' => $this->mergeableClasses('base')]);

        $this->assertSame('base caller', $merged->render('data-foo'));
    }

    public function testDefaultsMergesRealMergeableOnNonSpecialKey()
    {
        if (!class_exists(InlineStyle::class)) {
            $this->markTestSkipped('Requires twig/html-extra >= 3.24.');
        }

        // Generalization with a real Twig HTML extra mergeable (InlineStyle) on "style":
        // the caller value wins conflicts, appended after the default.
        $attributes = new ComponentAttributes(['style' => new InlineStyle(['display: block'])], new EscaperRuntime());

        $merged = $attributes->defaults(['style' => new InlineStyle(['color: red'])]);

        $this->assertSame('color: red; display: block;', $merged->render('style'));
    }

    /**
     * A minimal space-separated token list implementing the Twig HTML extra merge protocol,
     * so these tests exercise ComponentAttributes routing without depending on a concrete
     * implementation (e.g. tailwind_classes).
     */
    private function mergeableClasses(string ...$classes): object
    {
        return new class($classes) implements AttributeValueInterface, MergeableInterface {
            /** @param list<string> $classes */
            public function __construct(private array $classes)
            {
            }

            public function getValue(): ?string
            {
                return $this->classes ? implode(' ', $this->classes) : null;
            }

            public function mergeInto(mixed $previous): mixed
            {
                $previousClasses = $previous instanceof self ? $previous->classes : array_filter([(string) $previous], 'strlen');

                return new self([...$previousClasses, ...$this->classes]);
            }

            public function appendFrom(mixed $newValue): mixed
            {
                $newClasses = $newValue instanceof self ? $newValue->classes : array_filter([(string) $newValue], 'strlen');

                return new self([...$this->classes, ...$newClasses]);
            }
        };
    }

    public function testCanGetOnly()
    {
        $attributes = new ComponentAttributes(['class' => 'foo', 'style' => 'color:black;'], new EscaperRuntime());

        $this->assertSame(['class' => 'foo'], $attributes->only('class')->all());
    }

    public function testCanGetWithout()
    {
        $attributes = new ComponentAttributes(['class' => 'foo', 'style' => 'color:black;', 'data-foo' => 'bar'], new EscaperRuntime());

        $this->assertSame(['class' => 'foo', 'data-foo' => 'bar'], $attributes->without('style')->all());
        $this->assertSame(['class' => 'foo'], $attributes->without('style', 'data-foo')->all());
    }

    public function testCanAddStimulusControllerViaStimulusAttributes()
    {
        $attributes = new ComponentAttributes([
            'class' => 'foo',
            'data-controller' => 'live',
            'data-live-data-value' => '{}',
        ], new EscaperRuntime());

        $stimulusAttributes = new StimulusAttributes(new Environment(new ArrayLoader()));
        $stimulusAttributes->addController('foo', ['name' => 'ryan', 'some_array' => ['a', 'b'], 'some_array_with_keys' => ['key1' => 'value1', 'key2' => 'value2']]);
        $attributes = $attributes->defaults($stimulusAttributes);

        $this->assertEquals([
            'class' => 'foo',
            'data-controller' => 'foo live',
            'data-live-data-value' => '{}',
            'data-foo-name-value' => 'ryan',
            'data-foo-some-array-value' => '["a","b"]',
            'data-foo-some-array-with-keys-value' => '{"key1":"value1","key2":"value2"}',
        ], $attributes->all());
        $this->assertSame(' data-controller="foo live" data-foo-name-value="ryan" data-foo-some-array-value="[&quot;a&quot;,&quot;b&quot;]" data-foo-some-array-with-keys-value="{&quot;key1&quot;:&quot;value1&quot;,&quot;key2&quot;:&quot;value2&quot;}" class="foo" data-live-data-value="{}"', (string) $attributes);
    }

    public function testCanAddStimulusActionViaStimulusAttributes()
    {
        $attributes = new ComponentAttributes([
            'class' => 'foo',
            'data-action' => 'live#foo',
        ], new EscaperRuntime());

        $stimulusAttributes = new StimulusAttributes(new Environment(new ArrayLoader()));
        $stimulusAttributes->addAction('foo', 'barMethod');
        $attributes = $attributes->defaults([...$stimulusAttributes]);

        $this->assertEquals([
            'class' => 'foo',
            'data-action' => 'foo#barMethod live#foo',
        ], $attributes->all());
        $this->assertSame(' data-action="foo#barMethod live#foo" class="foo"', (string) $attributes);
    }

    public function testBooleanBehaviour()
    {
        $attributes = new ComponentAttributes(['disabled' => true], new EscaperRuntime());

        $this->assertSame(['disabled' => true], $attributes->all());
        $this->assertSame(' disabled=""', (string) $attributes);

        $attributes = new ComponentAttributes(['disabled' => false], new EscaperRuntime());

        $this->assertSame(['disabled' => false], $attributes->all());
        $this->assertSame('', (string) $attributes);
    }

    public function testIsTraversableAndCountable()
    {
        $attributes = new ComponentAttributes(['foo' => 'bar'], new EscaperRuntime());

        $this->assertSame($attributes->all(), iterator_to_array($attributes));
        $this->assertCount(1, $attributes);
    }

    public function testRenderSingleAttribute()
    {
        $attributes = new ComponentAttributes(['attr1' => 'value1', 'attr2' => 'value2'], new EscaperRuntime());

        $this->assertSame('value1', $attributes->render('attr1'));
        $this->assertNull($attributes->render('attr3'));
    }

    public function testRenderingSingleAttributeExcludesFromString()
    {
        $attributes = new ComponentAttributes([
            'attr1' => new class {
                public function __toString(): string
                {
                    return 'value1';
                }
            },
            'attr2' => 'value2',
        ], new EscaperRuntime());

        $this->assertSame('value1', $attributes->render('attr1'));
        $this->assertSame(' attr2="value2"', (string) $attributes);
    }

    public function testRenderOmittedAttributeReturnsNull()
    {
        $attributes = new ComponentAttributes(['attr1' => false, 'attr2' => null], new EscaperRuntime());

        $this->assertNull($attributes->render('attr1'));
        $this->assertNull($attributes->render('attr2'));
    }

    public function testRenderResolvesValueLikeHtmlAttr()
    {
        $attributes = new ComponentAttributes([
            'class' => ['a', 'b'],
            'hidden' => true,
            'data-open' => true,
            'data-config' => ['theme' => 'dark'],
        ], new EscaperRuntime());

        $this->assertSame('a b', $attributes->render('class'));
        $this->assertSame('', $attributes->render('hidden'));
        $this->assertSame('true', $attributes->render('data-open'));
        $this->assertSame('{"theme":"dark"}', $attributes->render('data-config'));
    }

    public function testCanCheckIfAttributeExists()
    {
        $attributes = new ComponentAttributes(['foo' => 'bar'], new EscaperRuntime());

        $this->assertTrue($attributes->has('foo'));
    }

    public function testNestedAttributes()
    {
        $attributes = new ComponentAttributes([
            'class' => 'foo',
            'title:class' => 'bar',
            'title:span:class' => 'baz',
        ], new EscaperRuntime());

        $this->assertSame(' class="foo"', (string) $attributes);
        $this->assertSame(' class="bar"', (string) $attributes->nested('title'));
        $this->assertSame(' class="baz"', (string) $attributes->nested('title')->nested('span'));
        $this->assertSame('', (string) $attributes->nested('invalid'));
    }

    public function testPrefixedAttributes()
    {
        $attributes = new ComponentAttributes([
            'x-click' => 'x+',
            'title:x-click' => 'title:x+',
        ], new EscaperRuntime());

        $this->assertSame(' x-click="x+"', (string) $attributes);
        $this->assertSame(' x-click="title:x+"', (string) $attributes->nested('title'));
        $this->assertSame('', (string) $attributes->nested('title')->nested('span'));
        $this->assertSame('', (string) $attributes->nested('invalid'));
    }

    public function testConvertTrueAriaAttributeValue()
    {
        $attributes = new ComponentAttributes([
            'aria-bar' => false,
            'aria-foo' => true,
            'aria-true' => 'true',
            'aria-false' => 'false',
            'aria-foobar' => 'foobar',
            'aria-number' => '1',
        ], new EscaperRuntime());

        $this->assertStringContainsString('aria-bar="false"', (string) $attributes);
        $this->assertStringContainsString('aria-foo="true"', (string) $attributes);
        $this->assertStringContainsString('aria-true="true"', (string) $attributes);
        $this->assertStringContainsString('aria-false="false"', (string) $attributes);
        $this->assertStringContainsString('aria-foobar="foobar"', (string) $attributes);
        $this->assertStringContainsString('aria-number="1"', (string) $attributes);

        $this->assertSame('true', $attributes->render('aria-foo'));
        $this->assertSame('true', $attributes->render('aria-true'));
        $this->assertSame('false', $attributes->render('aria-false'));
        $this->assertSame('foobar', $attributes->render('aria-foobar'));
        $this->assertSame('1', $attributes->render('aria-number'));

        $this->assertSame('false', $attributes->render('aria-bar'));
    }

    #[DataProvider('provideSpecialSyntaxAttributeNames')]
    public function testAllowsSpecialSyntaxAttributeNames(string $name)
    {
        $attributes = new ComponentAttributes([$name => 'value'], new EscaperRuntime());

        $this->assertSame(' '.$name.'="value"', (string) $attributes);
    }

    public static function provideSpecialSyntaxAttributeNames(): iterable
    {
        // Vue.js
        yield ['v-on:click'];
        yield ['@click'];
        // Alpine.js
        yield ['x-on:click'];
        yield ['@input.debounce.500ms'];
    }

    public function testThrowsTypeErrorWithoutEscaperRuntime()
    {
        $this->expectException(\TypeError::class);
        new ComponentAttributes([]);
    }

    #[DataProvider('nameProvider')]
    public function testEscapeName(string $input, string $expected)
    {
        $attributes = new ComponentAttributes([$input => 'foo'], new EscaperRuntime());

        $this->assertSame(' '.$expected.'="foo"', (string) $attributes);
    }

    #[DataProvider('valueProvider')]
    public function testEscapeValue(string $input, string $expected)
    {
        $attributes = new ComponentAttributes(['foo' => $input], new EscaperRuntime());

        $this->assertSame(' foo="'.$expected.'"', (string) $attributes);
    }

    public static function nameProvider(): iterable
    {
        // Should not escape
        yield 'basic' => ['class', 'class'];
        yield 'data-' => ['data-user', 'data-user'];
        yield 'aria' => ['aria-label', 'aria-label'];
        yield 'alnum' => ['attr123', 'attr123'];
        // Should escape
        yield 'double quote' => ['"', '&quot;'];
        yield 'ampersand' => ['&', '&amp;'];
        yield 'less than' => ['<', '&lt;'];
        yield 'greater than' => ['>', '&gt;'];
        // Twig strict escaping
        yield 'scripts' => ['><script>', '&gt;&lt;script&gt;'];
        yield 'single quote' => ["'", '&#x27;'];
        yield 'unicode' => ['data-🚀', 'data-&#x1F680;'];
    }

    public static function valueProvider(): iterable
    {
        // Should not escape
        yield 'plain text' => ['Hello', 'Hello'];
        yield 'numeric value' => ['42', '42'];
        yield 'js url' => ['javascript:alert(1)', 'javascript:alert(1)'];
        // Should escape
        yield 'ampersand' => ['Hello & Welcome', 'Hello &amp; Welcome'];
        yield 'single quote' => ["O'Reilly", 'O&#039;Reilly'];
        yield 'double quotes' => ['"Hello"', '&quot;Hello&quot;'];
        yield 'less than' => ['<', '&lt;'];
        yield 'greater than' => ['>', '&gt;'];
        yield 'script' => ['<script>alert(1)</script>', '&lt;script&gt;alert(1)&lt;/script&gt;'];
        yield 'inline xss' => ['<img src=x onerror=alert(1)>', '&lt;img src=x onerror=alert(1)&gt;'];
        yield 'malicious attr' => ['foo="bar"', 'foo=&quot;bar&quot;'];
        yield 'sql injection' => ["' OR 1=1 --", '&#039; OR 1=1 --'];
        yield 'url encoded xss' => ['%3Cscript%3Ealert(1)%3C/script%3E', '%3Cscript%3Ealert(1)%3C/script%3E'];
    }
}
