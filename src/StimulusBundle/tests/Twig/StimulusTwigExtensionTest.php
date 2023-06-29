<?php

declare(strict_types=1);

/*
 * This file is part of the Symfony StimulusBundle package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\StimulusBundle\Tests\Twig;

use PHPUnit\Framework\TestCase;
use Symfony\UX\StimulusBundle\Helper\StimulusHelper;
use Symfony\UX\StimulusBundle\Tests\StimulusIntegrationTestKernel;
use Symfony\UX\StimulusBundle\Twig\StimulusTwigExtension;
use Twig\Environment;

final class StimulusTwigExtensionTest extends TestCase
{
    private Environment $twig;

    protected function setUp(): void
    {
        $kernel = new StimulusIntegrationTestKernel();
        $kernel->boot();
        $container = $kernel->getContainer()->get('test.service_container');
        $this->twig = $container->get(Environment::class);
    }

    /**
     * @dataProvider provideRenderStimulusController
     */
    public function testRenderStimulusController(string $controllerName, array $controllerValues, array $controllerClasses, array $controllerOutlets, string $expectedString, array $expectedArray): void
    {
        $extension = new StimulusTwigExtension(new StimulusHelper($this->twig));
        $dto = $extension->renderStimulusController($controllerName, $controllerValues, $controllerClasses, $controllerOutlets);
        $this->assertSame($expectedString, (string) $dto);
        $this->assertSame($expectedArray, $dto->toArray());
    }

    public static function provideRenderStimulusController(): iterable
    {
        yield 'normalize-names' => [
            'controllerName' => '@symfony/ux-dropzone/dropzone',
            'controllerValues' => [
                'my"Key"' => true,
            ],
            'controllerClasses' => [
                'second"Key"' => 'loading',
            ],
            'controllerOutlets' => [
                'other' => '.test',
            ],
            'expectedString' => 'data-controller="symfony--ux-dropzone--dropzone" data-symfony--ux-dropzone--dropzone-my-key-value="true" data-symfony--ux-dropzone--dropzone-second-key-class="loading" data-symfony--ux-dropzone--dropzone-other-outlet=".test"',
            'expectedArray' => ['data-controller' => 'symfony--ux-dropzone--dropzone', 'data-symfony--ux-dropzone--dropzone-my-key-value' => 'true', 'data-symfony--ux-dropzone--dropzone-second-key-class' => 'loading', 'data-symfony--ux-dropzone--dropzone-other-outlet' => '.test'],
        ];

        yield 'short-single-controller-no-data' => [
            'controllerName' => 'my-controller',
            'controllerValues' => [],
            'controllerClasses' => [],
            'controllerOutlets' => [],
            'expectedString' => 'data-controller="my-controller"',
            'expectedArray' => ['data-controller' => 'my-controller'],
        ];

        yield 'short-single-controller-with-data' => [
            'controllerName' => 'my-controller',
            'controllerValues' => ['myValue' => 'scalar-value'],
            'controllerClasses' => [],
            'controllerOutlets' => [],
            'expectedString' => 'data-controller="my-controller" data-my-controller-my-value-value="scalar-value"',
            'expectedArray' => ['data-controller' => 'my-controller', 'data-my-controller-my-value-value' => 'scalar-value'],
        ];

        yield 'false-attribute-value-renders-false' => [
            'controllerName' => 'false-controller',
            'controllerValues' => ['isEnabled' => false],
            'controllerClasses' => [],
            'controllerOutlets' => [],
            'expectedString' => 'data-controller="false-controller" data-false-controller-is-enabled-value="false"',
            'expectedArray' => ['data-controller' => 'false-controller', 'data-false-controller-is-enabled-value' => 'false'],
        ];

        yield 'true-attribute-value-renders-true' => [
            'controllerName' => 'true-controller',
            'controllerValues' => ['isEnabled' => true],
            'controllerClasses' => [],
            'controllerOutlets' => [],
            'expectedString' => 'data-controller="true-controller" data-true-controller-is-enabled-value="true"',
            'expectedArray' => ['data-controller' => 'true-controller', 'data-true-controller-is-enabled-value' => 'true'],
        ];

        yield 'null-attribute-value-does-not-render' => [
            'controllerName' => 'null-controller',
            'controllerValues' => ['firstName' => null],
            'controllerClasses' => [],
            'controllerOutlets' => [],
            'expectedString' => 'data-controller="null-controller"',
            'expectedArray' => ['data-controller' => 'null-controller'],
        ];

        yield 'short-single-controller-no-data-with-class' => [
            'controllerName' => 'my-controller',
            'controllerValues' => [],
            'controllerClasses' => ['loading' => 'spinner'],
            'controllerOutlets' => [],
            'expectedString' => 'data-controller="my-controller" data-my-controller-loading-class="spinner"',
            'expectedArray' => ['data-controller' => 'my-controller', 'data-my-controller-loading-class' => 'spinner'],
        ];

        yield 'short-single-controller-no-data-with-outlet' => [
            'controllerName' => 'my-controller',
            'controllerValues' => [],
            'controllerClasses' => [],
            'controllerOutlets' => ['other-controller' => '.target'],
            'expectedString' => 'data-controller="my-controller" data-my-controller-other-controller-outlet=".target"',
            'expectedArray' => ['data-controller' => 'my-controller', 'data-my-controller-other-controller-outlet' => '.target'],
        ];
    }

    public function testAppendStimulusController(): void
    {
        $extension = new StimulusTwigExtension(new StimulusHelper($this->twig));
        $dto = $extension->renderStimulusController('my-controller', ['myValue' => 'scalar-value']);
        $this->assertSame(
            'data-controller="my-controller another-controller" data-my-controller-my-value-value="scalar-value" data-another-controller-another-value-value="scalar-value&#x20;2"',
            (string) $extension->appendStimulusController($dto, 'another-controller', ['another-value' => 'scalar-value 2']),
        );
    }

    /**
     * @dataProvider provideRenderStimulusAction
     */
    public function testRenderStimulusAction(string $controllerName, ?string $actionName, ?string $eventName, array $parameters, string $expectedString, array $expectedArray): void
    {
        $extension = new StimulusTwigExtension(new StimulusHelper($this->twig));
        $dto = $extension->renderStimulusAction($controllerName, $actionName, $eventName, $parameters);
        $this->assertSame($expectedString, (string) $dto);
        $this->assertSame($expectedArray, $dto->toArray());
    }

    public static function provideRenderStimulusAction(): iterable
    {
        yield 'with default event' => [
            'controllerName' => 'my-controller',
            'actionName' => 'onClick',
            'eventName' => null,
            'parameters' => [],
            'expectedString' => 'data-action="my-controller#onClick"',
            'expectedArray' => ['data-action' => 'my-controller#onClick'],
        ];

        yield 'with custom event' => [
            'controllerName' => 'my-controller',
            'actionName' => 'onClick',
            'eventName' => 'click',
            'parameters' => [],
            'expectedString' => 'data-action="click->my-controller#onClick"',
            'expectedArray' => ['data-action' => 'click->my-controller#onClick'],
        ];

        yield 'with parameters' => [
            'controllerName' => 'my-controller',
            'actionName' => 'onClick',
            'eventName' => null,
            'parameters' => ['bool-param' => true, 'int-param' => 4, 'string-param' => 'test'],
            'expectedString' => 'data-action="my-controller#onClick" data-my-controller-bool-param-param="true" data-my-controller-int-param-param="4" data-my-controller-string-param-param="test"',
            'expectedArray' => ['data-action' => 'my-controller#onClick', 'data-my-controller-bool-param-param' => 'true', 'data-my-controller-int-param-param' => '4', 'data-my-controller-string-param-param' => 'test'],
        ];

        yield 'normalize-name, with default event' => [
            'controllerName' => '@symfony/ux-dropzone/dropzone',
            'actionName' => 'onClick',
            'eventName' => null,
            'parameters' => [],
            'expectedString' => 'data-action="symfony--ux-dropzone--dropzone#onClick"',
            'expectedArray' => ['data-action' => 'symfony--ux-dropzone--dropzone#onClick'],
        ];

        yield 'normalize-name, with custom event' => [
            'controllerName' => '@symfony/ux-dropzone/dropzone',
            'actionName' => 'onClick',
            'eventName' => 'click',
            'parameters' => [],
            'expectedString' => 'data-action="click->symfony--ux-dropzone--dropzone#onClick"',
            'expectedArray' => ['data-action' => 'click->symfony--ux-dropzone--dropzone#onClick'],
        ];
    }

    public function testAppendStimulusAction(): void
    {
        $extension = new StimulusTwigExtension(new StimulusHelper($this->twig));
        $dto = $extension->renderStimulusAction('my-controller', 'onClick', 'click');
        $this->assertSame(
            'data-action="click->my-controller#onClick change->my-second-controller#onSomethingElse"',
            (string) $extension->appendStimulusAction($dto, 'my-second-controller', 'onSomethingElse', 'change')
        );
    }

    /**
     * @dataProvider provideRenderStimulusTarget
     */
    public function testRenderStimulusTarget(string $controllerName, ?string $targetName, string $expectedString, array $expectedArray)
    {
        $extension = new StimulusTwigExtension(new StimulusHelper($this->twig));
        $dto = $extension->renderStimulusTarget($controllerName, $targetName);
        $this->assertSame($expectedString, (string) $dto);
        $this->assertSame($expectedArray, $dto->toArray());
    }

    public static function provideRenderStimulusTarget(): iterable
    {
        yield 'simple' => [
            'controllerName' => 'my-controller',
            'targetName' => 'myTarget',
            'expectedString' => 'data-my-controller-target="myTarget"',
            'expectedArray' => ['data-my-controller-target' => 'myTarget'],
        ];

        yield 'normalize-name' => [
            'controllerName' => '@symfony/ux-dropzone/dropzone',
            'targetName' => 'myTarget',
            'expectedString' => 'data-symfony--ux-dropzone--dropzone-target="myTarget"',
            'expectedArray' => ['data-symfony--ux-dropzone--dropzone-target' => 'myTarget'],
        ];
    }

    public function testAppendStimulusTarget(): void
    {
        $extension = new StimulusTwigExtension(new StimulusHelper($this->twig));
        $dto = $extension->renderStimulusTarget('my-controller', 'myTarget');
        $this->assertSame(
            'data-my-controller-target="myTarget" data-symfony--ux-dropzone--dropzone-target="anotherTarget fooTarget"',
            (string) $extension->appendStimulusTarget($dto, '@symfony/ux-dropzone/dropzone', 'anotherTarget fooTarget')
        );
    }
}
