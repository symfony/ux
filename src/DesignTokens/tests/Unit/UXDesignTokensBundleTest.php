<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DesignTokens\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Symfony\UX\DesignTokens\UXDesignTokensBundle;

#[CoversClass(UXDesignTokensBundle::class)]
final class UXDesignTokensBundleTest extends TestCase
{
    public function testGetPathReturnsBundleRootDirectory(): void
    {
        $bundle = new UXDesignTokensBundle();
        $path = $bundle->getPath();

        self::assertDirectoryExists($path);
        self::assertDirectoryExists($path.'/src');
        self::assertFileExists($path.'/config/services.php');
    }

    public function testBundleExtendsAbstractBundle(): void
    {
        self::assertInstanceOf(AbstractBundle::class, new UXDesignTokensBundle());
    }

    public function testConfigurationAliasIsStable(): void
    {
        self::assertSame('ux_design_tokens', new UXDesignTokensBundle()->getContainerExtension()?->getAlias());
    }
}
