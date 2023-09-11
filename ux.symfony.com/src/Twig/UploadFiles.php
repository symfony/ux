<?php

namespace App\Twig;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
class UploadFiles
{
    use DefaultActionTrait;

    public function __construct(private ValidatorInterface $validator)
    {
    }

    #[LiveProp]
    public ?string $singleUploadFilename = null;
    #[LiveProp]
    public ?string $singleFileUploadError = null;

    #[LiveProp]
    public array $multipleUploadFilenames = [];

    #[LiveAction]
    public function uploadFiles(Request $request): void
    {
        $singleFileUpload = $request->files->get('single');
        if ($singleFileUpload) {
            $this->validateSingleFile($singleFileUpload);
        }

        if ($singleFileUpload instanceof UploadedFile) {
            [$this->singleUploadFilename] = $this->processFileUpload($singleFileUpload);
        }

        $multiple = $request->files->all('multiple');
        foreach ($multiple as $file) {
            if ($file instanceof UploadedFile) {
                [$filename, $size] = $this->processFileUpload($file);
                $this->multipleUploadFilenames[] = ['filename' => $filename, 'size' => $size];
            }
        }
    }

    private function processFileUpload(UploadedFile $file): array
    {
        // in a real app, move this file somewhere
        // $file->move(...);

        return [$file->getClientOriginalName(), $file->getSize()];
    }

    private function validateSingleFile(UploadedFile $singleFileUpload): void
    {
        $errors = $this->validator->validate($singleFileUpload, [
            new Assert\File([
                'maxSize' => '1M',
            ]),
        ]);

        if (0 === \count($errors)) {
            return;
        }

        $this->singleFileUploadError = $errors->get(0)->getMessage();

        // causes the component to re-render
        throw new UnprocessableEntityHttpException('Validation failed');
    }
}
