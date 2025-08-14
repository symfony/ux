<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Kit;

use Symfony\UX\Toolkit\Recipe\RecipeType;
use Symfony\UX\TwigComponent\ComponentFactory;
use Symfony\UX\TwigComponent\ComponentTemplateFinderInterface;
use Twig\Loader\ChainLoader;
use Twig\Loader\FilesystemLoader;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class KitContextRunner
{
    public function __construct(
        private readonly \Twig\Environment $twig,
        private readonly ComponentFactory $componentFactory,
    ) {
    }

    /**
     * @template TResult of mixed
     *
     * @param callable(Kit): TResult $callback
     *
     * @return TResult
     */
    public function runForKit(Kit $kit, callable $callback): mixed
    {
        $resetServices = $this->contextualizeServicesForKit($kit);

        try {
            return $callback($kit);
        } finally {
            $resetServices();
        }
    }

    /**
     * @return callable(): void Reset the services when called
     */
    private function contextualizeServicesForKit(Kit $kit): callable
    {
        // Configure Twig
        $initialTwigLoader = $this->twig->getLoader();
        $loaders = [];
        foreach ($kit->getRecipes(type: RecipeType::Component) as $recipe) {
            $loaders[] = new FilesystemLoader($recipe->absolutePath);
        }
        $this->twig->setLoader(new ChainLoader([...$loaders, $initialTwigLoader]));

        // Configure Twig Components
        $reflComponentFactory = new \ReflectionClass($this->componentFactory);

        $reflComponentFactoryConfig = $reflComponentFactory->getProperty('config');
        $initialComponentFactoryConfig = $reflComponentFactoryConfig->getValue($this->componentFactory);
        $reflComponentFactoryConfig->setValue($this->componentFactory, []);

        $reflComponentFactoryComponentTemplateFinder = $reflComponentFactory->getProperty('componentTemplateFinder');
        $initialComponentFactoryComponentTemplateFinder = $reflComponentFactoryComponentTemplateFinder->getValue($this->componentFactory);
        $reflComponentFactoryComponentTemplateFinder->setValue($this->componentFactory, $this->createComponentTemplateFinder($kit));

        return function () use ($initialTwigLoader, $reflComponentFactoryConfig, $initialComponentFactoryConfig, $reflComponentFactoryComponentTemplateFinder, $initialComponentFactoryComponentTemplateFinder) {
            $this->twig->setLoader($initialTwigLoader);
            $reflComponentFactoryConfig->setValue($this->componentFactory, $initialComponentFactoryConfig);
            $reflComponentFactoryComponentTemplateFinder->setValue($this->componentFactory, $initialComponentFactoryComponentTemplateFinder);
        };
    }

    private function createComponentTemplateFinder(Kit $kit): ComponentTemplateFinderInterface
    {
        static $instances = [];

        return $instances[$kit->manifest->name] ?? new class($kit) implements ComponentTemplateFinderInterface {
            public function __construct(private readonly Kit $kit)
            {
            }

            public function findAnonymousComponentTemplate(string $name): ?string
            {
                foreach ($this->kit->getRecipes(type: RecipeType::Component) as $recipe) {
                    foreach ($recipe->getFiles() as $file) {
                        if (str_ends_with($file->sourceRelativePathName, str_replace(':', '/', $name).'.html.twig')) {
                            return $file->sourceRelativePathName;
                        }
                    }
                }

                throw new \LogicException(\sprintf('No Twig files found for component "%s" in kit "%s", it should not happens.', $name, $this->kit->manifest->name));
            }
        };
    }
}
