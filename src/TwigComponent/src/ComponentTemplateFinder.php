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

/**
 * @author Matheo Daninos <matheo.daninos@gmail.com>
 */
final class ComponentTemplateFinder implements ComponentTemplateFinderInterface
{
    public function __construct(
        private Environment $environment,
        private readonly ?string $directory = null,
    ) {
        if (null === $this->directory) {
            trigger_deprecation('symfony/ux-twig-component', '2.13', 'The "%s()" method will require "string $directory" argument in 3.0. Not defining it or passing null is deprecated.', __METHOD__);
        }
    }

    public function findAnonymousComponentTemplate(string $name): ?string
    {
        $loader = $this->environment->getLoader();
        $componentPath = rtrim(str_replace(':', '/', $name));

        // Legacy auto-naming rules < 2.13
        if (null === $this->directory) {
            if ($loader->exists('components/'.$componentPath.'.html.twig')) {
                return 'components/'.$componentPath.'.html.twig';
            }

            if ($loader->exists($componentPath.'.html.twig')) {
                return $componentPath.'.html.twig';
            }

            if ($loader->exists('components/'.$componentPath)) {
                return 'components/'.$componentPath;
            }

            if ($loader->exists($componentPath)) {
                return $componentPath;
            }

            return null;
        }

        $template = rtrim($this->directory, '/').'/'.$componentPath.'.html.twig';
        if ($loader->exists($template)) {
            return $template;
        }

        return null;
    }
}
