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

/**
 * @author Ryan Weaver <ryan@symfonycasts.com>
 *
 * @experimental
 */
final class PostMountEvent extends Event
{
    public function __construct(private object $component, private array $data)
    {
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
}
