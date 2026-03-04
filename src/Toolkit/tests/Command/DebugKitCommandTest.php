<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Tests\Command;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\Toolkit\Tests\TestHelperTrait;
use Zenstruck\Console\Test\InteractsWithConsole;

class DebugKitCommandTest extends KernelTestCase
{
    use InteractsWithConsole;
    use TestHelperTrait;

    public function testShouldBeAbleToDebugShadcnKit()
    {
        $this->bootKernel();
        $this->consoleCommand(\sprintf('ux:toolkit:debug-kit %s', self::getLocalKitPath('shadcn')))
            ->execute()
            ->assertSuccessful()
            // Kit details
            ->assertOutputContains('Name       Shadcn')
            ->assertOutputContains('Homepage   https://ux.symfony.com/toolkit/kits/shadcn')
            ->assertOutputContains('License    MIT')
            // Components details
            ->assertOutputContains(implode(\PHP_EOL, [
                '+--------------+------------------------ Recipe: "avatar" ----------------------------------------+',
                '| File(s)      | templates/components/Avatar.html.twig                                            |',
                '|              | templates/components/Avatar/Badge.html.twig                                      |',
                '|              | templates/components/Avatar/Fallback.html.twig                                   |',
                '|              | templates/components/Avatar/Group.html.twig                                      |',
                '|              | templates/components/Avatar/GroupCount.html.twig                                 |',
                '|              | templates/components/Avatar/Image.html.twig                                      |',
                '| Dependencies | tales-from-a-dev/twig-tailwind-extra:^1.0.0                                      |',
                '+--------------+----------------------------------------------------------------------------------+',
            ]))
            ->assertOutputContains(implode(\PHP_EOL, [
                '+--------------+------------------------- Recipe: "table" ----------------------------------------+',
                '| File(s)      | templates/components/Table.html.twig                                             |',
                '|              | templates/components/Table/Body.html.twig                                        |',
                '|              | templates/components/Table/Caption.html.twig                                     |',
                '|              | templates/components/Table/Cell.html.twig                                        |',
                '|              | templates/components/Table/Footer.html.twig                                      |',
                '|              | templates/components/Table/Head.html.twig                                        |',
                '|              | templates/components/Table/Header.html.twig                                      |',
                '|              | templates/components/Table/Row.html.twig                                         |',
                '| Dependencies | tales-from-a-dev/twig-tailwind-extra:^1.0.0                                      |',
                '+--------------+----------------------------------------------------------------------------------+',
            ]));
    }

    public function testShouldBeAbleToDebugFixtureKitWithManyDependencies()
    {
        $this->bootKernel();
        $this->consoleCommand(\sprintf('ux:toolkit:debug-kit %s', self::getFixtureKitPath('with-many-dependencies')))
            ->execute()
            ->assertSuccessful()
            // Kit details
            ->assertOutputContains('Name       With many dependencies')
            ->assertOutputContains('Homepage   https://ux.symfony.com')
            ->assertOutputContains('License    MIT')
            // Components details
            ->assertOutputContains(implode(\PHP_EOL, [
                '+--------------+------------------------- Recipe: "alert" ----------------------------------------+',
                '| File(s)      | N/A                                                                              |',
                '| Dependencies | button                                                                           |',
                '|              | twig/html-extra:^3.12.0                                                          |',
                '|              | tales-from-a-dev/twig-tailwind-extra:^1.0.0                                      |',
                '|              | tailwindcss:^4.0.0                                                               |',
                '|              | @tailwindplus/elements:1                                                         |',
                '|              | @hotwired/stimulus                                                               |',
                '+--------------+----------------------------------------------------------------------------------+',
            ]))
            ->assertOutputContains(implode(\PHP_EOL, [
                '+--------------+------------------------ Recipe: "button" ----------------------------------------+',
                '| File(s)      | N/A                                                                              |',
                '| Dependencies | twig/html-extra:^3.12.0                                                          |',
                '|              | another/php-package:^2.0                                                         |',
                '|              | another-npm-package:^1.0.0                                                       |',
                '|              | another-importmap-package                                                        |',
                '+--------------+----------------------------------------------------------------------------------+',
            ]));
    }
}
