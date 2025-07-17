<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig\Components\Badge;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsTwigComponent(
    name: 'Badge',
    template: 'components/Badge.html.twig',
    exposePublicProps: false,
)]
class Badge
{
    public string $label;

    public string $value;

    public string $icon;

    public string $url;

    #[ExposeInTemplate(destruct: true)]
    public function getBadge(): array
    {
        return [
            'icon' => $this->getIcon(),
            'label' => $this->getLabel(),
            'value' => $this->getValue(),
            'url' => $this->getUrl(),
        ];
    }

    protected function getLabel(): string
    {
        return $this->label;
    }

    protected function getValue(): string
    {
        return $this->value ?? '';
    }

    protected function getIcon(): ?string
    {
        return $this->icon;
    }

    protected function getUrl(): ?string
    {
        return $this->url ?? '';
    }
}
