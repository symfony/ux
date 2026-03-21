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

use Twig\Loader\LoaderInterface;

/**
 * @author Matheo Daninos <matheo.daninos@gmail.com>
 */
final class ComponentTemplateFinder implements ComponentTemplateFinderInterface
{
    public function __construct(
        private readonly LoaderInterface $loader,
        private readonly string $directory,
    ) {
    }

    public function findAnonymousComponentTemplate(string $name): ?string
    {
        $componentPath = rtrim(str_replace(':', '/', $name));

        $template = rtrim($this->directory, '/').'/'.$componentPath.'.html.twig';
        if ($this->loader->exists($template)) {
            return $template;
        }

        $template = rtrim($this->directory, '/').'/'.$componentPath.'/index.html.twig';
        if ($this->loader->exists($template)) {
            return $template;
        }

        $parts = explode('/', $componentPath, 2);
        if (\count($parts) < 2) {
            return null;
        }

        $template = '@'.$parts[0].'/components/'.$parts[1].'.html.twig';
        if ($this->loader->exists($template)) {
            return $template;
        }

        $template = '@'.$parts[0].'/components/'.$parts[1].'/index.html.twig';
        if ($this->loader->exists($template)) {
            return $template;
        }

        return null;
    }
}
