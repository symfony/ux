<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Upload\Tests\Form;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\HttpFoundation\File\UploadedFile as HttpUploadedFile;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\Mime\MimeTypes;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validation;
use Symfony\UX\Upload\Event\UploadAssembledEvent;
use Symfony\UX\Upload\EventListener\FileValidationListener;
use Symfony\UX\Upload\Form\UploadType;
use Symfony\UX\Upload\Security\UploadContext;
use Symfony\UX\Upload\Storage\InMemoryStorage;
use Symfony\UX\Upload\Test\UploadedFileFactory;
use Symfony\UX\Upload\Tests\Fixtures\Form\DocumentData;
use Symfony\UX\Upload\Token\UploadTokenHandler;
use Symfony\UX\Upload\Upload\UploadedFile;
use Symfony\UX\Upload\Uploader;
use Symfony\UX\Upload\Url\UploadUrlGeneratorInterface;

/**
 * Runs application code written for FileType against UploadType, unchanged.
 */
#[CoversClass(UploadType::class)]
#[CoversClass(UploadedFile::class)]
final class FileTypeReplacementTest extends TestCase
{
    private const PNG = "\x89PNG\r\n\x1a\n\0\0\0\rIHDR\0\0\0\x01\0\0\0\x01\x08\x06\0\0\0\x1f\x15\xc4\x89\0\0\0\rIDATx\x9cc\xf8\xcf\xc0\xf0\x1f\0\x05\0\x01\xff\x89\x99=\x1d\0\0\0\0IEND\xaeB`\x82";

    private InMemoryStorage $storage;
    private UploadTokenHandler $tokenHandler;
    private FormFactoryInterface $formFactory;
    private string $directory;

    protected function setUp(): void
    {
        $this->storage = new InMemoryStorage();
        $this->tokenHandler = new UploadTokenHandler(new UriSigner('secret'), $this->storage);
        $router = $this->createStub(UrlGeneratorInterface::class);
        $router->method('generate')->willReturn('/upload/init');

        $dispatcher = new EventDispatcher();
        // A small chunk size makes a file submitted without JavaScript span several chunks
        $uploader = new Uploader($this->storage, $this->createStub(UploadUrlGeneratorInterface::class), $dispatcher, chunkSize: 4);
        $uploaders = new ServiceLocator(['default' => static fn (): Uploader => $uploader]);
        $dispatcher->addListener(UploadAssembledEvent::class, new FileValidationListener(new MimeTypes(), $uploaders));

        $this->formFactory = Forms::createFormFactoryBuilder()
            ->addExtension(new HttpFoundationExtension())
            ->addExtension(new ValidatorExtension(Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator()))
            ->addExtension(new PreloadedExtension([new UploadType($router, $this->tokenHandler, $uploaders, allowAnonymous: true)], []))
            ->getFormFactory();

        $this->directory = sys_get_temp_dir().'/ux_upload_replacement_'.bin2hex(random_bytes(4));
        mkdir($this->directory);
    }

    protected function tearDown(): void
    {
        array_map(unlink(...), glob($this->directory.'/*') ?: []);
        rmdir($this->directory);
    }

    /**
     * @return iterable<string, array{class-string<FormTypeInterface>, bool}>
     */
    public static function fieldTypes(): iterable
    {
        yield 'FileType' => [FileType::class, true];
        yield 'UploadType' => [UploadType::class, false];
        yield 'UploadType without JavaScript' => [UploadType::class, true];
    }

    /**
     * @param class-string<FormTypeInterface> $type
     */
    #[DataProvider('fieldTypes')]
    public function testAnUnmappedFieldGivesAFileTheControllerCanMove(string $type, bool $submitsFile): void
    {
        $form = $this->formFactory->createBuilder()
            ->add('attachment', $type, ['mapped' => false])
            ->getForm();

        $form->submit(['attachment' => $this->submitted($submitsFile, 'form.attachment', 'report.txt', 'hello', 'text/plain')]);

        $file = $form->get('attachment')->getData();
        self::assertTrue($form->isSynchronized());
        self::assertInstanceOf(HttpUploadedFile::class, $file);
        self::assertTrue($file->isValid());
        self::assertSame('report.txt', $file->getClientOriginalName());
        self::assertSame('txt', $file->getClientOriginalExtension());
        self::assertSame('text/plain', $file->getMimeType());
        self::assertSame(5, $file->getSize());

        $file->move($this->directory, 'stored.txt');

        self::assertStringEqualsFile($this->directory.'/stored.txt', 'hello');
    }

    /**
     * @param class-string<FormTypeInterface> $type
     */
    #[DataProvider('fieldTypes')]
    public function testAMappedFieldFillsAPropertyTypedAsUploadedFile(string $type, bool $submitsFile): void
    {
        $data = new DocumentData();
        $form = $this->formFactory->createBuilder(FormType::class, $data, ['data_class' => DocumentData::class])
            ->add('document', $type)
            ->getForm();

        $form->submit(['document' => $this->submitted($submitsFile, 'form.document', 'notes.txt', 'hello', 'text/plain')]);

        self::assertTrue($form->isValid(), (string) $form->getErrors(true));
        self::assertInstanceOf(HttpUploadedFile::class, $data->document);
        self::assertSame('hello', $data->document->getContent());
    }

    /**
     * @param class-string<FormTypeInterface> $type
     */
    #[DataProvider('fieldTypes')]
    public function testTheFileConstraintRefusesAnotherType(string $type, bool $submitsFile): void
    {
        $form = $this->formFactory->createBuilder(FormType::class, new DocumentData(), ['data_class' => DocumentData::class])
            ->add('document', $type)
            ->getForm();

        $form->submit(['document' => $this->submitted($submitsFile, 'form.document', 'pixel.png', self::PNG, 'image/png')]);

        self::assertFalse($form->isValid());
        self::assertSame([File::INVALID_MIME_TYPE_ERROR], $this->violationCodes($form->get('document')->getErrors()));
    }

    /**
     * @param class-string<FormTypeInterface> $type
     */
    #[DataProvider('fieldTypes')]
    public function testTheFileConstraintRefusesAFileAboveItsMaximumSize(string $type, bool $submitsFile): void
    {
        $form = $this->formFactory->createBuilder(FormType::class, new DocumentData(), ['data_class' => DocumentData::class])
            ->add('document', $type)
            ->getForm();

        $form->submit(['document' => $this->submitted($submitsFile, 'form.document', 'long.txt', str_repeat('a', 2048), 'text/plain')]);

        self::assertFalse($form->isValid());
        self::assertSame([File::TOO_LARGE_ERROR], $this->violationCodes($form->get('document')->getErrors()));
    }

    /**
     * @param class-string<FormTypeInterface> $type
     */
    #[DataProvider('fieldTypes')]
    public function testAMultipleFieldGivesTheFilesInOrder(string $type, bool $submitsFile): void
    {
        $form = $this->formFactory->createBuilder()
            ->add('attachments', $type, ['mapped' => false, 'multiple' => true])
            ->getForm();
        $first = $this->submitted($submitsFile, 'form.attachments', 'first.txt', 'one', 'text/plain');
        $second = $this->submitted($submitsFile, 'form.attachments', 'second.txt', 'two', 'text/plain');

        $form->submit(['attachments' => \is_string($first) && \is_string($second) ? '['.$first.','.$second.']' : [$first, $second]]);

        $files = $form->get('attachments')->getData();
        self::assertTrue($form->isSynchronized());
        self::assertIsList($files);
        self::assertContainsOnlyInstancesOf(HttpUploadedFile::class, $files);
        self::assertSame(['first.txt', 'second.txt'], array_map(static fn (HttpUploadedFile $file): string => $file->getClientOriginalName(), $files));
        self::assertSame(['one', 'two'], array_map(static fn (HttpUploadedFile $file): string => $file->getContent(), $files));
    }

    /**
     * @param class-string<FormTypeInterface> $type
     */
    #[DataProvider('fieldTypes')]
    public function testAnEmptySubmissionGivesNoFile(string $type, bool $submitsFile): void
    {
        $form = $this->formFactory->createBuilder()
            ->add('attachment', $type, ['mapped' => false])
            ->add('attachments', $type, ['mapped' => false, 'multiple' => true])
            ->getForm();

        $form->submit([]);

        self::assertTrue($form->isSynchronized());
        self::assertNull($form->get('attachment')->getData());
        self::assertSame([], $form->get('attachments')->getData());
    }

    /**
     * What the browser sends for one selected file: the file itself, or the
     * signed token of the upload completed by the UploadType controller.
     */
    private function submitted(bool $submitsFile, string $fieldName, string $name, string $content, string $mimeType): HttpUploadedFile|string
    {
        if ($submitsFile) {
            $path = $this->directory.'/'.bin2hex(random_bytes(4)).'-'.$name;
            file_put_contents($path, $content);

            return new HttpUploadedFile($path, $name, $mimeType, test: true);
        }

        $upload = new UploadedFileFactory(
            id: bin2hex(random_bytes(16)),
            originalName: $name,
            mimeType: $mimeType,
            size: \strlen($content),
            fieldName: $fieldName,
        )->create($this->storage, $content);

        return json_encode(['token' => $this->tokenHandler->generate($upload, new UploadContext(fieldName: $fieldName))], \JSON_THROW_ON_ERROR);
    }

    /**
     * @param FormErrorIterator<FormError> $errors
     *
     * @return list<string|null>
     */
    private function violationCodes(FormErrorIterator $errors): array
    {
        $codes = [];
        foreach ($errors as $error) {
            self::assertInstanceOf(FormError::class, $error);
            $cause = $error->getCause();
            $codes[] = $cause instanceof ConstraintViolationInterface ? $cause->getCode() : null;
        }

        return $codes;
    }
}
