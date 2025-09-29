<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Autocomplete\Maker;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Doctrine\DoctrineHelper;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\UseStatementGenerator;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;
use Symfony\UX\Autocomplete\Form\BaseEntityAutocompleteType;

/**
 * @author Ryan Weaver <ryan@symfonycasts.com>
 */
class MakeAutocompleteField extends AbstractMaker
{
    private string $className;
    private string $entityClass;

    public function __construct(
        private ?DoctrineHelper $doctrineHelper = null,
    ) {
    }

    public static function getCommandName(): string
    {
        return 'make:autocomplete-field';
    }

    public static function getCommandDescription(): string
    {
        return 'Generates an Ajax-autocomplete form field class for symfony/ux-autocomplete.';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
             ->setHelp(<<<EOF
                 The <info>%command.name%</info> command generates an Ajax-autocomplete form field class for symfony/ux-autocomplete

                 <info>php %command.full_name%</info>

                 The command will ask you which entity the field is for and what to call your new class.
                 EOF)
        ;
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
        $dependencies->addClassDependency(FormInterface::class, 'symfony/form');
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        if (null === $this->doctrineHelper) {
            throw new \LogicException('Somehow the DoctrineHelper service is missing from MakerBundle.');
        }

        $entities = $this->doctrineHelper->getEntitiesForAutocomplete();

        $question = new Question('The class name of the entity you want to autocomplete');
        $question->setAutocompleterValues($entities);
        $question->setValidator(function ($choice) use ($entities) {
            return Validator::entityExists($choice, $entities);
        });

        $this->entityClass = $io->askQuestion($question);

        $defaultClass = Str::asClassName(\sprintf('%s AutocompleteField', $this->entityClass));
        $this->className = $io->ask(
            \sprintf('Choose a name for your entity field class (e.g. <fg=yellow>%s</>)', $defaultClass),
            $defaultClass
        );
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        if (null === $this->doctrineHelper) {
            throw new \LogicException('Somehow the DoctrineHelper service is missing from MakerBundle.');
        }

        $entityClassDetails = $generator->createClassNameDetails(
            $this->entityClass,
            'Entity\\'
        );
        $entityDoctrineDetails = $this->doctrineHelper->createDoctrineDetails($entityClassDetails->getFullName());

        $classDetails = $generator->createClassNameDetails(
            $this->className,
            'Form\\',
        );

        $repositoryClassDetails = $entityDoctrineDetails->getRepositoryClass() ? $generator->createClassNameDetails('\\'.$entityDoctrineDetails->getRepositoryClass(), '') : null;

        // use App\Entity\Category;
        // use App\Repository\CategoryRepository;
        $useStatements = new UseStatementGenerator([
            $entityClassDetails->getFullName(),
            $repositoryClassDetails ? $repositoryClassDetails->getFullName() : EntityManagerInterface::class,
            AbstractType::class,
            OptionsResolver::class,
            AsEntityAutocompleteField::class,
            BaseEntityAutocompleteType::class,
        ]);

        $variables = new MakerAutocompleteVariables(
            useStatements: $useStatements,
            entityClassDetails: $entityClassDetails,
            repositoryClassDetails: $repositoryClassDetails,
        );
        $generator->generateClass(
            $classDetails->getFullName(),
            __DIR__.'/skeletons/AutocompleteField.tpl.php',
            [
                'variables' => $variables,
            ]
        );

        $generator->writeChanges();

        $this->writeSuccessMessage($io);

        $io->text([
            'Customize your new field class, then add it to a form:',
            '',
            '    <comment>$builder</comment>',
            '        <comment>// ...</comment>',
            \sprintf('        <comment>->add(\'%s\', %s::class)</comment>', Str::asLowerCamelCase($entityClassDetails->getShortName()), $classDetails->getShortName()),
            '    <comment>;</>',
        ]);
    }
}
