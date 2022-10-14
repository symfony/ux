<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @experimental
 *
 * @internal
 */
final class MountedComponent
{
    /**
     * @param array|null $inputProps if the component was just originally created,
     *                               (not hydrated from a request), this is the
     *                               array of initial props used to create the component
     */
    public function __construct(
        private string $name,
        private object $component,
        private ComponentAttributes $attributes,
        private ?array $inputProps = []
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getComponent(): object
    {
        return $this->component;
    }

    public function getAttributes(): ComponentAttributes
    {
        return $this->attributes;
    }

    public function getInputProps(): array
    {
        if (null === $this->inputProps) {
            throw new \LogicException('The component was not created from input props.');
        }

        return $this->inputProps;
    }
}
