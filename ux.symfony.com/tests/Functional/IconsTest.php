<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Browser\Test\HasBrowser;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class IconsTest extends KernelTestCase
{
    use HasBrowser;

    public function testCanViewIconFromHomepage(): void
    {
        $this->browser()
            ->visit('/')
            ->assertSuccessful()
            ->assertSeeIn('.AppNav_menu', 'Icons')
            ->click('Icons')
            ->assertSuccessful()
            ->assertSeeIn('title', 'Icons')
            ->assertSeeIn('h1', 'Icons')
        ;
    }

    public function testCanViewIconIndex(): void
    {
        $this->browser()
            ->visit('/icons')
            ->assertSuccessful()
            ->assertSeeIn('h1', 'Icons')
        ;
    }
}
