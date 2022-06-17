<?php

namespace App\Twig;

use App\Model\Package;
use App\Repository\ChatRepository;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('package_header')]
class PackageHeaderComponent
{
    public Package $package;

    public string $eyebrowText = '';

    /**
     * Render with the chat bubble icon?
     */
    public bool $withChatIcon = false;

    public function __construct(private ChatRepository $chatRepository)
    {
    }

    /**
     * Returns the chat count needed for the header.
     */
    public function getMessageCount(): int
    {
        return $this->chatRepository->count([]);
    }
}
