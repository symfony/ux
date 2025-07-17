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
final class PostMountEvent extends Event
{
    private ?ComponentMetadata $metadata;
    private array $extraMetadata;

    public function __construct(
        private object $component,
        private array $data,
        array|ComponentMetadata $metadata = [],
        $extraMetadata = [],
    ) {
        if (\is_array($metadata)) {
            trigger_deprecation('symfony/ux-twig-component', '2.13', 'In TwigComponent 3.0, the third argument of "%s()" will be a "%s" object and the "$extraMetadata" array should be passed as the fourth argument.', __METHOD__, ComponentMetadata::class);

            $this->metadata = null;
            $this->extraMetadata = $metadata;
        } else {
            if (null !== $metadata && !$metadata instanceof ComponentMetadata) {
                throw new \InvalidArgumentException(\sprintf('Expecting "$metadata" to be null or an instance of "%s", given: "%s."', ComponentMetadata::class, get_debug_type($metadata)));
            }
            if (!\is_array($extraMetadata)) {
                throw new \InvalidArgumentException(\sprintf('Expecting "$extraMetadata" to be array, given: "%s".', get_debug_type($extraMetadata)));
            }

            $this->metadata = $metadata;
            $this->extraMetadata = $extraMetadata;
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

    public function getExtraMetadata(): array
    {
        return $this->extraMetadata;
    }

    public function addExtraMetadata(string $key, mixed $value): void
    {
        $this->extraMetadata[$key] = $value;
    }

    public function removeExtraMetadata(string $key): void
    {
        unset($this->extraMetadata[$key]);
    }
}
