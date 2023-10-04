<?php

namespace Symfony\UX\LiveComponent\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Completion\CompletionInput;
use Symfony\Component\Console\Completion\CompletionSuggestions;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Metadata\LiveComponentMetadata;
use Symfony\UX\LiveComponent\Metadata\LiveComponentMetadataFactory;
use Symfony\UX\LiveComponent\Metadata\LivePropMetadata;
use Symfony\UX\TwigComponent\ComponentFactory;

/**
 * @author Simon André <smn.andre@gmail.com>
 *
 * @experimental
 */
#[AsCommand(name: 'debug:live-component', description: 'Display Live components')]
class LiveComponentDebugCommand extends Command
{
    private ?array $liveComponentsMap = null;

    /**
     * @internal
     */
    public function __construct(
        private readonly ComponentFactory $componentFactory,
        private readonly LiveComponentMetadataFactory $metadataFactory,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDefinition([
                new InputArgument('name', InputArgument::OPTIONAL, 'A LiveComponent name (or part of the component name)'),
            ])
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> display all the Live components in your application.

To list all components:

    <info>php %command.full_name%</info>

To get specific information about a component, specify its name (or a part of it):

    <info>php %command.full_name% FooBar</info>
EOF
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $name = $input->getArgument('name');

        if (!\is_string($name)) {
            $this->displayComponentsTable($io, $this->findComponents());
            $io->text([
                '// Provide the name of a LiveComponent as argument of this command to get its detailed information.',
                '// (e.g. <comment>debug:live-component FooBar</comment>)',
            ]);

            return Command::SUCCESS;
        }

        $component = $this->findComponentName($io, $name, $input->isInteractive());
        if (null === $component) {
            $io->error(sprintf('Unknown component "%s".', $name));

            return Command::FAILURE;
        }

        $this->displayComponentDetails($io, $component);

        return Command::SUCCESS;
    }

    public function complete(CompletionInput $input, CompletionSuggestions $suggestions): void
    {
        if ($input->mustSuggestArgumentValuesFor('name')) {
            $suggestions->suggestValues(array_keys($this->findComponents()));
        }
    }

    /**
     * @param array<string, LiveComponentMetadata> $components
     */
    private function displayComponentsTable(SymfonyStyle $io, array $components): void
    {
        $table = $io->createTable();
        // $table->setStyle('default');
        $table->setHeaderTitle('Live Components');
        $table->setHeaders(['Name', 'Class', 'Template']);
        foreach ($components as $metadata) {
            $componentMetadata = $metadata->getComponentMetadata();
            $table->addRow([
                $componentMetadata->getName(),
                $componentMetadata->getClass(),
                $componentMetadata->getTemplate(),
            ]);
        }
        $table->render();
        $io->newLine();
    }

    private function displayComponentDetails(SymfonyStyle $io, string $name): void
    {
        $metadata = $this->metadataFactory->getMetadata($name);
        $componentMetadata = $metadata->getComponentMetadata();
        $componentClass = $componentMetadata->getClass();

        $table = $io->createTable();
        $table->setHeaderTitle('Live Component');
        $table->setHeaders(['Property', 'Value']);
        $table->addRows([
            ['Name', $componentMetadata->getName()],
            ['Class', '<comment>'.$componentClass.'</comment>'],
            ['Template', $componentMetadata->getTemplate()],
        ]);

        if ($props = $metadata->getAllLivePropsMetadata()) {
            $formatLiveProp = fn (LivePropMetadata $liveProp) => sprintf(
                '%s <comment>$%s</comment>',
                $liveProp->getType() ?? '',
                $liveProp->getName(),
            );
            $table->addRows([
                new TableSeparator(),
                ['LiveProp', implode("\n", array_map($formatLiveProp(...), $props))],
            ]);
        }

        $methods = array_filter([
            'LiveAction' => AsLiveComponent::liveActionMethods($componentClass),
            'PreReRender' => AsLiveComponent::preReRenderMethods($componentClass),
            'PreDehydrate' => AsLiveComponent::preDehydrateMethods($componentClass),
            'PostHydrate' => AsLiveComponent::postHydrateMethods($componentClass),
        ]);
        foreach ($methods as $title => $values) {
            $table->addRows([
                new TableSeparator(),
                [$title, implode("\n", array_map($this->formatMethod(...), $values))],
            ]);
        }

        $io->newLine();
        $table->render();
    }

    private function formatMethod(\ReflectionMethod $method): string
    {
        $parameters = array_map($this->formatParameter(...), $method->getParameters());

        return sprintf('<comment>%s</comment>(%s)', $method->getName(), implode(',', $parameters));
    }

    private function formatParameter(\ReflectionParameter $param): string
    {
        $formatted = sprintf('$%s', $param->getName());
        if ($type = (string) $param->getType()) {
            if ($type = substr(strrchr($type, '\\'), 1)) {
                $formatted = sprintf('<fg=white;bg=default>%s</> %s', $type, $formatted);
            }
        }

        return trim($formatted);
    }

    /**
     * @return array<string, LiveComponentMetadata>
     */
    private function findComponents(): array
    {
        $components = [];
        foreach ($this->getLiveComponents() as $name) {
            $components[$name] = $this->metadataFactory->getMetadata($name);
        }

        return $components;
    }

    private function findComponentName(SymfonyStyle $io, string $name, bool $interactive): ?string
    {
        $components = [];
        foreach ($this->getLiveComponents() as $componentName) {
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
     * @return array<string>
     *
     * @internal
     */
    private function getLiveComponents(): array
    {
        if (null !== $this->liveComponentsMap) {
            return $this->liveComponentsMap;
        }

        $reflector = new \ReflectionClass($this->componentFactory);
        $config = $reflector->getProperty('config')->getValue($this->componentFactory);
        $liveMap = array_filter($config, fn (array $c) => $c['live'] ?? false);

        return $this->liveComponentsMap = array_keys($liveMap);
    }
}
