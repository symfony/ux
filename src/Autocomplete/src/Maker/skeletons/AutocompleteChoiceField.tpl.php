<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\UX\Autocomplete\Maker\MakerAutocompleteVariables;

/* @var MakerAutocompleteVariables $variables */
/* @var string $namespace */
/* @var string $class_name */
echo "<?php\n";
?>

namespace <?php echo $namespace; ?>;

<?php echo $variables->useStatements; ?>

#[AsAutocompleteField]
class <?php echo $class_name; ?> extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choices' => [
                // Add your choices here, e.g.:
                // 'Option 1' => 'option1',
                // 'Option 2' => 'option2',
            ],
            'placeholder' => 'Choose an option',

            // 'security' => 'ROLE_SOMETHING',
        ]);
    }

    public function getParent(): string
    {
        return AutocompleteChoiceType::class;
    }
}
