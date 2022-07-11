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
use Symfony\WebpackEncoreBundle\Dto\StimulusControllersDto;
use Symfony\WebpackEncoreBundle\Twig\StimulusTwigExtension;
use Twig\Environment;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental
 */
final class NotifyRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private HubInterface $hub,
        private StimulusTwigExtension $stimulusTwigExtension,
    ) {
    }

    /**
     * @param array|string $topics
     */
    public function renderStreamNotifications(Environment $environment, $topics = [], array $options = []): string
    {
        $topics = [] === $topics ? ['https://symfony.com/notifier'] : (array) $topics;

        $controllers = [];
        if (null !== ($customController = $options['data-controller'] ?? null)) {
            $controllers[$customController] = [];
        }
        $controllers['@symfony/ux-notify/notify'] = ['topics' => $topics, 'hub' => $this->hub->getPublicUrl()];

        if (class_exists(StimulusControllersDto::class)) {
            $dto = new StimulusControllersDto($environment);
            foreach ($controllers as $name => $controllerValues) {
                $dto->addController($name, $controllerValues);
            }
            $html = (string) $dto;
        } else {
            $html = $this->stimulusTwigExtension->renderStimulusController($environment, $controllers);
        }

        return trim(sprintf('<div %s></div>', $html));
    }
}
