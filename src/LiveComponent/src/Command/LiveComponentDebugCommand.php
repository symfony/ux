<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\TypeInfo\Type;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\Metadata\LiveComponentMetadata;
use Symfony\UX\LiveComponent\Metadata\LiveComponentMetadataFactory;

#[AsCommand(name: 'debug:live-component', description: 'Display live components and their usage for an application')]
class LiveComponentDebugCommand extends Command
{
    public function __construct(
        protected readonly LiveComponentMetadataFactory $metadataFactory,
        protected readonly array $liveComponentList,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDefinition([
                new InputArgument(
                    'name',
                    InputArgument::OPTIONAL,
                    'A LiveComponent name or part of the name'
                ),
                new InputOption(
                    name: 'listening',
                    mode: InputOption::VALUE_REQUIRED,
                    description: 'Filter list to display only those listening to the given event'
                ),
            ])
            ->setHelp(
                <<<'EOF'
                    The <info>%command.name%</info> display all the live components in your application.

                    To list all live components:

                        <info>php %command.full_name%</info>

                    To get specific information about a component, specify its name (or a part of it):

                        <info>php %command.full_name% ProductSearch</info>
                    EOF
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $name = $input->getArgument('name');

        if (\is_string($name)) {
            $componentName = $this->findComponentName($io, $name, $input->isInteractive());
            if (null === $componentName) {
                $io->error(\sprintf('Unknown component "%s".', $name));

                return Command::FAILURE;
            }

            $this->displayComponentDetails($io, $componentName);

            return Command::SUCCESS;
        }

        $components = $this->findComponents($input->getOption('listening'));

        $this->displayComponentsTable($components, $io);

        return Command::SUCCESS;
    }

    private function findComponentName(SymfonyStyle $io, string $name, bool $interactive): ?string
    {
        $components = [];
        foreach ($this->liveComponentList as $className) {
            $metadata = $this->metadataFactory->getMetadata($className);
            $componentName = $metadata->getComponentMetadata()->getName();
            if ($name === $componentName) {
                return $name;
            }
            if (str_contains($componentName, $name)) {
                $components[$componentName] = $componentName;
            }
        }

        if ($interactive && \count($components)) {
            return $io->choice('Select one of the following component to display its information', array_values($components), 0);
        }

        return null;
    }

    /**
     * @return array<string, LiveComponentMetadata>
     */
    private function findComponents(?string $eventFilter = null): array
    {
        $components = [];
        if (null === $eventFilter) {
            foreach ($this->liveComponentList as $className) {
                $components[$className] ??= $this->metadataFactory->getMetadata($className);
            }

            return $components;
        }

        foreach ($this->liveComponentList as $className) {
            foreach (AsLiveComponent::liveListeners($className) as $listener) {
                if ($listener['event'] === $eventFilter) {
                    $components[$className] ??= $this->metadataFactory->getMetadata($className);
                    break;
                }
            }
        }

        return $components;
    }

    private function displayComponentDetails(SymfonyStyle $io, string $name): void
    {
        $metadata = $this->metadataFactory->getMetadata($name);

        $table = $io->createTable();
        $table->setHeaderTitle('Component');
        $table->setHeaders(['Property', 'Value']);
        $table->addRows([
            ['Name', $metadata->getComponentMetadata()->getName()],
            ['Class', $metadata->getComponentMetadata()->getClass()],
        ]);

        $table->addRows([
            ['LiveProps', implode("\n", $this->getComponentLiveProps($metadata))],
            ['LiveListeners', implode("\n", $this->getComponentLiveListeners($metadata->getComponentMetadata()->getClass()))],
        ]);

        $table->render();
    }

    /**
     * @param array<string, LiveComponentMetadata> $components
     */
    private function displayComponentsTable(array $components, SymfonyStyle $io): void
    {
        $table = $io->createTable();
        $table->setStyle('default');
        $table->setHeaderTitle('Components');
        $table->setHeaders(['Name', 'Class']);
        foreach ($components as $component) {
            $table->addRow([
                $component->getComponentMetadata()->getName(),
                $component->getComponentMetadata()->getClass() ?? '',
            ]);
        }
        $table->render();
    }

    /**
     * @return array<string, string>
     */
    private function getComponentLiveProps(LiveComponentMetadata $component): array
    {
        $liveProps = [];
        foreach ($component->getAllLivePropsMetadata(null) as $liveProp) {
            $reflection = new \ReflectionProperty($component->getComponentMetadata()->getClass(), $liveProp->getName());
            $type = $this->displayType($liveProp->getType());
            $propertyName = '$'.$liveProp->getName();
            $defaultValueDisplay = $reflection->hasDefaultValue() ?
                $this->displayDefaultValue($reflection->getDefaultValue()) :
                '';
            $arguments = $reflection->getAttributes(LiveProp::class)[0]->getArguments();
            $argumentsDisplay = empty($arguments) ?
                '' :
                ' ('.implode(', ', array_map(
                    static fn ($key, $value) => $key.': '.json_encode($value),
                    array_keys($arguments),
                    $arguments
                )).')';

            $propertyDisplay = $type.$propertyName.$defaultValueDisplay.$argumentsDisplay;
            $liveProps[$liveProp->getName()] = $propertyDisplay;
        }

        return $liveProps;
    }

    /**
     * @return array<string, string>
     */
    private function getComponentLiveListeners(string $class): array
    {
        $events = [];
        foreach (AsLiveComponent::liveListeners($class) as $liveListener) {
            $name = $liveListener['event'];
            $methodName = $liveListener['action'];
            $method = new \ReflectionMethod($class, $methodName);
            $parameters = array_map(
                fn (\ReflectionParameter $parameter) => $this->displayType($parameter->getType()).'$'.$parameter->getName().$this->displayDefaultValue($parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null),
                array_filter(
                    $method->getParameters(),
                    static fn (\ReflectionParameter $parameter) => !empty($parameter->getAttributes(LiveArg::class))
                )
            );
            $parametersDisplay = empty($parameters) ?
                '' :
                ' ('.implode(', ', $parameters).')';

            $display = $name.' => '.$methodName.$parametersDisplay;
            $events[] = $display;
        }

        return $events;
    }

    private function displayType(Type|string|null $type): string
    {
        $display = (string) $type;
        if ($type instanceof Type && $type->isNullable() && !str_contains($display, 'null')) {
            $display = '?'.$display;
        }
        if ('' !== $display) {
            $display .= ' ';
        }

        return $display;
    }

    private function displayDefaultValue(mixed $defaultValue): string
    {
        return (null !== $defaultValue) ?
            ' = '.json_encode($defaultValue) :
            '';
    }
}
