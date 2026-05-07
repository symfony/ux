<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Tests\DataCollector;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\UX\Editor\DataCollector\UXEditorDataCollector;

final class UXEditorDataCollectorTest extends TestCase
{
    public function testRecordsBridgeUseAndWarnings(): void
    {
        $c = new UXEditorDataCollector();
        $c->recordBridgeUse('ckeditor', 'html');
        $c->recordCapabilityWarning('Bridge x lacks toolbar');
        $c->collect(new Request(), new Response());
        self::assertSame(1, $c->getBridgeUseCount());
        self::assertSame(['ckeditor' => ['html' => 1]], $c->getBridges());
        self::assertSame(['Bridge x lacks toolbar'], $c->getWarnings());
        self::assertSame('ux_editor', $c->getName());
    }

    public function testReset(): void
    {
        $c = new UXEditorDataCollector();
        $c->recordBridgeUse('q', 'html');
        $c->reset();
        self::assertSame(0, $c->getBridgeUseCount());
    }
}
