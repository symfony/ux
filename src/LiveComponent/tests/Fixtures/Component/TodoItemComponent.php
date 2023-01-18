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
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\Tests\Fixtures\Entity\Entity1;

#[AsLiveComponent('todo_item')]
final class TodoItemComponent
{
    #[LiveProp(writable: true)]
    public string $text = '';

    // here just to force a checksum to be needed, helps make tests more robust
    #[LiveProp(writable: false)]
    public string $readonlyValue = 'readonly';

    use DefaultActionTrait;
}
