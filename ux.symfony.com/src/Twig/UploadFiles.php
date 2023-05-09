<?php

declare(strict_types=1);

namespace App\Twig;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent()]
class UploadFiles
{
    use DefaultActionTrait;

    public ?UploadedFile $single = null;
    public array $multiple = [];

    #[LiveAction]
    public function sendFiles(Request $request): void
    {
        $this->single = $request->files->get('single');
        $this->multiple = $request->files->all('multiple');
    }
}
