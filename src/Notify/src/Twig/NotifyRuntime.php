<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Notify\Twig;

use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\ProtocolVersion;
use Symfony\UX\StimulusBundle\Helper\StimulusHelper;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
final class NotifyRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private HubInterface $hub,
        private StimulusHelper $stimulusHelper,
    ) {
    }

    public function renderStreamNotifications(array|string $topics = [], array $options = []): string
    {
        $topics = [] === $topics ? ['https://symfony.com/notifier'] : (array) $topics;

        $controllers = [];
        if (null !== ($customController = $options['data-controller'] ?? null)) {
            $controllers[$customController] = [];
        }
        $values = ['topics' => $topics, 'hub' => $this->hub->getPublicUrl()];

        // The controller subscribes with the "topic" query parameter of the protocol 0.x, and with "match" on a 1.0 hub.
        if ($this->speaksProtocolV1()) {
            $values['protocolVersion'] = ProtocolVersion::V1->value;
        }

        $controllers['@symfony/ux-notify/notify'] = $values;

        $stimulusAttributes = $this->stimulusHelper->createStimulusAttributes();
        foreach ($controllers as $name => $controllerValues) {
            $stimulusAttributes->addController($name, $controllerValues);
        }

        return trim(\sprintf('<div %s></div>', $stimulusAttributes));
    }

    private function speaksProtocolV1(): bool
    {
        return enum_exists(ProtocolVersion::class)
            && ProtocolVersion::V1 === $this->hub->getProtocolVersion();
    }
}
