<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\Attribute;

/**
 * Use to expose private/protected properties as variables directly
 * in a component template (`someProp` vs `this.someProp`). These
 * properties must be "accessible" (have a getter).
 *
 * @see https://symfony.com/bundles/ux-twig-component#exposeintemplate-attribute
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
final class ExposeInTemplate
{
    /**
     * @param string|null $name     The variable name to expose. Leave as null
     *                              to default to property name.
     * @param string|null $getter   The getter method to use. Leave as null
     *                              to default to PropertyAccessor logic.
     * @param bool        $destruct The content should be used as array of variable
     *                              names
     */
    public function __construct(public ?string $name = null, public ?string $getter = null, public bool $destruct = false)
    {
    }
}
