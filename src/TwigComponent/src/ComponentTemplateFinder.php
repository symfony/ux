<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent;

use Twig\Environment;
use Twig\Loader\LoaderInterface;

/**
 * @author Matheo Daninos <matheo.daninos@gmail.com>
 */
final class ComponentTemplateFinder implements ComponentTemplateFinderInterface
{
    private readonly LoaderInterface $loader;

    public function __construct(
        Environment|LoaderInterface $loader,
        private readonly ?string $directory = null,
    ) {
        if ($loader instanceof Environment) {
            trigger_deprecation('symfony/ux-twig-component', '2.13', 'The "%s()" method will require "%s $loader" as first argument in 3.0. Passing an "Environment" instance is deprecated.', __METHOD__, LoaderInterface::class);
            $loader = $loader->getLoader();
        }
        $this->loader = $loader;
        if (null === $this->directory) {
            trigger_deprecation('symfony/ux-twig-component', '2.13', 'The "%s()" method will require "string $directory" argument in 3.0. Not defining it or passing null is deprecated.', __METHOD__);
        }
    }

    public function findAnonymousComponentTemplate(string $name): ?string
    {
        $loader = $this->loader;
        $componentPath = rtrim(str_replace(':', '/', $name));

        // Legacy auto-naming rules < 2.13
        if (null === $this->directory) {
            foreach ([
                'components/'.$componentPath.'.html.twig',
                'components/'.$componentPath.'.twig',
                $componentPath.'.html.twig',
                $componentPath.'.twig',
                'components/'.$componentPath,
                $componentPath,
            ] as $path) {
                if ($loader->exists($path)) {
                    return $path;
                }
            }

            return null;
        }

        foreach (['.html.twig', '.twig'] as $extension) {
            $template = rtrim($this->directory, '/').'/'.$componentPath.$extension;
            if ($loader->exists($template)) {
                return $template;
            }
        }

        return null;
    }
}
