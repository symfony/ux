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
use Zenstruck\Console\Test\InteractsWithConsole;

class LintKitCommandTest extends KernelTestCase
{
    use InteractsWithConsole;

    public function testShouldBeAbleToLint(): void
    {
        $this->bootKernel();
        $this->consoleCommand('ux:toolkit:lint-kit shadcn')
            ->execute()
            ->assertSuccessful()
            ->assertOutputContains('The kit "Shadcn" is valid, it has 46 components')
        ;
    }
}
