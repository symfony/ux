<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Css\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\UX\Css\Tests\Fixtures\Dtcg;
use Symfony\UX\Css\Tests\Fixtures\TestKernel;
use Symfony\UX\DesignTokens\TokenRegistryInterface;

final class DesignTokensKernelTest extends KernelTestCase
{
    public function testTheTestKernelServesItsDesignTokens(): void
    {
        self::bootKernel();
        $registry = self::getContainer()->get(TokenRegistryInterface::class);

        $spacing = (string) $registry->get('dimension.spacing.md');

        $this->assertSame('1rem', $spacing);
    }

    protected static function createKernel(array $options = []): KernelInterface
    {
        $tokens = ['dimension' => ['spacing' => ['md' => Dtcg::dimension(1)]]];

        return new TestKernel(designTokens: $tokens);
    }
}
