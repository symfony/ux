<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DataTable;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Shared contract of the data table, column and filter types.
 *
 * Like form types, a type inherits the options and the behavior of its parent.
 *
 * @author Simon Daigre <simon@daig.re>
 */
interface TypeInterface
{
    public function configureOptions(OptionsResolver $resolver): void;

    /**
     * @return class-string<self>|null
     */
    public function getParent(): ?string;

    /**
     * The prefix of the Twig blocks rendering this type.
     */
    public function getBlockPrefix(): string;
}
