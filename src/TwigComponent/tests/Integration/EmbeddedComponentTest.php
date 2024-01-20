<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Twig\Environment;

/**
 * @author Bart Vanderstukken <bart.vanderstukken@gmail.com>
 */
final class EmbeddedComponentTest extends KernelTestCase
{
    /**
     * Rule 1: A block is not passed into an embedded component, since it would be rendered in place ànd in the component's template.
     */
    public function testABlockIsNotPassedIntoAnEmbeddedComponent(): void
    {
        $output = self::getContainer()->get(Environment::class)->render('embedded_component_blocks_basic.html.twig');

        // rule 1
        $this->assertStringContainsStringIgnoringIndentation('<div id="rendered-in-place">This block is rendered in place, since there\'s no extend happening</div>', $output);
        $this->assertStringNotContainsString('<div class="divComponent">Hello Fabien!This block is rendered in place, since there\'s no extend happening</div>', $output);
    }

    /**
     * Rule 2: An embedded component has access to the (outer) context.
     */
    public function testAnEmbeddedComponentHasContextAccess(): void
    {
        $this->assertStringContainsStringIgnoringIndentation(
            '<div class="divComponent">Hello Fabien!',
            self::getContainer()->get(Environment::class)->render('embedded_component_blocks_basic.html.twig')
        );
    }

    /**
     * Rule 3: A block is only passed one level down, via the display() function on the embedded Template that's representing a component instance.
     */
    public function testABlockIsOnlyPassedOneLevelDown(): void
    {
        $output = self::getContainer()->get(Environment::class)->render('embedded_component_blocks_no_pass.html.twig');

        $this->assertStringContainsStringIgnoringIndentation('<div class="divComponent">The Generic Element could have some default content, although it does not make sense in this example.<span class="foo">The Generic Element default foo block</span></div>', $output);
        $this->assertStringNotContainsString('Hello world!', $output);
        $this->assertStringNotContainsString('Foo content is not used', $output);
    }

    /**
     * Rule 4: Inside that component's template you can use it, but NOT within a nested component. The latter is repeating rule 1.
     */
    public function testABlockIsNotPassedToNestedComponents(): void
    {
        $this->assertStringContainsStringIgnoringIndentation(
            'Hello world!<div class="divComponent"><span class="foo">The Generic Element default foo block</span></div>',
            self::getContainer()->get(Environment::class)->render('embedded_component_blocks_example1.html.twig')
        );
    }

    /**
     * Rule 5: If you want to use that block inside a component's template within a nested component,
     *         then you can do so via the outerBlocks variable. For example: "block(outerBlocks.content)"
     * Rule 6: You can use it multiple times, just like any other "block()" call
     * Rule 7: If you want to pass that outer block along to the template of that nested component,
     *         You can use it inside the block definition with the embedded component.
     */
    public function testBlockCanBeUsedWithinNestedViaTheOuterBlocks(): void
    {
        $this->assertStringContainsStringIgnoringIndentation(
            'Hello world!<div class="divComponent">Hello world!<span class="foo">Override foo & Override foo</span></div>',
            self::getContainer()->get(Environment::class)->render('embedded_component_blocks_outer_blocks.html.twig')
        );
    }

    /**
     * Rule 5 bis: A block inside an extending template can be use inside a component in that template and is NOT rendered in the original location.
     */
    public function testBlockCanBeUsedViaTheOuterBlocks(): void
    {
        $output = self::getContainer()->get(Environment::class)->render('embedded_component_blocks_outer_blocks_extended_template.html.twig');

        $this->assertStringContainsStringIgnoringIndentation('<div>Hello world!</div>', $output);
        $this->assertStringNotContainsString("Hello world!\n<div", $output);
    }

    /**
     * Rule 8: Defining a block for a component overrides any default content that block has in the component's template.
     *         This also means that when passing block down that you will lose that default content.
     *         That can be avoided by using {{ parent() }} like you normally would.
     */
    public function testBlockDefinitionsPassingDownOuterBlocksOverrideDefaultContent(): void
    {
        $this->assertStringContainsStringIgnoringIndentation(
            '<div class="divComponent">Hello world!<span class="foo">The Generic Element default foo block + Override foo</span></div>',
            self::getContainer()->get(Environment::class)->render('embedded_component_blocks_outer_blocks_parent.html.twig')
        );
    }

    /**
     * Rule 9: Passing blocks also works with nesting a component inside another instance of the same component.
     */
    public function testDeepNesting(): void
    {
        $this->assertStringContainsStringIgnoringIndentation(
            '<div class="divComponent">Content 1<div class="divComponent">Content 2<div class="divComponent">Content 3<span class="foo">Override foo3</span></div><span class="foo">Override foo2</span></div><span class="foo">Override foo1</span></div>',
            self::getContainer()->get(Environment::class)->render('embedded_component_blocks_complex_nesting.html.twig')
        );
    }

    /**
     * Rule 10: Missing outer blocks use a fallback block so that nothing is rendered, and no unknown block error occurs.
     */
    public function testItCanHandleMissingOuterBlocks(): void
    {
        $this->assertStringContainsStringIgnoringIndentation(
            '<div class="divComponent">Content 1<div class="divComponent">Content 2<div class="divComponent">Content 3<span class="foo">Override foo3</span></div><span class="foo"></span></div><span class="foo">Override foo1</span></div>',
            self::getContainer()->get(Environment::class)->render('embedded_component_blocks_complex_nesting2.html.twig'),
        );
    }

    /**
     * Rule 11: To pass blocks down multiple levels, each level needs to define the block,
     *          so it can be used for its parent block (of the nested component).
     *          Not defining a block (and not passing said block along) will be considered as a missing block (see rule 10).
     */
    public function testPassingDownBlocksMultipleLevelsNeedsToBeDoneManually(): void
    {
        $this->assertStringContainsStringIgnoringIndentation(
            '<div class="divComponent">DIV CONTENT: WRAPPER CONTENT: Content from wrapper<span class="foo">I\'m fixing foo content</span></div><div class="divComponent">DIV CONTENT: WRAPPER CONTENT: Content from wrapperI don\'t have a foo block, so it will be considered empty.<span class="foo"></span></div>',
            self::getContainer()->get(Environment::class)->render('embedded_component_blocks_complex_nesting_deep.html.twig')
        );
    }

    /**
     * Rule 12: Blocks defined within an embedded component can access the context of the block they are replacing.
     * Rule 13: Blocks defined within an embedded component can access the context of components up the hierarchy (up to their own level) via "outerScope".
     */
    public function testBlockDefinitionCanAccessTheContextOfTheDestinationBlocks(): void
    {
        $this->assertStringContainsStringIgnoringIndentation(
            '<div class="divComponent">I can access my own properties: foo.I can access the id of the Generic Element: symfonyIsAwesome.This refers to the Generic Element: calling GenericElement.To access my own functions I can use outerScope.this: calling DivComponent.I have access to outer context variables like Fabien.I can access the id from Generic Element as well: symfonyIsAwesome.I can access the properties from DivComponent as well: foo.And of course the properties from DivComponentWrapper: bar.The less obvious thing is that at this level "this" refers to the component where the content block is used, i.e. the Generic Element.Therefore, functions through this will be calling GenericElement.Calls to outerScope.this will be calling DivComponent.Even I can access the id from Generic Element as well: symfonyIsAwesome.Even I can access the properties from DivComponent as well: foo.Even I can access the properties from DivComponentWrapper as well: bar.Even I can access the functions of DivComponent via outerScope.this: calling DivComponent.Since we are nesting two levels deep, calls to outerScope.outerScope.this will be calling DivComponentWrapper.<span class="foo">The Generic Element default foo block</span></div>',
            self::getContainer()->get(Environment::class)->render('embedded_component_blocks_context.html.twig')
        );
    }

    public function testAccessingTheHierarchyTooHighThrowsAnException(): void
    {
        $this->expectExceptionMessage('Key "this" for array with keys "app, __embedded" does not exist.');
        self::getContainer()->get(Environment::class)->render('embedded_component_hierarchy_exception.html.twig');
    }

    public function testANonEmbeddedComponentRendersOuterBlocksEmpty(): void
    {
        $this->assertStringContainsStringIgnoringIndentation(
            '<div class="divComponent"><span class="foo"></span></div>',
            self::getContainer()->get(Environment::class)->render('non_embedded_component_blocks.html.twig'),
        );
    }

    public function testANonEmbeddedComponentCanRenderParentBlocksAsFallback(): void
    {
        $this->assertStringContainsStringIgnoringIndentation(
            '<div class="divComponent">The Generic Element could have some default content, although it does not make sense in this example.<span class="foo">The Generic Element default foo block</span></div>',
            self::getContainer()->get(Environment::class)->render('non_embedded_component_blocks_with_fallback.html.twig'),
        );
    }

    private function assertStringContainsStringIgnoringIndentation(string $needle, string $haystack): void
    {
        $needle = trim(preg_replace('#(\s+)#u', '', $needle));
        $haystack = trim(preg_replace('#(\s+)#u', '', $haystack));
        $this->assertStringContainsString(trim($needle), trim($haystack));
    }
}
