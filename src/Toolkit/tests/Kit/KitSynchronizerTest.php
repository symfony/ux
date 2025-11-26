<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Tests\Kit;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\UX\Toolkit\Dependency\ConstraintVersion;
use Symfony\UX\Toolkit\Dependency\PhpPackageDependency;
use Symfony\UX\Toolkit\Kit\KitSynchronizer;
use Symfony\UX\Toolkit\Recipe\RecipeSynchronizer;
use Symfony\UX\Toolkit\Tests\TestHelperTrait;

final class KitSynchronizerTest extends KernelTestCase
{
    use TestHelperTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bootKernel();
    }

    public function testCanResolveDependencies()
    {
        $kitSynchronizer = new KitSynchronizer(new Filesystem(), new RecipeSynchronizer());
        $kit = self::createLocalKit('shadcn');

        $kitSynchronizer->synchronize($kit);

        $this->assertEquals([
            new PhpPackageDependency('twig/extra-bundle'),
            new PhpPackageDependency('twig/html-extra', new ConstraintVersion('^3.12.0')),
            new PhpPackageDependency('tales-from-a-dev/twig-tailwind-extra', new ConstraintVersion('^1.0.0')),
        ], $kit->getRecipe('button')->manifest->dependencies);
    }
}
