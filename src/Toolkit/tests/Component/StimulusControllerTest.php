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
use Symfony\UX\Toolkit\Component\StimulusController;

class StimulusControllerTest extends TestCase
{
    public function testIsFilename()
    {
        self::assertTrue(StimulusController::isFilename('assets/controllers/alert_controller.js'));
        self::assertTrue(StimulusController::isFilename('closeable_controller.js'));
        self::assertFalse(StimulusController::isFilename('assets/controllers/helper.js'));
        self::assertFalse(StimulusController::isFilename('alert.html.twig'));
    }

    public function testIdentifierFromFilenameOrPath()
    {
        self::assertSame('closeable', StimulusController::identifier('closeable_controller.js'));
        self::assertSame('alert-dialog', StimulusController::identifier('alert_dialog_controller.js'));
        self::assertSame('hover-card', StimulusController::identifier('assets/controllers/hover_card_controller.js'));
    }

    public function testFilenameFromIdentifier()
    {
        self::assertSame('closeable_controller.js', StimulusController::filename('closeable'));
        self::assertSame('alert_dialog_controller.js', StimulusController::filename('alert-dialog'));
    }

    public function testValueAttributeDasherizesCamelCaseAndSnakeCase()
    {
        self::assertSame('data-widget-auto-close-value', StimulusController::valueAttribute('widget', 'autoClose'));
        self::assertSame('data-widget-open-value', StimulusController::valueAttribute('widget', 'open'));
        // Underscores are normalized to kebab-case too.
        self::assertSame('data-widget-auto-close-value', StimulusController::valueAttribute('widget', 'auto_close'));
    }
}
