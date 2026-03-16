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

use Symfony\UX\LiveComponent\Attribute\LiveProp;

/**
 * Reproduces the pattern in ComponentWithFormTrait where a public #[LiveProp]
 * with a callable fieldName is declared in a trait.
 *
 * Child components that extend a parent using this trait must inherit the
 * LiveProp with the correct fieldName callable.
 */
trait HasLivePropTrait
{
    /**
     * Public #[LiveProp] with a callable fieldName — this is the pattern used
     * in ComponentWithFormTrait::$formValues.
     */
    #[LiveProp(writable: true, fieldName: 'getFormName()')]
    public array $formValues = [];

    public function getFormName(): string
    {
        return 'live_prop_inheritance_form';
    }
}
