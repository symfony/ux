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
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ComponentExtensionTest extends KernelTestCase
{
    public function testCanRenderComponent(): void
    {
        self::bootKernel();

        $output = self::$container->get(Environment::class)->render('template_a.html.twig');

        $this->assertStringContainsString('propA: prop a value', $output);
        $this->assertStringContainsString('propB: prop b value', $output);
        $this->assertStringContainsString('service: service a value', $output);
    }

    public function testCanRenderTheSameComponentMultipleTimes(): void
    {
        self::bootKernel();

        $output = self::$container->get(Environment::class)->render('template_b.html.twig');

        $this->assertStringContainsString('propA: prop a value 1', $output);
        $this->assertStringContainsString('propB: prop b value 1', $output);
        $this->assertStringContainsString('propA: prop a value 2', $output);
        $this->assertStringContainsString('propB: prop b value 2', $output);
        $this->assertStringContainsString('service: service a value', $output);
    }

    public function testCanCustomizeTemplateWithAttribute(): void
    {
        self::bootKernel();

        $output = self::$container->get(Environment::class)->render('template_b.html.twig');

        $this->assertStringContainsString('Custom template 1', $output);
    }

    public function testCanCustomizeTemplateWithServiceTag(): void
    {
        self::bootKernel();

        $output = self::$container->get(Environment::class)->render('template_c.html.twig');

        $this->assertStringContainsString('Custom template 2', $output);
    }
}
