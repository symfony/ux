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
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
#[AsLiveComponent('component3')]
final class Component3
{
    use DefaultActionTrait;

    #[LiveProp(fieldName: 'myProp1')]
    public $prop1;

    #[LiveProp(fieldName: 'getProp2Name()')]
    public $prop2;

    public function getProp2Name(): string
    {
        return 'myProp2';
    }
}
