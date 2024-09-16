<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\Twig;

use Symfony\Bundle\TwigBundle\DependencyInjection\Configurator\EnvironmentConfigurator;
use Symfony\UX\TwigComponent\ComponentAttributes;
use Twig\Environment;
use Twig\Extension\EscaperExtension;
use Twig\Runtime\EscaperRuntime;

/**
 * @final
 */
class TwigEnvironmentConfigurator
{
    public function __construct(
        private readonly EnvironmentConfigurator $decorated,
    ) {
    }

    public function configure(Environment $environment): void
    {
        $this->decorated->configure($environment);

        $environment->setLexer(new ComponentLexer($environment));

        if (class_exists(EscaperRuntime::class)) {
            $environment->getRuntime(EscaperRuntime::class)->addSafeClass(ComponentAttributes::class, ['html']);
        } elseif ($environment->hasExtension(EscaperExtension::class)) {
            $environment->getExtension(EscaperExtension::class)->addSafeClass(ComponentAttributes::class, ['html']);
        }
    }
}
