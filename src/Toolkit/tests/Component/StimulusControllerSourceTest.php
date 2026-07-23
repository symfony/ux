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
use Symfony\UX\Toolkit\Component\StimulusControllerSource;

class StimulusControllerSourceTest extends TestCase
{
    public function testTags()
    {
        $source = new StimulusControllerSource(<<<'JS'
            /**
             * @value  open   Whether it is open.
             * @target panel  The panel.
             * @action toggle Toggles it.
             */
            export default class extends Controller {}
            JS);

        self::assertSame(['open' => 'Whether it is open.'], $source->tags()['value']);
        self::assertSame(['panel' => 'The panel.'], $source->tags()['target']);
        self::assertSame(['toggle' => 'Toggles it.'], $source->tags()['action']);
    }

    public function testValuesReadTypeAndDefaultFromCode()
    {
        $source = new StimulusControllerSource(<<<'JS'
            export default class extends Controller {
                static values = { autoClose: Number, open: { type: Boolean, default: false } }
            }
            JS);

        self::assertSame([
            ['name' => 'autoClose', 'type' => 'Number', 'default' => null],
            ['name' => 'open', 'type' => 'Boolean', 'default' => 'false'],
        ], $source->values());
    }

    public function testTargets()
    {
        $source = new StimulusControllerSource(<<<'JS'
            export default class extends Controller {
                static targets = ['trigger', 'content']
            }
            JS);

        self::assertSame(['trigger', 'content'], $source->targets());
    }

    public function testEmptySourceYieldsNothing()
    {
        $source = new StimulusControllerSource('export default class extends Controller {}');

        self::assertSame(['value' => [], 'target' => [], 'action' => []], $source->tags());
        self::assertSame([], $source->values());
        self::assertSame([], $source->targets());
    }

    public function testTagWithoutDescriptionIsRecognizedWithEmptyDescription()
    {
        $source = new StimulusControllerSource(<<<'JS'
            /**
             * @action save
             */
            export default class extends Controller {}
            JS);

        self::assertSame(['save' => ''], $source->tags()['action']);
    }

    public function testDescriptionStopsAtTheNextAnnotationLine()
    {
        $source = new StimulusControllerSource(<<<'JS'
            /**
             * @action close Closes the element.
             *
             * @param {Event} event The click event.
             * @return {void}
             */
            export default class extends Controller {}
            JS);

        // The description must not swallow `@param`/`@return`.
        self::assertSame(['close' => 'Closes the element.'], $source->tags()['action']);
    }

    public function testDescriptionKeepsInlineAtMentions()
    {
        $source = new StimulusControllerSource(<<<'JS'
            /**
             * @value theme Theme forwarded to @hotwired/stimulus consumers.
             */
            export default class extends Controller {}
            JS);

        // An `@` mid-sentence must not truncate the description; only a new annotation line does.
        self::assertSame(['theme' => 'Theme forwarded to @hotwired/stimulus consumers.'], $source->tags()['value']);
    }

    public function testEachDocblockIsParsedIndependently()
    {
        $source = new StimulusControllerSource(<<<'JS'
            /**
             * @action cancel Cancels a pending close.
             */
            export default class extends Controller {
                /**
                 * @param delay in ms
                 */
                close({ params }) {}
            }
            JS);

        // A tag's description is bounded by its own docblock and cannot swallow another's prose.
        self::assertSame(['cancel' => 'Cancels a pending close.'], $source->tags()['action']);
    }
}
