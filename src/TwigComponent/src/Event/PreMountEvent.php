<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Symfony\UX\TwigComponent\ComponentMetadata;

/**
 * @author Ryan Weaver <ryan@symfonycasts.com>
 */
final class PreMountEvent extends Event
{
    public function __construct(private object $component, private array $data, private readonly ?ComponentMetadata $metadata = null)
    {
        if (null === $this->metadata) {
            trigger_deprecation('symfony/ux-twig-component', '2.13', 'In TwigComponent 3.0, "%s()" method will require a "%s $metadata" argument. Not passing it is deprecated.', __METHOD__, ComponentMetadata::class);
        }
    }

    public function getComponent(): object
    {
        return $this->component;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): void
    {
        $this->data = $data;
    }

    public function getMetadata(): ?ComponentMetadata
    {
        return $this->metadata;
    }
}
