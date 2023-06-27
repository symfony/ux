<?php

namespace App\Twig;

use App\Repository\PackageRepository;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent()]
class SearchPackages
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
