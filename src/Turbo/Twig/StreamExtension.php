<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Kévin Dunglas <kevin@dunglas.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Turbo\Twig;

use Symfony\WebpackEncoreBundle\Twig\StimulusTwigExtension;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig helpers to generated the Turbo Stream elements.
 *
 * @author Kévin Dunglas <kevin@dunglas.fr>
 *
 * @experimental
 */
final class StreamExtension extends AbstractExtension
{
    private $stimulusTwigExtension;
    private $mercureHub;

    public function __construct(StimulusTwigExtension $stimulusTwigExtension, ?string $mercureHub = null)
    {
        $this->stimulusTwigExtension = $stimulusTwigExtension;
        $this->mercureHub = $mercureHub;
    }

    public function getFunctions(): iterable
    {
        yield new TwigFunction('turbo_stream_listen', [$this, 'turboStreamListen'], ['needs_environment' => true, 'is_safe' => ['html']]);
    }

    public function turboStreamListen(Environment $env, string $topic): string
    {
        if (null === $this->mercureHub) {
            throw new \RuntimeException('The "turbo.mercure.subscribe_url" configuration key must be set to use "turbo_stream_listen()".');
        }

        return $this->stimulusTwigExtension->renderStimulusController($env, 'symfony/ux-turbo/turbo-stream-mercure', ['topic' => $topic, 'hub' => $this->mercureHub]);
    }
}
