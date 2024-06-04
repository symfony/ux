<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Completion\CompletionInput;
use Symfony\Component\Console\Completion\CompletionSuggestions;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;
use Symfony\UX\TwigComponent\ComponentFactory;
use Symfony\UX\TwigComponent\ComponentMetadata;
use Symfony\UX\TwigComponent\Twig\PropsNode;
use Twig\Environment;
use Twig\Error\LoaderError;

#[AsCommand(name: 'debug:twig-component', description: 'Display components and them usages for an application')]
class TwigComponentDebugCommand extends Command
{
    private readonly string $anonymousDirectory;

    public function __construct(
        private string $twigTemplatesPath,
        private ComponentFactory $componentFactory,
        private Environment $twig,
        private readonly array $componentClassMap,
        ?string $anonymousDirectory = null,
    ) {
        parent::__construct();
        $this->anonymousDirectory = $anonymousDirectory ?? 'components';
    }

    protected function configure(): void
    {
        $this
            ->setDefinition([
                new InputArgument('name', InputArgument::OPTIONAL, 'A component name or part of the component name'),
            ])
            ->setHelp(
                <<<'EOF'
The <info>%command.name%</info> display all the Twig components in your application.

To list all components:

    <info>php %command.full_name%</info>

To get specific information about a component, specify its name (or a part of it):

    <info>php %command.full_name% Alert</info>
EOF
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $name = $input->getArgument('name');

        if (\is_string($name)) {
            $component = $this->findComponentName($io, $name, $input->isInteractive());
            if (null === $component) {
                $io->error(sprintf('Unknown component "%s".', $name));

                return Command::FAILURE;
            }

            $this->displayComponentDetails($io, $component);

            return Command::SUCCESS;
        }

        $components = $this->findComponents();
        $this->displayComponentsTable($io, $components);

        return Command::SUCCESS;
    }

    public function complete(CompletionInput $input, CompletionSuggestions $suggestions): void
    {
        if ($input->mustSuggestArgumentValuesFor('name')) {
            $suggestions->suggestValues(array_keys($this->findComponents()));
        }
    }

    private function findComponentName(SymfonyStyle $io, string $name, bool $interactive): ?string
    {
        $components = [];
        foreach ($this->componentClassMap as $componentName) {
            if ($name === $componentName) {
                return $name;
            }
            if (str_contains($componentName, $name)) {
                $components[$componentName] = $componentName;
            }
        }
        foreach ($this->findAnonymousComponents() as $componentName) {
            if (isset($components[$componentName])) {
                continue;
            }
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
     * @return array<string, ComponentMetadata>
     */
    private function findComponents(): array
    {
        $components = [];
        $usedResolvedTemplatePaths = [];
        $loader = $this->twig->getLoader();

        foreach ($this->componentClassMap as $name) {
            $metadata = $this->componentFactory->metadataFor($name);
            $components[$name] ??= $metadata;
            $template = $metadata->getTemplate();

            try {
                $resolvedPath = $loader->getSourceContext($template)->getPath();
                $usedResolvedTemplatePaths[$resolvedPath] = true;
            } catch (LoaderError) {
                // Ignore unresolvable paths.
            }
        }

        foreach ($this->findAnonymousComponents() as $name) {
            // Ignore if anonymous component name is same as a class based component.
            if (isset($components[$name])) {
                continue;
            }

            $metadata = $this->componentFactory->metadataFor($name);
            $template = $metadata->getTemplate();
            $resolvedPath = $loader->getSourceContext($template)->getPath();

            // Ignore if this template is used by a class based component.
            if (isset($usedResolvedTemplatePaths[$resolvedPath])) {
                continue;
            }

            $components[$name] = $metadata;
        }

        return $components;
    }

    /**
     * Returns a component names inside anonymous component directory.
     *
     * @return list<string>
     */
    private function findAnonymousComponents(): array
    {
        $components = [];
        $anonymousPath = $this->twigTemplatesPath.'/'.$this->anonymousDirectory;
        $finderTemplates = new Finder();
        $finderTemplates->files()->in($anonymousPath)->notPath('/_');

        foreach ($finderTemplates as $template) {
            $component = str_replace('/', ':', $template->getRelativePathname());
            $component = preg_replace('/(\.html)?\.twig$/', '', $component);
            $components[] = $component;
        }

        return $components;
    }

    private function displayComponentDetails(SymfonyStyle $io, string $name): void
    {
        $metadata = $this->componentFactory->metadataFor($name);

        $table = $io->createTable();
        $table->setHeaderTitle('Component');
        $table->setHeaders(['Property', 'Value']);
        $table->addRows([
            ['Name', $metadata->getName()],
            ['Class', $metadata->get('class') ?? ''],
            ['Template', $metadata->getTemplate()],
        ]);

        // Anonymous Component
        if (null === $metadata->get('class')) {
            $table->addRows([
                ['Type', '<comment>Anonymous</comment>'],
                new TableSeparator(),
                ['Properties', implode("\n", $this->getAnonymousComponentProperties($metadata))],
            ]);
            $table->render();

            return;
        }

        $table->addRows([
            ['Type', $metadata->get('live') ? '<info>Live</info>' : ''],
            new TableSeparator(),
            // ['Attributes Var', $metadata->get('attributes_var')],
            ['Public Props', $metadata->get('expose_public_props') ? 'Yes' : 'No'],
            ['Properties', implode("\n", $this->getComponentProperties($metadata))],
        ]);

        $logMethod = function (\ReflectionMethod $m) {
            $params = array_map(
                fn (\ReflectionParameter $p) => '$'.$p->getName(),
                $m->getParameters(),
            );

            return sprintf('%s(%s)', $m->getName(), implode(', ', $params));
        };
        $hooks = [];
        if ($method = AsTwigComponent::mountMethod($metadata->getClass())) {
            $hooks[] = ['Mount', $logMethod($method)];
        }
        foreach (AsTwigComponent::preMountMethods($metadata->getClass()) as $method) {
            $hooks[] = ['PreMount', $logMethod($method)];
        }
        foreach (AsTwigComponent::postMountMethods($metadata->getClass()) as $method) {
            $hooks[] = ['PostMount', $logMethod($method)];
        }
        if ($hooks) {
            $table->addRows([
                new TableSeparator(),
                ...$hooks,
            ]);
        }

        $table->render();
    }

    /**
     * @param array<ComponentMetadata> $components
     */
    private function displayComponentsTable(SymfonyStyle $io, array $components): void
    {
        $table = $io->createTable();
        $table->setStyle('default');
        $table->setHeaderTitle('Components');
        $table->setHeaders(['Name', 'Class', 'Template', 'Type']);
        foreach ($components as $metadata) {
            $table->addRow([
                $metadata->getName(),
                $metadata->get('class') ? $metadata->getClass() : '',
                $metadata->getTemplate(),
                $metadata->get('live') ? '<info>Live</info>' : ($metadata->get('class') ? '' : '<comment>Anon</comment>'),
            ]);
        }
        $table->render();
    }

    /**
     * @return array<string, string>
     */
    private function getComponentProperties(ComponentMetadata $metadata): array
    {
        $properties = [];
        $reflectionClass = new \ReflectionClass($metadata->getClass());
        foreach ($reflectionClass->getProperties() as $property) {
            $propertyName = $property->getName();

            if ($metadata->isPublicPropsExposed() && $property->isPublic()) {
                $type = $property->getType();
                if ($type instanceof \ReflectionNamedType) {
                    $typeName = $type->getName();
                } else {
                    $typeName = (string) $type;
                }
                $value = $property->getDefaultValue();
                $propertyDisplay = $typeName.' $'.$propertyName.(null !== $value ? ' = '.json_encode($value) : '');
                $properties[$property->name] = $propertyDisplay;
            }

            foreach ($property->getAttributes(ExposeInTemplate::class) as $exposeAttribute) {
                /** @var ExposeInTemplate $attribute */
                $attribute = $exposeAttribute->newInstance();
                $properties[$property->name] = $attribute->name ?? $property->name;
            }
        }

        return $properties;
    }

    /**
     * Extract properties from {% props %} tag in anonymous template.
     *
     * @return array<string, string>
     */
    private function getAnonymousComponentProperties(ComponentMetadata $metadata): array
    {
        $source = $this->twig->load($metadata->getTemplate())->getSourceContext();
        $tokenStream = $this->twig->tokenize($source);
        $moduleNode = $this->twig->parse($tokenStream);

        $propsNode = null;
        foreach ($moduleNode->getNode('body') as $bodyNode) {
            foreach ($bodyNode as $node) {
                if (PropsNode::class === $node::class) {
                    $propsNode = $node;
                    break 2;
                }
            }
        }
        if (!$propsNode instanceof PropsNode) {
            return [];
        }

        $propertyNames = $propsNode->getAttribute('names');
        $properties = array_combine($propertyNames, $propertyNames);
        foreach ($propertyNames as $propName) {
            if ($propsNode->hasNode($propName)
                && ($valueNode = $propsNode->getNode($propName))
                && $valueNode->hasAttribute('value')
            ) {
                $value = $valueNode->getAttribute('value');
                if (\is_bool($value)) {
                    $value = $value ? 'true' : 'false';
                } else {
                    $value = json_encode($value);
                }
                $properties[$propName] = $propName.' = '.$value;
            }
        }

        return $properties;
    }
}
