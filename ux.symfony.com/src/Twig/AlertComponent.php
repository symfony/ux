<?php

namespace App\Twig;

use App\Service\PackageRepository;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('alert')]
class AlertComponent
{
    public string $type = 'success';
    public string $message;

    public function __construct(private PackageRepository $packageRepository)
    {
    }

    public function getIconClass(): string
    {
        return match ($this->type) {
            'success' => 'fa fa-circle-check',
            'danger' => 'fa fa-circle-exclamation',
        };
    }

    public function getPackageCount(): int
    {
        return $this->packageRepository->count();
    }
}
