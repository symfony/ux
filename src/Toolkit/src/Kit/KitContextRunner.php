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
    public function runForKit(Kit $kit, callable $callback): mixed
    {
        $resetTwig = $this->contextualizeTwig($kit);
        $resetComponentFactory = $this->contextualizeComponentFactory($kit);

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
    private function contextualizeTwig(Kit $kit): callable
    {
        $initialTwigLoader = $this->twig->getLoader();

        $loaders = [];
        foreach ($kit->getRecipes(type: RecipeType::Component) as $recipe) {
            $loaders[] = new FilesystemLoader($recipe->absolutePath);
        }
        $loaders[] = $initialTwigLoader;

        $this->twig->setLoader(new ChainLoader($loaders));

        return fn () => $this->twig->setLoader($initialTwigLoader);
    }

    /**
     * @return callable(): void
     */
    private function contextualizeComponentFactory(Kit $kit): callable
    {
        $reflComponentFactory = new \ReflectionClass($this->componentFactory);

        $reflConfig = $reflComponentFactory->getProperty('config');
        $initialConfig = $reflConfig->getValue($this->componentFactory);
        $reflConfig->setValue($this->componentFactory, []);

        $reflComponentTemplateFinder = $reflComponentFactory->getProperty('componentTemplateFinder');
        $initialComponentTemplateFinder = $reflComponentTemplateFinder->getValue($this->componentFactory);
        $reflComponentTemplateFinder->setValue($this->componentFactory, $this->createComponentTemplateFinder($kit));

        return function () use ($reflConfig, $initialConfig, $reflComponentTemplateFinder, $initialComponentTemplateFinder): void {
            $reflConfig->setValue($this->componentFactory, $initialConfig);
            $reflComponentTemplateFinder->setValue($this->componentFactory, $initialComponentTemplateFinder);
        };
    }

    private function createComponentTemplateFinder(Kit $kit): ComponentTemplateFinderInterface
    {
        return self::$componentTemplateFinders[$kit->manifest->name] ??= new class($kit) implements ComponentTemplateFinderInterface {
            public function __construct(private readonly Kit $kit)
            {
            }

            public function findAnonymousComponentTemplate(string $name): ?string
            {
                foreach ($this->kit->getRecipes(type: RecipeType::Component) as $recipe) {
                    foreach ($recipe->getFiles() as $file) {
                        if (str_ends_with($file->sourceRelativePathName, 'templates/components/'.str_replace(':', '/', $name).'.html.twig')) {
                            return $file->sourceRelativePathName;
                        }
                    }
                }

                throw new \LogicException(\sprintf('No Twig files found for component "%s" in kit "%s", it should not happens.', $name, $this->kit->manifest->name));
            }
        };
    }
}
