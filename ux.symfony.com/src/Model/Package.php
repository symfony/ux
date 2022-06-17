<?php

namespace App\Model;

class Package
{
    private ?string $docsLink = null;
    private ?string $docsLinkText = null;
    private ?string $screencastLink = null;
    private ?string $screencastLinkText = null;

    public function __construct(
        private string $name,
        private string $humanName,
        private string $route,
        private string $gradient,
        private string $description,
        private string $createString,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getHumanName(): string
    {
        return $this->humanName;
    }

    public function getRoute(): string
    {
        return $this->route;
    }

    public function getGradient(): string
    {
        return $this->gradient;
    }

    public function getImageFilename(): string
    {
        return ltrim($this->name, 'ux-').'.png';
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getComposerName(): string
    {
        return 'symfony/ux-'.$this->getName();
    }

    public function getComposerRequireCommand(): string
    {
        return 'composer require '.$this->getComposerName();
    }

    public function getDocsLink(): ?string
    {
        return $this->docsLink;
    }

    public function setDocsLink(string $url, string $description): self
    {
        $this->docsLink = $url;
        $this->docsLinkText = $description;

        return $this;
    }

    public function getScreencastLink(): ?string
    {
        return $this->screencastLink;
    }

    public function setScreencastLink(string $url, string $description): self
    {
        $this->screencastLink = $url;
        $this->screencastLinkText = $description;

        return $this;
    }

    public function getDocsLinkText(): ?string
    {
        return $this->docsLinkText;
    }

    public function getScreencastLinkText(): ?string
    {
        return $this->screencastLinkText;
    }

    public function getOfficialDocsUrl(): string
    {
        return sprintf('https://symfony.com/bundles/ux-%s/current/index.html', $this->name);
    }

    public function getCreateString(): string
    {
        return $this->createString;
    }
}
