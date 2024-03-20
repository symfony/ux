<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Browser\Test\HasBrowser;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class IconsTest extends KernelTestCase
{
    use HasBrowser;

    /**
     * @test
     */
    public function can_view_icon_index(): void
    {
        $this->browser()
            ->visit('/')
            ->assertSuccessful()
        ;
            // ->click('Icons')
            // ->assertSuccessful()
            // ->assertOn('/icons')
            // ->assertSeeIn('h1', 'Icons')
    }
}
