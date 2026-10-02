<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Turbo\Bridge\Mercure;

use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Twig\MercureExtension;
use Symfony\UX\StimulusBundle\Helper\StimulusHelper;
use Symfony\UX\Turbo\Broadcaster\IdAccessor;
use Symfony\UX\Turbo\Twig\TurboStreamListenRendererInterface;
use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\AbstractExtension;

/**
 * Renders the attributes to load the "mercure-turbo-stream" controller.
 *
 * @author Kévin Dunglas <kevin@dunglas.fr>
 *
 * @deprecated since Symfony UX 3.1, use {@see MercureStreamSourceRenderer} with turbo_stream_from() or the <twig:Turbo:Stream:From> Twig component instead. Will be removed in 4.0.
 */
final class TurboStreamListenRenderer implements TurboStreamListenRendererInterface
{
    private readonly TopicResolver $topicResolver;

    public function __construct(
        private HubInterface $hub,
        private StimulusHelper $stimulusHelper,
        IdAccessor $idAccessor,
        private Environment $twig,
        private ?string $hubName = null,
    ) {
        $this->topicResolver = new TopicResolver($idAccessor);
    }

    public function renderTurboStreamListen(Environment $env, $topic, array $eventSourceOptions = []): string
    {
        $topicList = $topic instanceof TopicSet ? array_values($topic->getTopics()) : [$topic];

        // The Stimulus controller subscribes with the "topic" query parameter of the protocol 0.x.
        if (TopicResolver::speaksProtocolV1($this->hub)) {
            throw new \LogicException(\sprintf('The deprecated turbo_stream_listen() function does not support the Mercure protocol 1.0 spoken by the "%s" hub. Use turbo_stream_from() or the <twig:Turbo:Stream:From> Twig component instead.', $this->hubName ?? 'default'));
        }

        $topics = $this->topicResolver->resolveForProtocolV0($topicList);
        $controllerAttributes = ['hub' => $this->hub->getPublicUrl()];
        if (1 < \count($topics)) {
            $controllerAttributes['topics'] = $topics;
        } else {
            $controllerAttributes['topic'] = current($topics);
        }

        if ([] !== $eventSourceOptions) {
            try {
                // Mercure >= 0.7: https://github.com/symfony/mercure/pull/123
                /* @phpstan-ignore-next-line function.alreadyNarrowedType */
                $mercure = is_subclass_of(MercureExtension::class, AbstractExtension::class)
                    ? $this->twig->getExtension(MercureExtension::class) /* @phpstan-ignore argument.templateType */
                    : $this->twig->getRuntime(MercureExtension::class);

                if ($eventSourceOptions['withCredentials'] ?? false) {
                    $eventSourceOptions['subscribe'] ??= $topics;
                    $controllerAttributes['withCredentials'] = true;
                }

                $mercure->mercure($topics, $eventSourceOptions);
            } catch (RuntimeError $e) {
            }
        }

        $stimulusAttributes = $this->stimulusHelper->createStimulusAttributes();
        $stimulusAttributes->addController(
            'symfony/ux-turbo/mercure-turbo-stream',
            $controllerAttributes,
        );

        return (string) $stimulusAttributes;
    }
}
