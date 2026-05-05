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
use Twig\Error\RuntimeError;

final class ProvideInjectTest extends KernelTestCase
{
    public function testSlotInjectsMaxLengthFromInputOtpRoot()
    {
        $output = self::getContainer()->get(Environment::class)->render('provide_inject_direct_parent.html.twig');

        $this->assertStringContainsString('name="otp[0]" maxlength="1" data-index="1" data-max-length="6"', $output);
    }

    public function testSlotInjectsAcrossNonProvidingGroup()
    {
        $output = self::getContainer()->get(Environment::class)->render('provide_inject_across_group.html.twig');

        $this->assertStringContainsString('name="code[0]" maxlength="1" data-index="1" data-max-length="6"', $output);
        $this->assertStringContainsString('name="code[1]" maxlength="1" data-index="2" data-max-length="6"', $output);
        $this->assertStringContainsString('name="code[2]" maxlength="1" data-index="3" data-max-length="6"', $output);
    }

    public function testNearestAncestorWins()
    {
        $output = self::getContainer()->get(Environment::class)->render('provide_inject_nearest_wins.html.twig');

        $this->assertStringContainsString('aria-selected="true"', $output);
        $this->assertStringNotContainsString('aria-selected="false"', $output);
    }

    public function testInjectReturnsDefaultWhenMissing()
    {
        $output = self::getContainer()->get(Environment::class)->render('provide_inject_default.html.twig');

        $this->assertStringContainsString('name="otp[0]" maxlength="1" data-index="1" data-max-length="4"', $output);
    }

    public function testProvideOutsideComponentThrows()
    {
        try {
            self::getContainer()->get(Environment::class)->render('provide_inject_outside_component.html.twig');
            $this->fail('Expected RuntimeError to be thrown.');
        } catch (RuntimeError $e) {
            $this->assertStringContainsString('The "provide()" Twig function cannot be called outside of a component template, "foo" key was being provided.', $e->getMessage());
            $this->assertInstanceOf(\LogicException::class, $e->getPrevious());
        }
    }

    public function testInjectOutsideComponentReturnsDefault()
    {
        $output = self::getContainer()->get(Environment::class)->render('provide_inject_inject_outside_component.html.twig');

        $this->assertStringContainsString('fallback=fallback-value', $output);
    }

    public function testProvideNullIsDistinctFromMissing()
    {
        $output = self::getContainer()->get(Environment::class)->render('provide_inject_null_distinct.html.twig');

        // provide('inputOtp.name', null) shadows the default 'otp', so the slot renders name="[0]".
        $this->assertStringContainsString('name="[0]" maxlength="1" data-index="1" data-max-length="4"', $output);
    }

    public function testProvideOverwritesPreviousValueInSameComponent()
    {
        $output = self::getContainer()->get(Environment::class)->render('provide_inject_overwrite.html.twig');

        // OverwritingTabs calls provide('tabs.active', 'first') then provide('tabs.active', 'second').
        // Last call wins, so only the trigger with value="second" is selected.
        $this->assertSame(1, substr_count($output, 'data-value="second" aria-selected="true"'));
        $this->assertSame(1, substr_count($output, 'data-value="first" aria-selected="false"'));
    }

    public function testGrandchildInjectsThroughIntermediateComponent()
    {
        $output = self::getContainer()->get(Environment::class)->render('provide_inject_deep_nesting.html.twig');

        $this->assertStringContainsString('name="deep[0]" maxlength="1" data-index="1" data-max-length="9"', $output);
    }

    public function testProvideAcceptsComplexValues()
    {
        $output = self::getContainer()->get(Environment::class)->render('provide_inject_complex_values.html.twig');

        $this->assertStringContainsString('list:a+b+c;config:value/42', $output);
    }

    public function testInjectInsidePassedContentSkipsWrappingComponent()
    {
        $output = self::getContainer()->get(Environment::class)->render('provide_inject_passed_content_skips_self.html.twig');

        // Tabs is on the stack but considered "self" while its content block renders,
        // so inject() skips it and falls back to the default.
        $this->assertStringContainsString('inline:NONE', $output);
        $this->assertStringNotContainsString('inline:A', $output);
    }

    public function testSameComponentRenderedTwiceDoesNotLeakProvidedValues()
    {
        $first = self::getContainer()->get(Environment::class)->render('provide_inject_render_first.html.twig');
        $second = self::getContainer()->get(Environment::class)->render('provide_inject_render_second.html.twig');

        $this->assertStringContainsString('name="first-render[0]" maxlength="1" data-index="1" data-max-length="6"', $first);
        $this->assertStringContainsString('name="second-render[0]" maxlength="1" data-index="1" data-max-length="2"', $second);
        $this->assertStringNotContainsString('first-render', $second);
        $this->assertStringNotContainsString('data-max-length="6"', $second);
    }

    public function testDeepDescendantCannotReachSiblingTreeContext()
    {
        $output = self::getContainer()->get(Environment::class)->render('provide_inject_cross_tree.html.twig');

        // Branch 1's slot reaches its own ancestor InputOtp.
        $this->assertStringContainsString('name="branch1[0]" maxlength="1" data-index="1" data-max-length="6"', $output);
        // Branch 2's deep slot has no InputOtp ancestor, so it falls back to defaults
        // even though branch 1 (already finished and popped) provided those keys.
        $this->assertStringContainsString('name="otp[0]" maxlength="1" data-index="1" data-max-length="4"', $output);
    }

    public function testSiblingComponentsDoNotLeakContext()
    {
        $output = self::getContainer()->get(Environment::class)->render('provide_inject_siblings.html.twig');

        // First sibling: maxLength=6, name=first.
        $this->assertStringContainsString('name="first[0]" maxlength="1" data-index="1" data-max-length="6"', $output);
        // Second sibling: maxLength=4, name=second.
        $this->assertStringContainsString('name="second[0]" maxlength="1" data-index="1" data-max-length="4"', $output);
        // Standalone slot: falls back to defaults (maxLength=4, name=otp).
        $this->assertStringContainsString('name="otp[0]" maxlength="1" data-index="1" data-max-length="4"', $output);
    }
}
