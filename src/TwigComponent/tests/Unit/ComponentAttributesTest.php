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

use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\UX\StimulusBundle\Dto\StimulusAttributes;
use Symfony\UX\TwigComponent\ComponentAttributes;
use Symfony\WebpackEncoreBundle\Dto\AbstractStimulusDto;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Runtime\EscaperRuntime;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ComponentAttributesTest extends TestCase
{
    use ExpectDeprecationTrait;

    public function testCanConvertToString(): void
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

        $this->assertSame(' class="foo" style="color:black;" value="" autofocus', (string) $attributes);
    }

    public function testCanSetDefaults(): void
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

        $this->assertSame(['class' => 'foo'], (new ComponentAttributes([], new EscaperRuntime()))->defaults(['class' => 'foo'])->all());
    }

    public function testCanGetOnly(): void
    {
        $attributes = new ComponentAttributes(['class' => 'foo', 'style' => 'color:black;'], new EscaperRuntime());

        $this->assertSame(['class' => 'foo'], $attributes->only('class')->all());
    }

    public function testCanGetWithout(): void
    {
        $attributes = new ComponentAttributes(['class' => 'foo', 'style' => 'color:black;'], new EscaperRuntime());

        $this->assertSame(['class' => 'foo'], $attributes->without('style')->all());
    }

    /**
     * @group legacy
     */
    public function testCanAddStimulusController(): void
    {
        $attributes = new ComponentAttributes([
            'class' => 'foo',
            'data-controller' => 'live',
            'data-live-data-value' => '{}',
        ], new EscaperRuntime());

        $controllerDto = $this->createMock(AbstractStimulusDto::class);
        $controllerDto->expects(self::once())
            ->method('toArray')
            ->willReturn([
                'data-controller' => 'foo bar',
                'data-foo-name-value' => 'ryan',
            ]);

        $attributes = $attributes->add($controllerDto);

        $this->assertEquals([
            'class' => 'foo',
            'data-controller' => 'live foo bar',
            'data-live-data-value' => '{}',
            'data-foo-name-value' => 'ryan',
        ], $attributes->all());
    }

    /**
     * @group legacy
     */
    public function testCanAddStimulusControllerIfNoneAlreadyPresent(): void
    {
        $attributes = new ComponentAttributes([
            'class' => 'foo',
        ], new EscaperRuntime());

        $controllerDto = $this->createMock(AbstractStimulusDto::class);
        $controllerDto->expects(self::once())
            ->method('toArray')
            ->willReturn([
                'data-controller' => 'foo bar',
                'data-foo-name-value' => 'ryan',
            ]);

        $attributes = $attributes->add($controllerDto);

        $this->assertEquals([
            'class' => 'foo',
            'data-controller' => 'foo bar',
            'data-foo-name-value' => 'ryan',
        ], $attributes->all());
    }

    public function testCanAddStimulusControllerViaStimulusAttributes(): void
    {
        // if PHP less than 8.1, skip
        if (version_compare(\PHP_VERSION, '8.1.0', '<')) {
            $this->markTestSkipped('PHP 8.1+ required');
        }

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

    public function testCanAddStimulusActionViaStimulusAttributes(): void
    {
        // if PHP less than 8.1, skip
        if (version_compare(\PHP_VERSION, '8.1.0', '<')) {
            $this->markTestSkipped('PHP 8.1+ required');
        }

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

    public function testBooleanBehaviour(): void
    {
        $attributes = new ComponentAttributes(['disabled' => true], new EscaperRuntime());

        $this->assertSame(['disabled' => true], $attributes->all());
        $this->assertSame(' disabled', (string) $attributes);

        $attributes = new ComponentAttributes(['disabled' => false], new EscaperRuntime());

        $this->assertSame(['disabled' => false], $attributes->all());
        $this->assertSame('', (string) $attributes);
    }

    /**
     * @group legacy
     */
    public function testNullBehaviour(): void
    {
        $attributes = new ComponentAttributes(['disabled' => null], new EscaperRuntime());

        $this->assertSame(['disabled' => null], $attributes->all());
        $this->assertSame(' disabled', (string) $attributes);
    }

    public function testIsTraversableAndCountable(): void
    {
        $attributes = new ComponentAttributes(['foo' => 'bar'], new EscaperRuntime());

        $this->assertSame($attributes->all(), iterator_to_array($attributes));
        $this->assertCount(1, $attributes);
    }

    public function testRenderSingleAttribute(): void
    {
        $attributes = new ComponentAttributes(['attr1' => 'value1', 'attr2' => 'value2'], new EscaperRuntime());

        $this->assertSame('value1', $attributes->render('attr1'));
        $this->assertNull($attributes->render('attr3'));
    }

    public function testRenderingSingleAttributeExcludesFromString(): void
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

    public function testCannotRenderNonStringAttribute(): void
    {
        $attributes = new ComponentAttributes(['attr1' => false], new EscaperRuntime());

        $this->expectException(\LogicException::class);

        $attributes->render('attr1');
    }

    public function testCanCheckIfAttributeExists(): void
    {
        $attributes = new ComponentAttributes(['foo' => 'bar'], new EscaperRuntime());

        $this->assertTrue($attributes->has('foo'));
    }

    public function testNestedAttributes(): void
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

    public function testPrefixedAttributes(): void
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

    public function testConvertTrueAriaAttributeValue(): void
    {
        $attributes = new ComponentAttributes([
            'aria-bar' => false,
            'aria-foo' => true,
            'aria-true' => 'true',
            'aria-false' => 'false',
            'aria-foobar' => 'foobar',
            'aria-number' => '1',
        ], new EscaperRuntime());

        $this->assertStringNotContainsString('aria-bar', (string) $attributes);
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

        $this->expectException(\LogicException::class);
        $attributes->render('aria-bar');
    }

    /**
     * @dataProvider provideSpecialSyntaxAttributeNames
     */
    public function testAllowsSpecialSyntaxAttributeNames(string $name): void
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

    public function testThrowsTypeErrorWithoutEscaperRuntime(): void
    {
        $this->expectException(\TypeError::class);
        new ComponentAttributes([]);
    }

    /**
     * @dataProvider nameProvider
     */
    public function testEscapeName(string $input, string $expected): void
    {
        $runtime = new EscaperRuntime();
        $attributes = new ComponentAttributes([$input => 'foo'], $runtime);

        $this->assertSame(' '.$expected.'="foo"', (string) $attributes);
    }

    /**
     * @dataProvider valueProvider
     */
    public function testEscapeValue(string $input, string $expected): void
    {
        $runtime = new EscaperRuntime();
        $attributes = new ComponentAttributes(['foo' => $input], $runtime);

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
