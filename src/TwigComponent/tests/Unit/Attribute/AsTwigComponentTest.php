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
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\PreMount;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class AsTwigComponentTest extends TestCase
{
    public function testPreMountHooksAreOrderedByPriority(): void
    {
        $hooks = AsTwigComponent::preMountMethods(
            new class() {
                #[PreMount(priority: -10)]
                public function hook1()
                {
                }

                #[PreMount(priority: 10)]
                public function hook2()
                {
                }

                #[PreMount]
                public function hook3()
                {
                }
            }
        );

        $this->assertCount(3, $hooks);
        $this->assertSame('hook2', $hooks[0]->name);
        $this->assertSame('hook3', $hooks[1]->name);
        $this->assertSame('hook1', $hooks[2]->name);
    }
}
