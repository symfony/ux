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
use Twig\Environment;

class TwigEnvironmentConfigurator
{
    private EnvironmentConfigurator $decorated;

    public function __construct(
        EnvironmentConfigurator $decorated,
    ) {
        $this->decorated = $decorated;
    }

    public function configure(Environment $environment): void
    {
        $this->decorated->configure($environment);

        $environment->setLexer(new ComponentLexer($environment));
    }
}
