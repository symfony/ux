<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DataTable\Filter;

use Symfony\Component\Form\Util\StringUtil;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Simon Daigre <simon@daig.re>
 */
abstract class AbstractFilterType implements FilterTypeInterface
{
    public function configureOptions(OptionsResolver $resolver): void
    {
    }

    public function getParent(): ?string
    {
        return FilterType::class;
    }

    public function getBlockPrefix(): string
    {
        return (string) StringUtil::fqcnToBlockPrefix(static::class);
    }
}
