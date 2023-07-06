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
class ComponentTemplateFinder implements ComponentTemplateFinderInterface
{
    public function __construct(
        private Environment $environment
    ) {
    }

    public function findAnonymousComponentTemplate(string $name): ?string
    {
        $loader = $this->environment->getLoader();
        $componentPath = rtrim(str_replace(':', '/', $name));

        if ($loader->exists($componentPath)) {
            return $componentPath;
        }

        if ($loader->exists($componentPath.'.html.twig')) {
            return $componentPath.'.html.twig';
        }

        if ($loader->exists('components/'.$componentPath)) {
            return 'components/'.$componentPath;
        }

        if ($loader->exists('components/'.$componentPath.'.html.twig')) {
            return 'components/'.$componentPath.'.html.twig';
        }

        return null;
    }
}
