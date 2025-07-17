<?php

use Symfony\UX\Autocomplete\Maker\MakerAutocompleteVariables;

/** @var MakerAutocompleteVariables $variables */
/** @var string $namespace */
/** @var string $class_name */
echo "<?php\n";
?>

namespace <?php echo $namespace; ?>;

<?php echo $variables->useStatements; ?>

#[AsEntityAutocompleteField]
class <?php echo $class_name; ?> extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => <?php echo $variables->entityClassDetails->getShortName(); ?>::class,
            'placeholder' => 'Choose a <?php echo $variables->entityClassDetails->getShortName(); ?>',
            // 'choice_label' => 'name',

            // choose which fields to use in the search
            // if not passed, *all* fields are used
            // 'searchable_fields' => ['name'],

            // 'security' => 'ROLE_SOMETHING',
        ]);
    }

    public function getParent(): string
    {
        return BaseEntityAutocompleteType::class;
    }
}
