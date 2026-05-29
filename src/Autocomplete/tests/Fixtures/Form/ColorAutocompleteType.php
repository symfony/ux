<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Autocomplete\Tests\Fixtures\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Autocomplete\Form\AsAutocompleteField;
use Symfony\UX\Autocomplete\Form\AutocompleteChoiceType;

#[AsAutocompleteField]
class ColorAutocompleteType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choices' => [
                'Red' => 'red',
                'Green' => 'green',
                'Blue' => 'blue',
                'Yellow' => 'yellow',
                'Purple' => 'purple',
                'Orange' => 'orange',
                'Pink' => 'pink',
                'Brown' => 'brown',
                'Black' => 'black',
                'White' => 'white',
                'Gray' => 'gray',
                'Cyan' => 'cyan',
            ],
            'placeholder' => 'Pick a color',
            'max_results' => 5,
        ]);
    }

    public function getParent(): string
    {
        return AutocompleteChoiceType::class;
    }
}
