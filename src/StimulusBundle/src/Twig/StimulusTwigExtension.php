<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\StimulusBundle\Twig;

use Symfony\UX\StimulusBundle\Dto\StimulusAttributes;
use Symfony\UX\StimulusBundle\Helper\StimulusHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * @author Ryan Weaver <ryan@symfonycasts.com>
 */
final class StimulusTwigExtension extends AbstractExtension
{
    public function __construct(private StimulusHelper $stimulusHelper)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('stimulus_controller', [$this, 'renderStimulusController'], ['is_safe' => ['html_attr']]),
            new TwigFunction('stimulus_action', [$this, 'renderStimulusAction'], ['is_safe' => ['html_attr']]),
            new TwigFunction('stimulus_target', [$this, 'renderStimulusTarget'], ['is_safe' => ['html_attr']]),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('stimulus_controller', [$this, 'appendStimulusController'], ['is_safe' => ['html_attr']]),
            new TwigFilter('stimulus_action', [$this, 'appendStimulusAction'], ['is_safe' => ['html_attr']]),
            new TwigFilter('stimulus_target', [$this, 'appendStimulusTarget'], ['is_safe' => ['html_attr']]),
        ];
    }

    /**
     * @param string $controllerName    the Stimulus controller name
     * @param array  $controllerValues  array of controller values
     * @param array  $controllerClasses array of controller CSS classes
     * @param array  $controllerOutlets array of controller outlets
     */
    public function renderStimulusController(string $controllerName, array $controllerValues = [], array $controllerClasses = [], array $controllerOutlets = []): StimulusAttributes
    {
        $stimulusAttributes = $this->stimulusHelper->createStimulusAttributes();
        $stimulusAttributes->addController($controllerName, $controllerValues, $controllerClasses, $controllerOutlets);

        return $stimulusAttributes;
    }

    public function appendStimulusController(StimulusAttributes $stimulusAttributes, string $controllerName, array $controllerValues = [], array $controllerClasses = [], array $controllerOutlets = []): StimulusAttributes
    {
        $stimulusAttributes->addController($controllerName, $controllerValues, $controllerClasses, $controllerOutlets);

        return $stimulusAttributes;
    }

    /**
     * @param array $parameters Parameters to pass to the action. Optional.
     */
    public function renderStimulusAction(string $controllerName, ?string $actionName = null, ?string $eventName = null, array $parameters = []): StimulusAttributes
    {
        $stimulusAttributes = $this->stimulusHelper->createStimulusAttributes();
        $stimulusAttributes->addAction($controllerName, $actionName, $eventName, $parameters);

        return $stimulusAttributes;
    }

    /**
     * @param array $parameters Parameters to pass to the action. Optional.
     */
    public function appendStimulusAction(StimulusAttributes $stimulusAttributes, string $controllerName, string $actionName, ?string $eventName = null, array $parameters = []): StimulusAttributes
    {
        $stimulusAttributes->addAction($controllerName, $actionName, $eventName, $parameters);

        return $stimulusAttributes;
    }

    /**
     * @param string      $controllerName the Stimulus controller name
     * @param string|null $targetNames    The space-separated list of target names if a string is passed to the 1st argument. Optional.
     */
    public function renderStimulusTarget(string $controllerName, ?string $targetNames = null): StimulusAttributes
    {
        $stimulusAttributes = $this->stimulusHelper->createStimulusAttributes();
        $stimulusAttributes->addTarget($controllerName, $targetNames);

        return $stimulusAttributes;
    }

    /**
     * @param string      $controllerName the Stimulus controller name
     * @param string|null $targetNames    The space-separated list of target names if a string is passed to the 1st argument. Optional.
     */
    public function appendStimulusTarget(StimulusAttributes $stimulusAttributes, string $controllerName, ?string $targetNames = null): StimulusAttributes
    {
        $stimulusAttributes->addTarget($controllerName, $targetNames);

        return $stimulusAttributes;
    }
}
