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
use Symfony\UX\TwigComponent\Attribute\PostMount;
use Symfony\UX\TwigComponent\Attribute\PreMount;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class AsTwigComponentTest extends TestCase
{
    /**
     * @dataProvider provideServiceConfigData
     */
    public function testServiceConfigValues(AsTwigComponent $attribute, array $expectedConfig): void
    {
        $this->assertSame($expectedConfig, $attribute->serviceConfig());
    }

    public static function provideServiceConfigData(): iterable
    {
        yield 'No values' => [
            new AsTwigComponent(),
            [
                'expose_public_props' => true,
                'attributes_var' => 'attributes',
            ],
        ];
        yield 'Default values' => [
             new AsTwigComponent(null, null, true, 'attributes'),
             [
                 'expose_public_props' => true,
                 'attributes_var' => 'attributes',
             ],
        ];
        yield 'Name and template set' => [
             new AsTwigComponent('foo', 'template'),
             [
                'key' => 'foo',
                'template' => 'template',
                 'expose_public_props' => true,
                 'attributes_var' => 'attributes',
             ],
        ];
    }

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

    public function testPostMountHooksAreOrderedByPriority(): void
    {
        $hooks = AsTwigComponent::postMountMethods(
            new class() {
                #[PostMount(priority: -10)]
                public function hook1()
                {
                }

                #[PostMount(priority: 10)]
                public function hook2()
                {
                }

                #[PostMount]
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
