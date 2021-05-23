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

use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @final
 * @experimental
 */
class NotifyExtension extends AbstractExtension
{
    private $defaultHubUrl;

    public function __construct(string $defaultHubUrl)
    {
        $this->defaultHubUrl = $defaultHubUrl;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('stream_notifications', [$this, 'streamNotifications'], ['needs_environment' => true, 'is_safe' => ['html']]),
        ];
    }

    /**
     * @param array|string $topics
     */
    public function streamNotifications(Environment $environment, $topics = [], ?string $hubUrl = null): string
    {
        if (!\is_array($topics) && !\is_string($topics)) {
            throw new \TypeError(sprintf('"%s()" expects parameter 1 to be an array of strings or a string, "%s" given.', __METHOD__, get_debug_type($topics)));
        }

        $topics = [] === $topics ? ['https://symfony.com/notifier'] : (array) $topics;
        $hubUrl = $hubUrl ?: $this->defaultHubUrl;

        $html = '
            <div
                style="display: none"
                data-controller="symfony--ux-notify--notify"
                data-symfony--ux-notify--notify-topics-value="'.twig_escape_filter($environment, json_encode($topics)).'"
                data-symfony--ux-notify--notify-hub-value="'.twig_escape_filter($environment, $hubUrl).'"
            ></div>
        ';

        return trim($html);
    }
}
