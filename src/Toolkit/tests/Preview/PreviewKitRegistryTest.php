<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Tests\Preview;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\UX\Toolkit\Kit\KitFactory;
use Symfony\UX\Toolkit\Kit\KitSynchronizer;
use Symfony\UX\Toolkit\Preview\PreviewKitRegistry;
use Symfony\UX\Toolkit\Recipe\RecipeSynchronizer;
use Symfony\UX\Toolkit\Tests\TestHelperTrait;

final class PreviewKitRegistryTest extends TestCase
{
    use TestHelperTrait;

    public function testLoadsOnlyTheRequestedKit(): void
    {
        $registry = $this->createRegistry(['preview' => self::getFixtureKitPath('preview'), 'broken' => '/does/not/exist']);

        $kit = $registry->getKit('preview');

        $this->assertSame(self::getFixtureKitPath('preview'), $kit->absolutePath);
    }

    public function testReturnsNullForAKitThatIsNotPreviewed(): void
    {
        $registry = $this->createRegistry(['preview' => self::getFixtureKitPath('preview')]);

        $this->assertNull($registry->getKit('shadcn'));
    }

    public function testListsTheKitsByName(): void
    {
        $registry = $this->createRegistry(['preview' => self::getFixtureKitPath('preview'), 'plain' => self::getFixtureKitPath('preview-plain')]);

        $this->assertSame(['preview', 'plain'], array_keys($registry->getKits()));
    }

    /**
     * @param array<string, string> $kitDirs
     */
    private function createRegistry(array $kitDirs): PreviewKitRegistry
    {
        $filesystem = new Filesystem();

        return new PreviewKitRegistry(new KitFactory($filesystem, new KitSynchronizer($filesystem, new RecipeSynchronizer())), $kitDirs);
    }
}
