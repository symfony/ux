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
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\TwigComponent\Attribute\PostMount;
use Symfony\UX\TwigComponent\Attribute\PreMount;
use Symfony\UX\TwigComponent\ComponentFactory;
use Symfony\UX\TwigComponent\ComponentMetadata;
use Symfony\UX\TwigComponent\Twig\PropsNode;
use Twig\Environment;

#[AsCommand(name: 'debug:twig-component', description: 'Display current components and them usages for an application')]
class ComponentDebugCommand extends Command
{
    public function __construct(private string $twigTemplatesPath, private ComponentFactory $componentFactory, private Environment $twigEnvironment, private iterable $components)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDefinition([
                new InputArgument('name', InputArgument::OPTIONAL, 'A component name'),
                new InputOption('dir', null, InputOption::VALUE_REQUIRED, 'Show all components with a specific directory in templates', 'components'),
            ])
            ->setHelp(<<<'EOF'
                The <info>%command.name%</info> display all components in your application:

                    <info>php %command.full_name%</info>

                Find all components within a specific directory in templates by specifying the directory name with the <info>--dir</info> option:

                    <info>php %command.full_name% --dir=bar/foo</info>

                EOF
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $name = $input->getArgument('name');
        $componentsDir = $input->getOption('dir');

        if (null !== $name) {
            try {
                $metadata = $this->componentFactory->metadataFor($name);
            } catch (\Exception $e) {
                $io->error($e->getMessage());

                return Command::FAILURE;
            }

            $class = $metadata->get('class');
            $live = null;
            $allProperties = [];

            if ($class) {
                if ($metadata->get('live')) {
                    $live = 'X';
                }

                $reflectionClass = new \ReflectionClass($class);
                $properties = $reflectionClass->getProperties();
                $allLiveProperties = [];

                foreach ($properties as $property) {
                    if ($property->isPublic()) {
                        $visibility = $property->getType()?->getName();
                        $propertyName = $property->getName();
                        $value = $property->getDefaultValue();
                        $propertyAttributes = $property->getAttributes(LiveProp::class);

                        $propertyDisplay = $visibility.' $'.$propertyName.(null !== $value ? ' = '.$value : '');

                        if (\count($propertyAttributes) > 0) {
                            $allLiveProperties[] = $propertyDisplay;
                        } else {
                            $allProperties[] = $propertyDisplay;
                        }
                    }
                }

                $methods = $reflectionClass->getMethods();
                $allEvents = [];
                $allActions = [];

                foreach ($methods as $method) {
                    if ('mount' === $method->getName()) {
                        $allEvents[] = 'Mount';
                    }

                    foreach ($method->getAttributes() as $attribute) {
                        if (PreMount::class === $attribute->getName()) {
                            $allEvents[] = 'PreMount';
                            break;
                        }

                        if (PostMount::class === $attribute->getName()) {
                            $allEvents[] = 'PostMount';
                            break;
                        }

                        if (LiveAction::class === $attribute->getName()) {
                            $allActions[] = $method->getName();
                            break;
                        }
                    }
                }
            } else {
                $allProperties = $this->getPropertiesForAnonymousComponent($metadata);
            }

            $componentInfos = [
                ['Component', $name],
                ['Live', $live],
                ['Class', $class ?? 'Anonymous component'],
                ['Template', $metadata->getTemplate()],
                ['Properties', \count($allProperties) > 0 ? implode("\n", $allProperties) : null],
            ];

            if (isset($allLiveProperties) && \count($allLiveProperties) > 0) {
                $componentInfos[] = ['Live Properties', implode("\n", $allLiveProperties)];
            }
            if (isset($allEvents) && \count($allEvents) > 0) {
                $componentInfos[] = ['Events', implode("\n", $allEvents)];
            }
            if (isset($allActions) && \count($allActions) > 0) {
                $componentInfos[] = ['LiveAction Methods', implode("\n", $allActions)];
            }

            $table = new Table($output);
            $table->setHeaders(['Property', 'Value'])->setRows($componentInfos);
            $table->render();

            return Command::SUCCESS;
        }

        $finderTemplates = new Finder();
        $finderTemplates->files()->in("{$this->twigTemplatesPath}/components");

        $anonymousTemplatesComponents = [];
        foreach ($finderTemplates as $template) {
            $anonymousTemplatesComponents[] = $template->getRelativePathname();
        }

        $componentsWithClass = [];
        foreach ($this->components as $class) {
            $reflectionClass = new \ReflectionClass($class);
            $attributes = $reflectionClass->getAttributes();

            foreach ($attributes as $attribute) {
                $arguments = $attribute->getArguments();

                $name = $arguments['name'] ?? $arguments[0] ?? null;
                $template = $arguments['template'] ?? $arguments[1] ?? null;

                if (null !== $template || null !== $name) {
                    if (null !== $template && null !== $name) {
                        $templateFile = str_replace('components/', '', $template);
                        $metadata = $this->componentFactory->metadataFor($name);
                    } elseif (null !== $name) {
                        $templateFile = str_replace(':', '/', "{$name}.html.twig");
                        $metadata = $this->componentFactory->metadataFor($name);
                    } else {
                        $templateFile = str_replace('components/', '', $template);
                        $metadata = $this->componentFactory->metadataFor(str_replace('.html.twig', '', $templateFile));
                    }
                } else {
                    $templateFile = "{$reflectionClass->getShortName()}.html.twig";
                    $metadata = $this->componentFactory->metadataFor($reflectionClass->getShortName());
                }

                $componentsWithClass[] = [
                    'name' => $metadata->getName(),
                    'live' => null !== $metadata->get('live') ? 'X' : null,
                ];

                if (($key = array_search($templateFile, $anonymousTemplatesComponents)) !== false) {
                    unset($anonymousTemplatesComponents[$key]);
                }
            }
        }

        $anonymousComponents = array_map(fn ($template): array => [
            'name' => str_replace('/', ':', str_replace('.html.twig', '', $template)),
            'live' => null,
        ], $anonymousTemplatesComponents);

        $allComponents = array_merge($componentsWithClass, $anonymousComponents);
        $dataToRender = [];
        foreach ($allComponents as $component) {
            $metadata = $this->componentFactory->metadataFor($component['name']);

            if (str_contains($metadata->getTemplate(), $componentsDir)) {
                $dataToRender[] = [
                    $metadata->getName(),
                    $metadata->get('class') ?? 'Anonymous component',
                    $metadata->getTemplate(),
                    $component['live'],
                ];
            }
        }

        $table = new Table($output);
        $table->setHeaders(['Component', 'Class', 'Template', 'Live'])->setRows($dataToRender);
        $table->render();

        return Command::SUCCESS;
    }

    private function getPropertiesForAnonymousComponent(ComponentMetadata $metadata): array
    {
        $allProperties = [];

        $source = $this->twigEnvironment->load($metadata->getTemplate())->getSourceContext();
        $tokenStream = $this->twigEnvironment->tokenize($source);
        $bodyNode = $this->twigEnvironment->parse($tokenStream)->getNode('body')->getNode(0);

        $propsNode = [];

        foreach ($bodyNode as $node) {
            if ($node instanceof PropsNode) {
                $propsNode = $node;
                break;
            }
        }

        if (\count($propsNode) > 0) {
            $allVariables = $propsNode->getAttribute('names');

            foreach ($allVariables as $variable) {
                if ($propsNode->hasNode($variable)) {
                    $value = $propsNode->getNode($variable)->getAttribute('value');

                    if (\is_bool($value)) {
                        $value = $value ? 'true' : 'false';
                    }

                    $property = $variable.' = '.$value;
                } else {
                    $property = $variable;
                }

                $allProperties[] = $property;
            }
        }

        return $allProperties;
    }
}
