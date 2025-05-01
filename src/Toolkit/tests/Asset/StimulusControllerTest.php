<?php

declare(strict_types=1);

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Tests\Asset;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Toolkit\Asset\StimulusController;
use Symfony\UX\Toolkit\File\File;
use Symfony\UX\Toolkit\File\FileType;

final class StimulusControllerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $stimulusController = new StimulusController('clipboard', [
            new File(FileType::StimulusController, 'assets/controllers/clipboard_controller.js', 'clipboard_controller.js'),
        ]);

        $this->assertSame('clipboard', $stimulusController->name);
    }

    public function testShouldFailIfStimulusControllerNameIsInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid Stimulus controller name "invalid_controller".');

        new StimulusController('invalid_controller', [new File(FileType::StimulusController, 'assets/controllers/invalid_controller.js', 'invalid_controller.js')]);
    }

    public function testShouldFailIfStimulusControllerHasNoFiles(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Stimulus controller "clipboard" has no files.');

        new StimulusController('clipboard', []);
    }
}
