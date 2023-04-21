<?php

namespace App\Twig;

use App\Model\Package;
use App\Repository\ChatRepository;
use App\Service\PackageRepository;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent()]
class PackageHeader
{
    public Package $package;

    public string $eyebrowText = '';

    /**
     * Render with the chat bubble icon?
     */
    public bool $withChatIcon = false;

    public function __construct(private PackageRepository $packageRepository, private ChatRepository $chatRepository)
    {
    }

    public function mount(string $package): void
    {
        $this->package = $this->packageRepository->find($package);
    }

    /**
     * Returns the chat count needed for the header.
     */
    public function getMessageCount(): int
    {
        return $this->chatRepository->count([]);
    }
}
