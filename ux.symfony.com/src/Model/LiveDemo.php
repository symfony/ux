<?php

namespace App\Model;

class LiveDemo
{
    public function __construct(
        private string $identifier,
        private string $name,
        private string $description,
        private string $route,
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

    public function getRoute(): string
    {
        return $this->route;
    }

    public function getScreenshotFilename(): string
    {
        return 'build/images/live_demo/'.$this->identifier.'.png';
    }
}
