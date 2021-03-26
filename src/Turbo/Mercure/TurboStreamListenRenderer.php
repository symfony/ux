<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Turbo\Mercure;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\UX\Turbo\Twig\TurboStreamListenRendererInterface;
use Symfony\WebpackEncoreBundle\Twig\StimulusTwigExtension;
use Twig\Environment;

/**
 * Renders the attributes to load the "turbo-stream-mercure" controller.
 *
 * @author Kévin Dunglas <kevin@dunglas.fr>
 */
final class TurboStreamListenRenderer implements TurboStreamListenRendererInterface
{
    private $hub;
    private $stimulusTwigExtension;
    private $propertyAccessor;
    private $doctrine;

    public function __construct(HubInterface $hub, StimulusTwigExtension $stimulusTwigExtension, PropertyAccessorInterface $propertyAccessor = null, ManagerRegistry $doctrine = null)
    {
        $this->hub = $hub;
        $this->stimulusTwigExtension = $stimulusTwigExtension;
        $this->propertyAccessor = $propertyAccessor ?? (class_exists(PropertyAccess::class) ? PropertyAccess::createPropertyAccessor() : null);
        $this->doctrine = $doctrine;
    }

    public function renderTurboStreamListen(Environment $env, $topic): string
    {
        if (\is_object($topic)) {
            $class = \get_class($topic);

            if ($this->doctrine && $em = $this->doctrine->getManagerForClass($class)) {
                $id = implode('-', $em->getClassMetadata($class)->getIdentifierValues($topic));
            } elseif ($this->propertyAccessor) {
                $id = $this->propertyAccessor->getValue($topic, 'id');
            } else {
                throw new \LogicException(sprintf('Cannot listen to entity of class "%s" as the PropertyAccess component is not installed. Try running "composer require symfony/property-access".', $class));
            }

            $topic = sprintf(Broadcaster::TOPIC_PATTERN, rawurlencode($class), rawurlencode($id));
        } elseif (!preg_match('/[^a-zA-Z0-9_\x7f-\xff\\\\]/', $topic) && class_exists($topic)) {
            // Generate a URI template to subscribe to updates for all objects of this class
            $topic = sprintf(Broadcaster::TOPIC_PATTERN, rawurlencode($topic), '{id}');
        }

        return $this->stimulusTwigExtension->renderStimulusController(
            $env,
            'symfony/ux-turbo/turbo-stream-mercure',
            ['topic' => $topic, 'hub' => $this->hub->getPublicUrl()]
        );
    }
}
