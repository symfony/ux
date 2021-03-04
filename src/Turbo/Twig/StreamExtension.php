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

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\UX\Turbo\Broadcaster\TwigMercureBroadcaster;
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
    private $propertyAccessor;
    private $mercureHub;

    public function __construct(StimulusTwigExtension $stimulusTwigExtension, ?PropertyAccessorInterface $propertyAccessor, ?string $mercureHub = null)
    {
        $this->stimulusTwigExtension = $stimulusTwigExtension;
        $this->propertyAccessor = $propertyAccessor ?? PropertyAccess::createPropertyAccessor();
        $this->mercureHub = $mercureHub;
    }

    public function getFunctions(): iterable
    {
        yield new TwigFunction('turbo_stream_listen', [$this, 'turboStreamListen'], ['needs_environment' => true, 'is_safe' => ['html']]);
    }

    /**
     * @param string|object $topic
     */
    public function turboStreamListen(Environment $env, $topic): string
    {
        if (null === $this->mercureHub) {
            throw new \RuntimeException('The "turbo.mercure.subscribe_url" configuration key must be set to use "turbo_stream_listen()".');
        }

        if (\is_object($topic)) {
            $topic = sprintf(TwigMercureBroadcaster::TOPIC_PATTERN, rawurlencode(\get_class($topic)), rawurlencode($this->propertyAccessor->getValue($topic, 'id')));
        } elseif (class_exists($topic)) {
            // Generate a URI template to subscribe to updates for all objects of this class
            $topic = sprintf(TwigMercureBroadcaster::TOPIC_PATTERN, rawurlencode($topic), '{id}');
        }

        return $this->stimulusTwigExtension->renderStimulusController($env, 'symfony/ux-turbo/turbo-stream-mercure', ['topic' => $topic, 'hub' => $this->mercureHub]);
    }
}
