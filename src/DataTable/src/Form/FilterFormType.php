<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DataTable\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The GET form holding the filters of a data table.
 *
 * @internal
 *
 * @author Simon Daigre <simon@daig.re>
 */
final class FilterFormType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'method' => 'GET',
            'allow_extra_fields' => true,
        ]);

        // A GET form changes nothing, and must work from a shared link
        if ($resolver->isDefined('csrf_protection')) {
            $resolver->setDefault('csrf_protection', false);
        }
    }

    public function getBlockPrefix(): string
    {
        return 'ux_data_table_filters';
    }
}
