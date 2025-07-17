<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Fixtures\Component;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * @author Tomas Norkūnas <norkunas.tom@gmail.com>
 */
#[AsLiveComponent('component6', method: 'get')]
class Component6
{
    use DefaultActionTrait;

    #[LiveProp]
    public bool $called = false;

    #[LiveProp]
    public $arg1;

    #[LiveProp]
    public $arg2;

    #[LiveProp]
    public $arg3;

    #[LiveAction]
    public function inject(
        #[LiveArg] string $arg1,
        #[LiveArg] int $arg2,
        #[LiveArg('custom')] float $arg3,
    ) {
        $this->called = true;
        $this->arg1 = $arg1;
        $this->arg2 = $arg2;
        $this->arg3 = $arg3;
    }
}
