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
final class AnonymousAnonymousComponentTemplateParser implements AnonymousComponentTemplateParserInterface
{
    public function __construct(
        private Environment $environment
    ) {
    }

    public function findAnonymousComponentTemplate(string $name): ?string
    {
        $loader = $this->environment->getLoader();
        $componentPath = rtrim(str_replace(':', '/', $name));

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

    public function findComponentProps(string $name): array
    {
        $loader = $this->environment->getLoader();
        $templateContent = $loader->getSourceContext($this->findAnonymousComponentTemplate($name))->getCode();

        $pattern = '/{%\s*props\s*(.*?)\s*%}/';
        preg_match($pattern, $templateContent, $matches);

        if (isset($matches[1])) {
            $props = $matches[1];
            $props = preg_replace('/\s/', '', $props);
            $propsArray = explode(',', $props);

            return array_map(fn (string $propName) => strtok($propName, '='), $propsArray);
        }

        return [];
    }
}
