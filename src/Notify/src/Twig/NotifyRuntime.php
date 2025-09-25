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
        $controllers['@symfony/ux-notify/notify'] = ['topics' => $topics, 'hub' => $this->hub->getPublicUrl()];

        $stimulusAttributes = $this->stimulusHelper->createStimulusAttributes();
        foreach ($controllers as $name => $controllerValues) {
            $stimulusAttributes->addController($name, $controllerValues);
        }

        return trim(\sprintf('<div %s></div>', $stimulusAttributes));
    }
}
