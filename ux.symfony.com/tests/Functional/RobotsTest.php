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

class RobotsTest extends KernelTestCase
{
    use HasBrowser;

    public function testSitemapContainsPages(): void
    {
        $browser = $this->browser([], [
            'HTTP_HOST' => 'ux.symfony.com',
        ])
            ->visit('/robots.txt')
            ->assertSuccessful()
            ->assertContentType('text/plain');
        $robot = $browser->content();

        $this->assertStringStartsWith("User-agent: *\nDisallow:\n", $robot);
        $this->assertStringContainsString('Sitemap: http://ux.symfony.com/sitemap.xml', $robot);
    }
}
