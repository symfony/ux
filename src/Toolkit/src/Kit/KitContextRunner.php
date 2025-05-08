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

use Symfony\Component\Filesystem\Path;
use Symfony\UX\Toolkit\File\FileType;
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
        $this->twig->setLoader(new ChainLoader([
            new FilesystemLoader(Path::join($kit->path, 'templates/components')),
            $initialTwigLoader,
        ]));

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

        return $instances[$kit->name] ?? new class($kit) implements ComponentTemplateFinderInterface {
            public function __construct(private readonly Kit $kit)
            {
            }

            public function findAnonymousComponentTemplate(string $name): ?string
            {
                if (null === $component = $this->kit->getComponent($name)) {
                    throw new \RuntimeException(\sprintf('Component "%s" does not exist in kit "%s".', $name, $this->kit->name));
                }

                foreach ($component->files as $file) {
                    if (FileType::Twig === $file->type) {
                        return $file->relativePathName;
                    }
                }

                throw new \LogicException(\sprintf('No Twig files found for component "%s" in kit "%s", it should not happens.', $name, $this->kit->name));
            }
        };
    }
}
