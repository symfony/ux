<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Model;

class LiveDemo
{
    public function __construct(
        private string $identifier,
        private string $name,
        private string $description,
        private string $route,
        private string $longDescription,
    ) {
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getLongDescription(): string
    {
        return $this->longDescription;
    }

    public function getRoute(): string
    {
        return $this->route;
    }

    public function getScreenshotFilename(): string
    {
        return 'images/live_demo/'.$this->identifier.'.png';
    }
}
