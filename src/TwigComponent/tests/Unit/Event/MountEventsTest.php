<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\Tests\Unit\Event;

use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\UX\TwigComponent\Event\PostMountEvent;
use Symfony\UX\TwigComponent\Event\PreMountEvent;

/**
 * Remove in TwigComponent 3.0.
 *
 * @group legacy
 */
class MountEventsTest extends TestCase
{
    use ExpectDeprecationTrait;

    public function testPreMountEventCreation()
    {
        $this->expectDeprecation('Since symfony/ux-twig-component 2.13: In TwigComponent 3.0, "Symfony\UX\TwigComponent\Event\PreMountEvent::__construct()" method will require a "Symfony\UX\TwigComponent\ComponentMetadata $metadata" argument. Not passing it is deprecated.');
        new PreMountEvent(new \stdClass(), []);

        $this->expectDeprecation('Since symfony/ux-twig-component 2.13: In TwigComponent 3.0, "Symfony\UX\TwigComponent\Event\PreMountEvent::__construct()" method will require a "Symfony\UX\TwigComponent\ComponentMetadata $metadata" argument. Not passing it is deprecated.');
        new PreMountEvent(new \stdClass(), [], null);
    }

    public function testPostMountEventCreation()
    {
        $this->expectDeprecation('Since symfony/ux-twig-component 2.13: In TwigComponent 3.0, the third argument of "Symfony\UX\TwigComponent\Event\PostMountEvent::__construct()" will be a "Symfony\UX\TwigComponent\ComponentMetadata" object and the "$extraMetadata" array should be passed as the fourth argument.');
        new PostMountEvent(new \stdClass(), []);

        $this->expectDeprecation('Since symfony/ux-twig-component 2.13: In TwigComponent 3.0, the third argument of "Symfony\UX\TwigComponent\Event\PostMountEvent::__construct()" will be a "Symfony\UX\TwigComponent\ComponentMetadata" object and the "$extraMetadata" array should be passed as the fourth argument.');
        new PostMountEvent(new \stdClass(), [], []);
    }
}
