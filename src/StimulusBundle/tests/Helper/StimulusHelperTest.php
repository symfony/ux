<?php

declare(strict_types=1);

/*
 * This file is part of the Symfony StimulusBundle package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\StimulusBundle\Tests\Helper;

use PHPUnit\Framework\TestCase;
use Symfony\UX\StimulusBundle\Dto\StimulusAttributes;
use Symfony\UX\StimulusBundle\Helper\StimulusHelper;
use Twig\Environment;

final class StimulusHelperTest extends TestCase
{
    public function testCreateStimulusAttributes(): void
    {
        $helper = new StimulusHelper($this->createMock(Environment::class));
        $attributes = $helper->createStimulusAttributes();

        $this->assertInstanceOf(StimulusAttributes::class, $attributes);
    }
}
