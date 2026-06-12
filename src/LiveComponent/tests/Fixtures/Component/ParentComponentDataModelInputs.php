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

#[AsLiveComponent('parent_component_data_model_inputs')]
final class ParentComponentDataModelInputs
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public string $choice = 'b';

    /** @var string[] */
    #[LiveProp(writable: true)]
    public array $selected = ['paris'];

    #[LiveProp(writable: true)]
    public bool $active = true;

    #[LiveProp(writable: true)]
    public string $name = 'John';
}
