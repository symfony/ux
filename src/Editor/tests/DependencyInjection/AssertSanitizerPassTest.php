<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\UX\Editor\DependencyInjection\Compiler\AssertSanitizerPass;

final class AssertSanitizerPassTest extends TestCase
{
    public function testThrowsWhenRequiredButMissing(): void
    {
        $c = new ContainerBuilder();
        $c->setParameter('ux_editor.html.sanitize_required', true);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/html_sanitizer.sanitizer.default/');
        (new AssertSanitizerPass())->process($c);
    }

    public function testPassesWhenSanitizerRegistered(): void
    {
        $c = new ContainerBuilder();
        $c->setParameter('ux_editor.html.sanitize_required', true);
        $c->setDefinition('html_sanitizer.sanitizer.default', new Definition(\stdClass::class));
        (new AssertSanitizerPass())->process($c);
        $this->addToAssertionCount(1);
    }

    public function testSilentWhenNotRequired(): void
    {
        $c = new ContainerBuilder();
        $c->setParameter('ux_editor.html.sanitize_required', false);
        (new AssertSanitizerPass())->process($c);
        $this->addToAssertionCount(1);
    }
}
