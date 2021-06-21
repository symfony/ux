<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\Tests\Fixture\Component;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Tests\Fixture\Service\ServiceA;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
#[AsTwigComponent('component_a')]
final class ComponentA
{
    public $propA;

    private $propB;
    private $service;

    public function __construct(ServiceA $service)
    {
        $this->service = $service;
    }

    public function getService()
    {
        return $this->service;
    }

    public function mount($propB)
    {
        $this->propB = $propB;
    }

    public function getPropB()
    {
        return $this->propB;
    }
}
