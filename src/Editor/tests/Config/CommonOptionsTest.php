<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Tests\Config;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Editor\Config\CommonOptions;

final class CommonOptionsTest extends TestCase
{
    public function testDefaults()
    {
        $o = new CommonOptions();
        self::assertNull($o->toolbar);
        self::assertNull($o->placeholder);
        self::assertFalse($o->readOnly);
        self::assertNull($o->height);
        self::assertNull($o->theme);
        self::assertNull($o->language);
        self::assertSame([], $o->plugins);
        self::assertFalse($o->autofocus);
        self::assertTrue($o->spellcheck);
    }

    public function testNamedArgsConstruction()
    {
        $o = new CommonOptions(
            toolbar: ['bold', 'italic'],
            placeholder: 'Write…',
            readOnly: true,
            height: '400px',
            theme: 'dark',
            language: 'fr',
            plugins: ['image', 'link'],
            autofocus: true,
            spellcheck: false,
        );
        self::assertSame(['bold', 'italic'], $o->toolbar);
        self::assertSame('Write…', $o->placeholder);
        self::assertTrue($o->readOnly);
        self::assertSame('400px', $o->height);
        self::assertSame('dark', $o->theme);
        self::assertSame('fr', $o->language);
        self::assertSame(['image', 'link'], $o->plugins);
        self::assertTrue($o->autofocus);
        self::assertFalse($o->spellcheck);
    }

    public function testFromArrayMapsKeys()
    {
        $o = CommonOptions::fromArray(['toolbar' => ['bold'], 'placeholder' => 'x']);
        self::assertSame(['bold'], $o->toolbar);
        self::assertSame('x', $o->placeholder);
        self::assertFalse($o->readOnly);
    }
}
