<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Tests\Content;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Editor\Content\EditorContentFormat;

final class EditorContentFormatTest extends TestCase
{
    public function testCases(): void
    {
        self::assertSame('html',   EditorContentFormat::Html->value);
        self::assertSame('blocks', EditorContentFormat::Blocks->value);
        self::assertSame('page',   EditorContentFormat::Page->value);
        self::assertSame(EditorContentFormat::Html, EditorContentFormat::from('html'));
    }
}
