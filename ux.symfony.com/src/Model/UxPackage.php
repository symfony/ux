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

class UxPackage
{
    private ?string $docsLink = null;
    private ?string $docsLinkText = null;
    private ?string $screencastLink = null;
    private ?string $screencastLinkText = null;

    public function __construct(
        private string $name,
        private string $humanName,
        private string $route,
        private string $color,
        private string $gradient,
        private string $tagLine,
        private string $description,
        private string $createString,
        private ?string $imageFileName = null,
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

    public function getColor(): string
    {
        return $this->color;
    }

    public function getGradient(): string
    {
        return $this->gradient;
    }

    public function getImageFilename(?string $format = null): string
    {
        return $this->imageFileName ?? ltrim($this->name, 'ux-').($format ? ('-'.$format) : '').'.png';
    }

    public function getTagLine(): string
    {
        return $this->tagLine;
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

    public function getImage(?string $format = null): string
    {
        return 'images/ux_packages/'.$this->getImageFilename($format);
    }
}
