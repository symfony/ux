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
use Symfony\WebpackEncoreBundle\Twig\StimulusTwigExtension;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
final class NotifyRuntime implements RuntimeExtensionInterface
{
    private StimulusHelper $stimulusHelper;

    /**
     * @param $stimulus StimulusHelper
     */
    public function __construct(
        private HubInterface $hub,
        StimulusHelper|StimulusTwigExtension $stimulus,
    ) {
        if ($stimulus instanceof StimulusTwigExtension) {
            trigger_deprecation('symfony/ux-notify', '2.9', 'Passing an instance of "%s" to "%s" is deprecated, pass an instance of "%s" instead.', StimulusTwigExtension::class, __CLASS__, StimulusHelper::class);
            $stimulus = new StimulusHelper(null);
        }

        $this->stimulusHelper = $stimulus;
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
