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
use Symfony\UX\Turbo\Twig\TurboStreamListenRendererWithOptionsInterface;
use Symfony\WebpackEncoreBundle\Twig\StimulusTwigExtension;
use Twig\Environment;
use Twig\Error\RuntimeError;

/**
 * Renders the attributes to load the "mercure-turbo-stream" controller.
 *
 * @author Kévin Dunglas <kevin@dunglas.fr>
 */
final class TurboStreamListenRenderer implements TurboStreamListenRendererWithOptionsInterface
{
    private StimulusHelper $stimulusHelper;

    public function __construct(
        private HubInterface $hub,
        StimulusHelper|StimulusTwigExtension $stimulus,
        private IdAccessor $idAccessor,
        private Environment $twig,
    ) {
        if ($stimulus instanceof StimulusTwigExtension) {
            trigger_deprecation('symfony/ux-turbo', '2.9', 'Passing an instance of "%s" as second argument of "%s" is deprecated, pass an instance of "%s" instead.', StimulusTwigExtension::class, __CLASS__, StimulusHelper::class);

            $stimulus = new StimulusHelper(null);
        }

        /* @var StimulusHelper $stimulus */
        $this->stimulusHelper = $stimulus;
    }

    public function renderTurboStreamListen(Environment $env, $topic /* array $eventSourceOptions = [] */): string
    {
        if (\func_num_args() > 2) {
            $eventSourceOptions = func_get_arg(2);
        }

        $topics = $topic instanceof TopicSet
            ? array_map($this->resolveTopic(...), $topic->getTopics())
            : [$this->resolveTopic($topic)];

        $controllerAttributes = ['hub' => $this->hub->getPublicUrl()];
        if (1 < \count($topics)) {
            $controllerAttributes['topics'] = $topics;
        } else {
            $controllerAttributes['topic'] = current($topics);
        }

        if (isset($eventSourceOptions)) {
            try {
                $mercure = $this->twig->getExtension(MercureExtension::class);
                $mercure->mercure($topics, $eventSourceOptions);

                if (isset($eventSourceOptions['withCredentials'])) {
                    $controllerAttributes['withCredentials'] = $eventSourceOptions['withCredentials'];
                }
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

    private function resolveTopic(object|string $topic): string
    {
        if (\is_object($topic)) {
            $class = $topic::class;

            if (!$id = $this->idAccessor->getEntityId($topic)) {
                throw new \LogicException(\sprintf('Cannot listen to entity of class "%s" as the PropertyAccess component is not installed. Try running "composer require symfony/property-access".', $class));
            }

            return \sprintf(Broadcaster::TOPIC_PATTERN, rawurlencode($class), rawurlencode(implode('-', $id)));
        }

        if (!preg_match('/[^a-zA-Z0-9_\x7f-\xff\\\\]/', $topic) && class_exists($topic)) {
            // Generate a URI template to subscribe to updates for all objects of this class
            return \sprintf(Broadcaster::TOPIC_PATTERN, rawurlencode($topic), '{id}');
        }

        return $topic;
    }
}
