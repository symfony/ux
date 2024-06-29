<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Twig;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\LiveComponent\LiveComponentHydrator;
use Symfony\UX\LiveComponent\Metadata\LiveComponentMetadataFactory;
use Symfony\UX\StimulusBundle\Helper\StimulusHelper;
use Symfony\UX\TwigComponent\ComponentFactory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class LiveComponentRuntime
{
    public function __construct(
        private LiveComponentHydrator $hydrator,
        private ComponentFactory $factory,
        private UrlGeneratorInterface $urlGenerator,
        private LiveComponentMetadataFactory $metadataFactory,
        private StimulusHelper $stimulusHelper,
    ) {
    }

    public function getComponentUrl(string $name, array $props = []): string
    {
        $mounted = $this->factory->create($name, $props);
        $props = $this->hydrator->dehydrate(
            $mounted->getComponent(),
            $mounted->getAttributes(),
            $this->metadataFactory->getMetadata($mounted->getName())
        );
        $params = ['_live_component' => $name] + ['props' => json_encode($props->getProps())];

        $metadata = $this->factory->metadataFor($mounted->getName());

        return $this->urlGenerator->generate($metadata->get('route'), $params, $metadata->get('url_reference_type'));
    }

    public function liveAction(string $actionName, array $parameters = [], array $modifiers = [], ?string $event = null): string
    {
        $attributes = $this->stimulusHelper->createStimulusAttributes();

        $modifiers = array_map(static function (string $key, mixed $value) {
            return $value ? \sprintf('%s(%s)', $key, $value) : $key;
        }, array_keys($modifiers), array_values($modifiers));

        $parts = explode(':', $actionName);

        $parameters['action'] = $modifiers ? \sprintf('%s|%s', implode('|', $modifiers), $parts[0]) : $parts[0];

        array_shift($parts);

        $name = 'action';

        if (\count($parts) > 0) {
            $name .= ':'.implode(':', $parts);
        }

        $attributes->addAction('live', $name, $event, $parameters);

        return (string) $attributes;
    }
}
