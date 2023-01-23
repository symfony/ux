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

#[AsLiveComponent('todo_list')]
final class TodoListComponent
{
    #[LiveProp(writable: true)]
    public string $name = '';

    #[LiveProp]
    public array $items = [];

    #[LiveProp(writable: true)]
    public $includeDataLiveId = false;

    use DefaultActionTrait;
}
