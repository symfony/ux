<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\EventListener;

use Symfony\Contracts\EventDispatcher\Event;
use Symfony\UX\TwigComponent\ComponentMetadata;
use Symfony\UX\TwigComponent\MountedComponent;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @experimental
 */
final class PreRenderEvent extends Event
{
    private string $template;

    /**
     * @internal
     */
    public function __construct(
        private MountedComponent $mounted,
        private ComponentMetadata $metadata,
        private array $variables
    ) {
        $this->template = $this->metadata->getTemplate();
    }

    /**
     * @return string The twig template used for the component
     */
    public function getTemplate(): string
    {
        return $this->template;
    }

    /**
     * Change the twig template used.
     */
    public function setTemplate(string $template): self
    {
        $this->template = $template;

        return $this;
    }

    public function getComponent(): object
    {
        return $this->mounted->getComponent();
    }

    /**
     * @return array the variables that will be available in the component's template
     */
    public function getVariables(): array
    {
        return $this->variables;
    }

    /**
     * Change the twig variables used.
     */
    public function setVariables(array $variables): self
    {
        $this->variables = $variables;

        return $this;
    }

    public function getMetadata(): ComponentMetadata
    {
        return $this->metadata;
    }

    /**
     * @internal
     */
    public function getMountedComponent(): MountedComponent
    {
        return $this->mounted;
    }
}
