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
use Symfony\UX\TwigComponent\Twig\ComponentNode;
use Twig\Compiler;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Node\Expression\ConstantExpression;

final class ComponentNodeTest extends TestCase
{
    /**
     * Twig 4 removed Environment::useYield() because yield stopped being optional. Probing for
     * that method to decide whether yield is available therefore reports "no" on Twig 4 and
     * selects the legacy echo/display path, which Twig 4 rejects with
     * 'Using "echo" is not supported; use "yield" instead' -- making every template that
     * contains a component fail to compile.
     */
    public function testCompilesToYieldWhenTheEnvironmentOnlySupportsYield()
    {
        if (Environment::MAJOR_VERSION < 4) {
            $this->markTestSkipped('Only Twig 4+ drops useYield() entirely.');
        }

        $source = self::compile();

        $this->assertStringContainsString('yield $preRendered;', $source);
        $this->assertStringNotContainsString('echo $preRendered;', $source);
        $this->assertStringContainsString('yield from ', $source);
        $this->assertStringNotContainsString('->display(', $source);
    }

    public function testCompilesToYieldWhenYieldIsEnabledOnTwig3()
    {
        if (Environment::MAJOR_VERSION >= 4) {
            $this->markTestSkipped('Twig 3 only: Twig 4 has no use_yield option to toggle.');
        }

        $source = self::compile(['use_yield' => true]);

        $this->assertStringContainsString('yield $preRendered;', $source);
        $this->assertStringContainsString('yield from ', $source);
        $this->assertStringNotContainsString('->display(', $source);
    }

    /**
     * The node is #[YieldReady], so it emits `yield $preRendered` even in legacy mode -- but the
     * template it embeds is still loaded through display(). Pinned so the Twig 4 fix cannot
     * quietly change what Twig 3 emits.
     */
    public function testKeepsTheLegacyDisplayPathWhenYieldIsDisabledOnTwig3()
    {
        if (Environment::MAJOR_VERSION >= 4) {
            $this->markTestSkipped('Twig 3 only: Twig 4 has no legacy path.');
        }

        $source = self::compile(['use_yield' => false]);

        $this->assertStringContainsString('->display(', $source);
        $this->assertStringNotContainsString('yield from ', $source);
    }

    private static function compile(array $options = []): string
    {
        $compiler = new Compiler(new Environment(new ArrayLoader([]), $options));

        $compiler->compile(new ComponentNode(
            new ConstantExpression('Alert', 1),
            'embedded_template',
            0,
            null,
            false,
            1,
        ));

        return $compiler->getSource();
    }
}
