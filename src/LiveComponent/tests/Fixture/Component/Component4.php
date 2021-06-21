<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Fixture\Component;

use Symfony\UX\LiveComponent\Attribute\BeforeReRender;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\Attribute\PostHydrate;
use Symfony\UX\LiveComponent\Attribute\PreDehydrate;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
class Component4
{
    #[LiveProp]
    public $prop1;

    public $prop2;

    #[LiveProp]
    private $prop3;

    #[LiveAction]
    public function method1()
    {
    }

    public function method2()
    {
    }

    #[BeforeReRender]
    public function method3()
    {
    }

    #[PreDehydrate]
    public function method4()
    {
    }

    #[PostHydrate]
    public function method5()
    {
    }
}
