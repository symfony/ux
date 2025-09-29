<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Fixtures\Component;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\Tests\Fixtures\Dto\Document;
use Symfony\UX\LiveComponent\Tests\Fixtures\Form\DocumentFormType;

#[AsLiveComponent('form_with_file_upload', template: 'components/form_with_file_upload.html.twig')]
class FormWithFileUploadComponent extends AbstractController
{
    use ComponentWithFormTrait;
    use DefaultActionTrait;

    #[LiveProp]
    public array $submitted = [];

    #[LiveAction]
    public function save(Request $request): void
    {
        $this->submitForm(true, $request);
        $this->storeSubmittedData();
    }

    #[LiveAction]
    public function saveWithoutRequest(): void
    {
        $this->submitForm();
        $this->storeSubmittedData();
    }

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(DocumentFormType::class, new Document());
    }

    private function storeSubmittedData(): void
    {
        /** @var Document $document */
        $document = $this->getForm()->getData();

        $this->submitted = [
            'title' => $document->title,
            'file' => $document->file?->getClientOriginalName(),
            'attachments' => array_map(static fn (UploadedFile $file): string => $file->getClientOriginalName(), $document->attachments),
            'thumbnail' => ($document->details['thumbnail'] ?? null)?->getClientOriginalName(),
        ];
    }
}
