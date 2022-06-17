<?php

namespace App\Twig;

use App\Service\PackageRepository;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('search_packages')]
class SearchPackagesComponent
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public ?string $query = null;

    public function __construct(private PackageRepository $packageRepo)
    {
    }

    public function getPackages(): array
    {
        return $this->packageRepo->findAll($this->query);
    }
}
