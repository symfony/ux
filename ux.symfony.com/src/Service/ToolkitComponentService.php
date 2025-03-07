<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Service;

use Symfony\Component\DependencyInjection\Argument\ServiceLocator;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\UX\Toolkit\Registry\Registry;
use Symfony\UX\Toolkit\Registry\RegistryFactory;
use Symfony\UX\Toolkit\Registry\RegistryItem;
use Symfony\UX\Toolkit\Registry\RegistryItemType;
use Symfony\UX\TwigComponent\ComponentFactory;
use Symfony\UX\TwigComponent\ComponentProperties;
use Symfony\UX\TwigComponent\ComponentRenderer;
use Symfony\UX\TwigComponent\ComponentStack;
use Symfony\UX\TwigComponent\ComponentTemplateFinder;
use Symfony\UX\TwigComponent\DependencyInjection\TwigComponentExtension;
use Twig\Environment;

final class ToolkitComponentService
{
    public function __construct(
        private readonly RegistryFactory $registryFactory,
        private readonly Environment $twig,
        private readonly string $manifest = __DIR__ . '/../../vendor/symfony/ux-toolkit/registry/default',
    ) {}


    public function getRegistry(): Registry
    {
        return $this->registryFactory->create((new Finder())->files()->in($this->manifest));
    }

    public function get(string $componentName): ?RegistryItem
    {
        $registry = $this->getRegistry();

        foreach ($registry->all() as $component) {
            if ($component->name === $componentName && $component->type === RegistryItemType::Component) {
                return $component;
            }
        }
        
        return null;
    }

    private function getWorkdir(): string {

        // Put the code in a temporary file. We can image in the future use another way to pass the code to the component, 
        // but today the ComponentFactory need a folder
        // We do it only once, for all visitors
        $filesystem = new Filesystem();
        $workdir = sys_get_temp_dir() . '/uxcomponent';
        if (!$filesystem->exists($workdir)) {
            $filesystem->mkdir($workdir);

            // we put all components in this folder
            foreach ($this->getRegistry()->all() as $component) {
                $componentFile = $workdir . '/' . $component->name . '.twig';
                file_put_contents($componentFile, $component->code);
            }
        }

        return $workdir;
    }

    public function getComponentTwigPath(string $componentName): string
    {
        return $this->getWorkdir() . '/' . $componentName . '.twig';
    }

    /**
     * This method allow to render a dynamic component, without using the auto-wired TwigComponentExtension
     * 
     * Actually, the extension compile components. If we want to render a dynamic component, we need to do it manually
     * This should be improved in the future.
     * 
     * @param string $componentName
     * @return string
     */
    public function preview(string $componentName, RegistryItemType $type = RegistryItemType::Component): string
    {
        $registry = $this->getRegistry();
        if (!$registry->has($componentName, $type)) {
            throw new \InvalidArgumentException(sprintf('Component "%s" not found.', $componentName));
        }

        $currentComponentFromRegistry = $registry->get($componentName, $type);

        
        
        // Add this path to twig
        $loader = $this->twig->getLoader();
        $loader->addPath($this->getWorkdir());

        // Component finder use compiled values. We need to construct new one
        $componentFactory = new ComponentFactory(
            new ComponentTemplateFinder($this->twig),
            new ServiceLocator(function() {
                return [];
            }, []),
            new PropertyAccessor(),
            new EventDispatcher(),
            [],
            [],
        );

        if ($type === RegistryItemType::Component) {
            // Preview a component
            $mounted = $componentFactory->create($currentComponentFromRegistry->name);

            $componentProperties = new ComponentProperties(new PropertyAccessor());
            $componentStack = new ComponentStack();
    
            $renderer = new ComponentRenderer(
                $this->twig,
                new EventDispatcher(),
                $componentFactory,
                $componentProperties,
                $componentStack,
            );
    
            return $renderer->render($mounted);
        }

        // Preview an example
        return $this->twig->render($currentComponentFromRegistry->name . '.twig');
    }
}
