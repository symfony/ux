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
 * @author Bart Vanderstukken <bart.vanderstukken@gmail.com>
 */
#[AsLiveComponent('parent_component_data_model_2')]
final class ParentComponentDataModel2
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)] public string $content;
}
