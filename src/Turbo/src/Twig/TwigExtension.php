<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Turbo\Twig;

use Psr\Container\ContainerInterface;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @author Kévin Dunglas <kevin@dunglas.fr>
 */
final class TwigExtension extends AbstractExtension
{
    private $turboStreamListenRenderers;
    private $default;

    public function __construct(ContainerInterface $turboStreamListenRenderers, string $default)
    {
        $this->turboStreamListenRenderers = $turboStreamListenRenderers;
        $this->default = $default;
    }

    public function getFunctions(): iterable
    {
        yield new TwigFunction('turbo_stream_listen', [$this, 'turboStreamListen'], ['needs_environment' => true, 'is_safe' => ['html']]);
    }

    /**
     * @param object|string $topic
     */
    public function turboStreamListen(Environment $env, $topic, ?string $transport = null): string
    {
        $transport = $transport ?? $this->default;

        if (!$this->turboStreamListenRenderers->has($transport)) {
            throw new \InvalidArgumentException(sprintf('The Turbo stream transport "%s" doesn\'t exist.', $transport));
        }

        return $this->turboStreamListenRenderers->get($transport)->renderTurboStreamListen($env, $topic);
    }
}
