<?php

use Symfony\Bundle\MakerBundle\Str;
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
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'class' => <?php echo $variables->entityClassDetails->getShortName(); ?>::class,
            'placeholder' => 'Choose a <?php echo $variables->entityClassDetails->getShortName(); ?>',
            //'choice_label' => 'name',

<?php if ($variables->repositoryClassDetails) { ?>
            'query_builder' => function(<?php echo $variables->repositoryClassDetails->getShortName(); ?> $<?php echo Str::asLowerCamelCase($variables->repositoryClassDetails->getShortName()); ?>) {
                return $<?php echo Str::asLowerCamelCase($variables->repositoryClassDetails->getShortName()); ?>->createQueryBuilder('<?php echo Str::asLowerCamelCase($variables->entityClassDetails->getShortName()); ?>');
            },
<?php } ?>
            //'security' => 'ROLE_SOMETHING',
        ]);
    }

    public function getParent(): string
    {
        return ParentEntityAutocompleteType::class;
    }
}
