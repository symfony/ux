<?php

declare(strict_types=1);

/*
 * This file is part of the Symfony StimulusBundle package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\StimulusBundle\Tests\Dto;

use PHPUnit\Framework\TestCase;
use Symfony\UX\StimulusBundle\Dto\StimulusAttributes;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

final class StimulusAttributesTest extends TestCase
{
    private StimulusAttributes $stimulusAttributes;

    protected function setUp(): void
    {
        $this->stimulusAttributes = new StimulusAttributes(new Environment(new ArrayLoader()));
    }

    public function testAddAction(): void
    {
        $this->stimulusAttributes->addAction('foo', 'bar', 'baz', ['qux' => '"']);
        $attributesHtml = (string) $this->stimulusAttributes;
        self::assertSame('data-action="baz->foo#bar" data-foo-qux-param="&quot;"', $attributesHtml);
    }

    public function testAddActionToArrayNoEscapingAttributeValues(): void
    {
        $this->stimulusAttributes->addAction('foo', 'bar', 'baz', ['qux' => '"']);
        $attributesArray = $this->stimulusAttributes->toArray();
        self::assertSame(['data-action' => 'baz->foo#bar', 'data-foo-qux-param' => '"'], $attributesArray);
    }

    public function testAddActionWithMultiple(): void
    {
        $this->stimulusAttributes->addAction('my-controller', 'onClick');
        $this->assertSame('data-action="my-controller#onClick"', (string) $this->stimulusAttributes);
        $this->assertSame(['data-action' => 'my-controller#onClick'], $this->stimulusAttributes->toArray());

        $this->stimulusAttributes->addAction('second-controller', 'onClick', 'click');
        $this->assertSame(
            'data-action="my-controller#onClick click->second-controller#onClick"',
            (string) $this->stimulusAttributes,
        );
    }

    public function testAddControllerToStringEscapingAttributeValues(): void
    {
        $this->stimulusAttributes->addController('foo', ['bar' => '"'], ['baz' => '"']);
        $attributesHtml = (string) $this->stimulusAttributes;
        self::assertSame(
            'data-controller="foo" '.
            'data-foo-bar-value="&quot;" '.
            'data-foo-baz-class="&quot;"',
            $attributesHtml
        );
    }

    public function testAddControllerToArrayNoEscapingAttributeValues(): void
    {
        $this->stimulusAttributes->addController('foo', ['bar' => '"'], ['baz' => '"']);
        $attributesArray = $this->stimulusAttributes->toArray();
        self::assertSame(
            [
                'data-controller' => 'foo',
                'data-foo-bar-value' => '"',
                'data-foo-baz-class' => '"',
            ],
            $attributesArray
        );
    }

    public function testAddControllerNormalizesControllerName()
    {
        $this->stimulusAttributes->addController('@symfony/ux-dropzone/dropzone',
            ['my"Key"' => true],
            ['second"Key"' => 'loading']
        );

        $this->assertSame(
            'data-controller="symfony--ux-dropzone--dropzone" data-symfony--ux-dropzone--dropzone-my-key-value="true" data-symfony--ux-dropzone--dropzone-second-key-class="loading"',
            (string) $this->stimulusAttributes,
        );
        $this->assertSame(
            ['data-controller' => 'symfony--ux-dropzone--dropzone', 'data-symfony--ux-dropzone--dropzone-my-key-value' => 'true', 'data-symfony--ux-dropzone--dropzone-second-key-class' => 'loading'],
            $this->stimulusAttributes->toArray(),
        );

        $this->stimulusAttributes->addController('my-controller', ['myValue' => 'scalar-value']);
        $this->assertSame(
            'data-controller="symfony--ux-dropzone--dropzone my-controller" data-symfony--ux-dropzone--dropzone-my-key-value="true" data-symfony--ux-dropzone--dropzone-second-key-class="loading" data-my-controller-my-value-value="scalar-value"',
            (string) $this->stimulusAttributes,
        );
    }

    public function testAddTargetToStringEscapingAttributeValues(): void
    {
        $this->stimulusAttributes->addTarget('foo', '"');
        $attributesHtml = (string) $this->stimulusAttributes;
        self::assertSame('data-foo-target="&quot;"', $attributesHtml);
    }

    public function testAddTargetToArrayNoEscapingAttributeValues(): void
    {
        $this->stimulusAttributes->addTarget('foo', '"');
        $attributesArray = $this->stimulusAttributes->toArray();
        self::assertSame(['data-foo-target' => '"'], $attributesArray);
    }

    public function testAddTargetWithMultiple(): void
    {
        $this->stimulusAttributes->addTarget('my-controller', 'myTarget');
        $this->assertSame('data-my-controller-target="myTarget"', (string) $this->stimulusAttributes);
        $this->assertSame(['data-my-controller-target' => 'myTarget'], $this->stimulusAttributes->toArray());

        $this->stimulusAttributes->addTarget('second-controller', 'secondTarget');
        $this->assertSame(
            'data-my-controller-target="myTarget" data-second-controller-target="secondTarget"',
            (string) $this->stimulusAttributes,
        );
    }

    public function testAddMultipleTargetsAtOnce()
    {
        $this->stimulusAttributes->addTarget('my-controller', 'myTarget myOtherTarget');
        $this->assertSame('data-my-controller-target="myTarget myOtherTarget"', (string) $this->stimulusAttributes);
        $this->assertSame(['data-my-controller-target' => 'myTarget myOtherTarget'], $this->stimulusAttributes->toArray());
    }

    public function testIsTraversable()
    {
        $this->stimulusAttributes->addController('foo', ['bar' => 'baz']);
        $actualAttributes = iterator_to_array($this->stimulusAttributes);
        self::assertSame(['data-controller' => 'foo', 'data-foo-bar-value' => 'baz'], $actualAttributes);
    }

    public function testAddAttribute()
    {
        $this->stimulusAttributes->addAttribute('foo', 'bar baz');
        $this->assertSame('foo="bar&#x20;baz"', (string) $this->stimulusAttributes);
        $this->assertSame(['foo' => 'bar baz'], $this->stimulusAttributes->toArray());
    }
}
