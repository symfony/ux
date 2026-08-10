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

use Symfony\UX\Toolkit\Recipe\Recipe;
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
    /**
     * @var array<string, ComponentTemplateFinderInterface>
     */
    private static $componentTemplateFinders = [];

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
    public function runForKit(Kit $kit, callable $callback, ?Recipe $prioritizedRecipe = null): mixed
    {
        $resetTwig = $this->contextualizeTwig($kit, $prioritizedRecipe);
        $resetComponentFactory = $this->contextualizeComponentFactory($kit, $prioritizedRecipe);

        try {
            return $callback($kit);
        } finally {
            $resetTwig();
            $resetComponentFactory();
        }
    }

    /**
     * @return callable(): void
     */
    private function contextualizeTwig(Kit $kit, ?Recipe $prioritizedRecipe): callable
    {
        $initialTwigLoader = $this->twig->getLoader();

        $loaders = [];
        if (null !== $prioritizedRecipe) {
            $loaders[] = new FilesystemLoader($prioritizedRecipe->absolutePath);
        }
        foreach ($kit->getRecipes() as $recipe) {
            if (null !== $prioritizedRecipe && $recipe->name === $prioritizedRecipe->name) {
                continue;
            }
            $loaders[] = new FilesystemLoader($recipe->absolutePath);
        }
        $loaders[] = $initialTwigLoader;

        $this->twig->setLoader(new ChainLoader($loaders));

        return fn () => $this->twig->setLoader($initialTwigLoader);
    }

    /**
     * @return callable(): void
     */
    private function contextualizeComponentFactory(Kit $kit, ?Recipe $prioritizedRecipe): callable
    {
        $reflComponentFactory = new \ReflectionClass($this->componentFactory);

        $reflConfig = $reflComponentFactory->getProperty('config');
        $initialConfig = $reflConfig->getValue($this->componentFactory);
        $reflConfig->setValue($this->componentFactory, []);

        $reflComponentTemplateFinder = $reflComponentFactory->getProperty('componentTemplateFinder');
        $initialComponentTemplateFinder = $reflComponentTemplateFinder->getValue($this->componentFactory);
        $reflComponentTemplateFinder->setValue($this->componentFactory, $this->createComponentTemplateFinder($kit, $prioritizedRecipe));

        return function () use ($reflConfig, $initialConfig, $reflComponentTemplateFinder, $initialComponentTemplateFinder): void {
            $reflConfig->setValue($this->componentFactory, $initialConfig);
            $reflComponentTemplateFinder->setValue($this->componentFactory, $initialComponentTemplateFinder);
        };
    }

    private function createComponentTemplateFinder(Kit $kit, ?Recipe $prioritizedRecipe): ComponentTemplateFinderInterface
    {
        $cacheKey = $kit->manifest->name.($prioritizedRecipe ? '/'.$prioritizedRecipe->name : '');

        return self::$componentTemplateFinders[$cacheKey] ??= new class($kit, $prioritizedRecipe) implements ComponentTemplateFinderInterface {
            public function __construct(
                private readonly Kit $kit,
                private readonly ?Recipe $prioritizedRecipe,
            ) {
            }

            public function findAnonymousComponentTemplate(string $name): ?string
            {
                $templateSuffix = 'templates/components/'.str_replace(':', '/', $name).'.html.twig';

                if (null !== $this->prioritizedRecipe) {
                    foreach ($this->prioritizedRecipe->getFiles() as $file) {
                        if (str_ends_with($file->sourceRelativePathName, $templateSuffix)) {
                            return $file->sourceRelativePathName;
                        }
                    }
                }

                foreach ($this->kit->getRecipes() as $recipe) {
                    if (null !== $this->prioritizedRecipe && $recipe->name === $this->prioritizedRecipe->name) {
                        continue;
                    }
                    foreach ($recipe->getFiles() as $file) {
                        if (str_ends_with($file->sourceRelativePathName, $templateSuffix)) {
                            return $file->sourceRelativePathName;
                        }
                    }
                }

                throw new \LogicException(\sprintf('No Twig files found for component "%s" in kit "%s", it should not happens.', $name, $this->kit->manifest->name));
            }
        };
    }
}
