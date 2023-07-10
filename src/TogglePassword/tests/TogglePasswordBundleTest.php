<?php

declare(strict_types=1);

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\tests;

use PHPUnit\Framework\TestCase;
use Symfony\UX\TogglePassword\Tests\Kernel\TwigAppKernel;

/**
 * @author Félix Eymonot <felix.eymonot@alximy.io>
 *
 * @internal
 */
class TogglePasswordBundleTest extends TestCase
{
    public function testBootKernel(): void
    {
        $kernel = new TwigAppKernel('test', true);
        $kernel->boot();
        $this->assertArrayHasKey('TogglePasswordBundle', $kernel->getBundles());
    }

    public function testFormThemeMerging(): void
    {
        $kernel = new TwigAppKernel('test', true);
        $kernel->boot();
        $this->assertEquals([
            'form_div_layout.html.twig',
            '@TogglePassword/form_theme.html.twig',
        ], $kernel->getContainer()->getParameter('twig.form.resources'));
    }
}
