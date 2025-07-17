<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Twig\Components;

use App\Twig\Components\ChangelogItem;
use PHPUnit\Framework\TestCase;

class ChangelogItemTest extends TestCase
{
    public function testSetItem()
    {
        $component = new ChangelogItem();
        $component->item = ['body' => 'foobar'];

        $this->assertSame(['body' => 'foobar'], $component->item);
        $this->assertSame('foobar', $component->getContent());
    }

    /**
     * @dataProvider provideContentValues
     */
    public function testFormatContent(string $body, string $expected)
    {
        $component = new ChangelogItem();
        $component->item = [
            'body' => $body,
        ];

        $this->assertSame($expected, $component->getContent());
    }

    public static function provideContentValues(): iterable
    {
        yield 'keep_existing_h1' => [
            '# Title 1',
            '# Title 1',
        ];
        yield 'transform_h2_to_h3' => [
            '## Title',
            '### Title',
        ];
        yield 'keep_existing_h3' => [
            '### Title 3',
            '### Title 3',
        ];
        yield 'inject_changelog_link' => [
            'https://github.com/symfony/ux/compare/v2.14.1...v2.14.2',
            '[v2.14.1 -> v2.14.2](https://github.com/symfony/ux/compare/v2.14.1...v2.14.2)',
        ];
    }
}
