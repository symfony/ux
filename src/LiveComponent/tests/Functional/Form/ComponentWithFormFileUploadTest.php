<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Functional\Form;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

final class ComponentWithFormFileUploadTest extends KernelTestCase
{
    use InteractsWithLiveComponents;

    public function testSubmitFormWithRequestMapsUploadedFileToFormData(): void
    {
        $component = $this->createLiveComponent('form_with_file_upload')
            ->set('document_form.title', 'Report')
            ->call('save', [], ['document_form' => ['file' => self::uploadedFile('test.txt')]])
        ;

        $this->assertSame(200, $component->response()->getStatusCode());
        $this->assertSame([
            'title' => 'Report',
            'file' => 'test.txt',
            'attachments' => [],
            'thumbnail' => null,
        ], $component->component()->submitted);
    }

    public function testSubmitFormWithRequestWithoutFilesKeepsOtherValues(): void
    {
        $component = $this->createLiveComponent('form_with_file_upload')
            ->set('document_form.title', 'Report')
            ->call('save')
        ;

        $this->assertSame(200, $component->response()->getStatusCode());
        $this->assertSame([
            'title' => 'Report',
            'file' => null,
            'attachments' => [],
            'thumbnail' => null,
        ], $component->component()->submitted);
    }

    public function testSubmitFormWithRequestMapsMultipleAndNestedFiles(): void
    {
        $component = $this->createLiveComponent('form_with_file_upload')
            ->set('document_form.title', 'Report')
            ->call('save', [], ['document_form' => [
                'attachments' => [self::uploadedFile('test.txt'), self::uploadedFile('foo.json')],
                'details' => ['thumbnail' => self::uploadedFile('foo.json')],
            ]])
        ;

        $this->assertSame(200, $component->response()->getStatusCode());
        $this->assertSame([
            'title' => 'Report',
            'file' => null,
            'attachments' => ['test.txt', 'foo.json'],
            'thumbnail' => 'foo.json',
        ], $component->component()->submitted);
    }

    public function testSubmitFormWithRequestReplacesValuesSentByFileInputs(): void
    {
        $component = $this->createLiveComponent('form_with_file_upload')
            ->set('document_form.title', 'Report')
            ->set('document_form.file', 'C:\\fakepath\\test.txt')
            ->set('document_form.attachments', 'C:\\fakepath\\test.txt')
            ->call('save', [], ['document_form' => [
                'file' => self::uploadedFile('test.txt'),
                'attachments' => [self::uploadedFile('test.txt'), self::uploadedFile('foo.json')],
            ]])
        ;

        $this->assertSame(200, $component->response()->getStatusCode());
        $this->assertSame([
            'title' => 'Report',
            'file' => 'test.txt',
            'attachments' => ['test.txt', 'foo.json'],
            'thumbnail' => null,
        ], $component->component()->submitted);
    }

    public function testSubmitFormWithRequestValidatesUploadedFile(): void
    {
        $component = $this->createLiveComponent('form_with_file_upload')
            ->set('document_form.title', 'Report')
        ;

        $this->expectException(UnprocessableEntityHttpException::class);
        $this->expectExceptionMessageMatches("/^Form validation failed:\ndocument_form\.file: Only text files are allowed$/");

        $component->call('save', [], ['document_form' => ['file' => self::uploadedFile('foo.json')]]);
    }

    public function testSubmitFormWithRequestReportsErrorOnOtherFieldWithValidFile(): void
    {
        $component = $this->createLiveComponent('form_with_file_upload');

        $this->expectException(UnprocessableEntityHttpException::class);
        $this->expectExceptionMessageMatches("/^Form validation failed:\ndocument_form\.title: The title field should not be blank$/");

        $component->call('save', [], ['document_form' => ['file' => self::uploadedFile('test.txt')]]);
    }

    public function testSubmitFormWithRequestReportsUploadError(): void
    {
        $component = $this->createLiveComponent('form_with_file_upload')
            ->set('document_form.title', 'Report')
        ;

        $this->expectException(UnprocessableEntityHttpException::class);
        $this->expectExceptionMessageMatches("/^Form validation failed:\ndocument_form\.file: The file is too large\./");

        $component->call('save', [], ['document_form' => ['file' => self::uploadedFile('test.txt', \UPLOAD_ERR_INI_SIZE)]]);
    }

    public function testSubmitFormWithoutRequestIgnoresUploadedFiles(): void
    {
        $component = $this->createLiveComponent('form_with_file_upload')
            ->set('document_form.title', 'Report')
            ->call('saveWithoutRequest', [], ['document_form' => ['file' => self::uploadedFile('test.txt')]])
        ;

        $this->assertSame(200, $component->response()->getStatusCode());
        $this->assertSame([
            'title' => 'Report',
            'file' => null,
            'attachments' => [],
            'thumbnail' => null,
        ], $component->component()->submitted);
    }

    private static function uploadedFile(string $name, int $error = \UPLOAD_ERR_OK): UploadedFile
    {
        return new UploadedFile(__DIR__.'/../../Fixtures/files/'.$name, $name, null, $error, true);
    }
}
