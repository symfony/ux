<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Tests\Component;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Toolkit\Component\StimulusControllerDoc;
use Symfony\UX\Toolkit\Component\StimulusControllerDocParser;

// Source scanning (tags, value types, targets) is covered by StimulusControllerSourceTest; these
// tests focus on what the parser itself does: assemble the DTOs (deriving each value's `data-*`
// attribute, wiring descriptions) and honor the opt-in gate.
class StimulusControllerDocParserTest extends TestCase
{
    public function testParsesValueNameTypeAndDerivedAttribute()
    {
        $doc = new StimulusControllerDocParser()->parse(<<<'JS'
            /**
             * @value autoClose Delay in milliseconds before the element removes itself.
             */
            export default class extends Controller {
                static values = { autoClose: Number }
            }
            JS, 'closeable');

        self::assertInstanceOf(StimulusControllerDoc::class, $doc);
        self::assertCount(1, $doc->values);
        self::assertSame('autoClose', $doc->values[0]->name);
        self::assertSame('data-closeable-auto-close-value', $doc->values[0]->attribute);
        self::assertSame('Number', $doc->values[0]->type);
        self::assertNull($doc->values[0]->default);
        self::assertSame('Delay in milliseconds before the element removes itself.', $doc->values[0]->description);
    }

    public function testActionsAreOptInFromTags()
    {
        $doc = new StimulusControllerDocParser()->parse(<<<'JS'
            /**
             * @action close Removes the element.
             * @action cancel Cancels a pending close.
             */
            export default class extends Controller {
                close() {}
                cancel() {}
                #remove() {}
                internalHelper() {}
            }
            JS, 'closeable');

        // Actions come only from `@action` tags, so undocumented methods are not exposed.
        self::assertCount(2, $doc->actions);
        self::assertSame('close', $doc->actions[0]->name);
        self::assertSame('Removes the element.', $doc->actions[0]->description);
        self::assertSame('cancel', $doc->actions[1]->name);
    }

    public function testUndocumentedControllerYieldsEmptyDocEvenWithDeclaredValuesAndTargets()
    {
        $doc = new StimulusControllerDocParser()->parse(<<<'JS'
            export default class extends Controller {
                static values = { open: Boolean }
                static targets = ['panel']
            }
            JS, 'closeable');

        // Documentation is opt-in: no docblock tag means no API reference, even though the
        // controller declares a value and a target in code.
        self::assertTrue($doc->isEmpty());
    }

    public function testReturnsEmptyDocWhenNothingDeclared()
    {
        $doc = new StimulusControllerDocParser()->parse('export default class extends Controller {}', 'closeable');

        self::assertTrue($doc->isEmpty());
    }
}
