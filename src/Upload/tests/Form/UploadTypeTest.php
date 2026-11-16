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
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\HttpFoundation\File\UploadedFile as HttpUploadedFile;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\Upload\Form\UploadType;
use Symfony\UX\Upload\Policy\UploadPolicySigner;
use Symfony\UX\Upload\Security\UploadContext;
use Symfony\UX\Upload\Storage\InMemoryStorage;
use Symfony\UX\Upload\Test\UploadedFileFactory;
use Symfony\UX\Upload\Token\UploadTokenHandler;
use Symfony\UX\Upload\Upload\UploadedFile;
use Symfony\UX\Upload\Uploader;
use Symfony\UX\Upload\UploaderInterface;
use Symfony\UX\Upload\Url\UploadUrlGeneratorInterface;

#[CoversClass(UploadType::class)]
final class UploadTypeTest extends TestCase
{
    private InMemoryStorage $storage;
    private UploadTokenHandler $tokenHandler;
    private FormFactoryInterface $formFactory;
    /** @var list<string> */
    private array $temporaryFiles = [];

    protected function tearDown(): void
    {
        array_map(unlink(...), array_filter($this->temporaryFiles, is_file(...)));
    }

    protected function setUp(): void
    {
        $this->storage = new InMemoryStorage();
        $this->tokenHandler = new UploadTokenHandler(new UriSigner('secret'), $this->storage);
        $this->formFactory = $this->factory();
    }

    /**
     * @return iterable<string, array{string, string, int, array<string, mixed>}>
     */
    public static function uploadsOutsideTheFieldPolicy(): iterable
    {
        yield 'created by another uploader' => ['avatar', 'image/png', 10, ['uploader' => 'documents']];
        yield 'larger than the field limit' => ['default', 'application/pdf', 2048, ['max_size' => '1K']];
        yield 'outside the field MIME policy' => ['default', 'text/plain', 10, ['allowed_types' => ['image/*']]];
    }

    /**
     * @param array<string, mixed> $options
     */
    #[DataProvider('uploadsOutsideTheFieldPolicy')]
    public function testRejectsAnUploadOutsideTheFieldPolicy(string $uploader, string $mimeType, int $size, array $options): void
    {
        $form = $this->formFactory->create(UploadType::class, options: $options);

        $form->submit($this->payload($this->stage($uploader, $mimeType, $size, 'ux_upload')));

        self::assertFalse($form->isSynchronized());
    }

    public function testAcceptsUploadMatchingFieldMimeWildcard(): void
    {
        $upload = $this->stage('default', 'image/png', 10, 'ux_upload');
        $form = $this->formFactory->create(UploadType::class, options: ['allowed_types' => ['image/*']]);

        $form->submit($this->payload($upload));

        self::assertTrue($form->isSynchronized());
        self::assertEquals($upload, $form->getData());
    }

    public function testIsRequiredAndMappedByDefaultLikeFileType(): void
    {
        $config = $this->formFactory->create(UploadType::class)->getConfig();

        self::assertTrue($config->getRequired());
        self::assertTrue($config->getMapped());
    }

    public function testMapsTheUploadToTheUnderlyingData(): void
    {
        $upload = $this->stage('default', 'text/plain', 10, 'form.document');
        $form = $this->formFactory->createBuilder(data: ['document' => null])
            ->add('document', UploadType::class)
            ->getForm();

        $form->submit(['document' => $this->payload($upload)]);

        self::assertTrue($form->isSynchronized());
        self::assertEquals(['document' => $upload], $form->getData());
    }

    public function testAnEmptySubmissionGivesTheEmptyDataOfFileType(): void
    {
        $single = $this->formFactory->create(UploadType::class);
        $multiple = $this->formFactory->create(UploadType::class, options: ['multiple' => true]);

        $single->submit('');
        $multiple->submit('');

        self::assertNull($single->getData());
        self::assertSame([], $multiple->getData());
    }

    public function testAMultipleFieldHasNoFileLimitByDefault(): void
    {
        $uploads = [
            $this->stage('default', 'text/plain', 10, 'ux_upload'),
            $this->stage('default', 'text/plain', 10, 'ux_upload'),
            $this->stage('default', 'text/plain', 10, 'ux_upload'),
        ];
        $form = $this->formFactory->create(UploadType::class, options: ['multiple' => true]);

        $form->submit(json_encode(array_map(
            fn (UploadedFile $upload): array => ['token' => $this->tokenHandler->generate($upload, new UploadContext(fieldName: 'ux_upload'))],
            $uploads,
        ), \JSON_THROW_ON_ERROR));

        self::assertTrue($form->isSynchronized());
        self::assertEquals($uploads, $form->getData());
        self::assertSame(0, $form->createView()->vars['max_files']);
    }

    public function testASingleFieldIsLimitedToOneFile(): void
    {
        $form = $this->formFactory->create(UploadType::class);

        self::assertSame(1, $form->createView()->vars['max_files']);
    }

    public function testAFileSubmittedWithoutJavaScriptIsStoredThroughTheUploader(): void
    {
        $form = $this->nativeFormFactory()->create(UploadType::class);

        $form->submit($this->nativeFile('hello world'));

        $upload = $form->getData();
        self::assertTrue($form->isSynchronized());
        self::assertInstanceOf(UploadedFile::class, $upload);
        self::assertSame('notes.txt', $upload->getClientOriginalName());
        self::assertSame(11, $upload->getSize());
        self::assertSame('ux_upload', $upload->getFieldName());
        self::assertSame('hello world', $this->storage->read($upload->getStoragePath()));
    }

    public function testTheViewRendersTheStoredUploadNeverTheSubmittedFile(): void
    {
        $form = $this->nativeFormFactory()->create(UploadType::class);
        $file = $this->nativeFile('hello world');

        $form->submit($file);
        $view = $form->createView();

        self::assertTrue($view->vars['multipart']);
        self::assertIsString($view->vars['value']);
        self::assertStringNotContainsString($file->getPathname(), $view->vars['value']);
        $token = json_decode($view->vars['value'], true, flags: \JSON_THROW_ON_ERROR)['token'];
        self::assertEquals($form->getData(), $this->tokenHandler->resolve($token, new UploadContext(fieldName: 'ux_upload')));
    }

    public function testAnAnonymousVisitorCannotSubmitAFileUnlessTheApplicationAllowsIt(): void
    {
        $form = $this->nativeFormFactory(allowAnonymous: false)->create(UploadType::class);

        $form->submit($this->nativeFile('hello world'));

        self::assertFalse($form->isSynchronized());
        self::assertNull($form->getData());
        self::assertStringContainsString('Anonymous uploads are disabled.', (string) $form->getTransformationFailure()?->getMessage());
        self::assertSame('', $form->createView()->vars['value']);
    }

    /**
     * @return iterable<string, array{array<string, mixed>, string, int, string}>
     */
    public static function refusedNativeFiles(): iterable
    {
        yield 'refused by PHP' => [[], 'hello world', \UPLOAD_ERR_INI_SIZE, 'The file could not be uploaded.'];
        yield 'above the field limit' => [['max_size' => '4'], 'hello world', \UPLOAD_ERR_OK, 'File size exceeds form policy limit of 4 bytes.'];
        yield 'outside the field types' => [['allowed_types' => ['image/png']], 'hello world', \UPLOAD_ERR_OK, 'MIME type "text/plain" is not allowed by the form policy.'];
        yield 'empty' => [[], '', \UPLOAD_ERR_OK, 'The file could not be uploaded.'];
    }

    /**
     * @param array<string, mixed> $options
     */
    #[DataProvider('refusedNativeFiles')]
    public function testARefusedFileSubmittedWithoutJavaScriptCarriesItsReason(array $options, string $content, int $error, string $message): void
    {
        $form = $this->nativeFormFactory()->create(UploadType::class, options: $options);

        $form->submit($this->nativeFile($content, error: $error));

        self::assertFalse($form->isSynchronized());
        self::assertSame($message, $form->getTransformationFailure()?->getInvalidMessage());
    }

    public function testAFileSubmittedWithoutJavaScriptIsJudgedOnItsContent(): void
    {
        $png = "\x89PNG\r\n\x1a\n\0\0\0\rIHDR\0\0\0\x01\0\0\0\x01\x08\x06\0\0\0\x1f\x15\xc4\x89\0\0\0\rIDATx\x9cc\xf8\xcf\xc0\xf0\x1f\0\x05\0\x01\xff\x89\x99=\x1d\0\0\0\0IEND\xaeB`\x82";
        $factory = $this->nativeFormFactory();
        $accepted = $factory->create(UploadType::class, options: ['allowed_types' => ['image/png']]);
        $refused = $factory->create(UploadType::class, options: ['allowed_types' => ['image/png']]);

        // A client may announce any type: only the bytes count
        $accepted->submit($this->nativeFile($png, 'pixel.png', 'application/octet-stream'));
        $refused->submit($this->nativeFile('plain text', 'pixel.png', 'image/png'));

        self::assertTrue($accepted->isSynchronized());
        self::assertSame('image/png', $accepted->getData()->getMimeType());
        self::assertFalse($refused->isSynchronized());
        self::assertSame('MIME type "text/plain" is not allowed by the form policy.', $refused->getTransformationFailure()?->getInvalidMessage());
    }

    public function testSeveralFilesSubmittedWithoutJavaScriptFollowTheFileLimit(): void
    {
        $factory = $this->nativeFormFactory();
        $accepted = $factory->create(UploadType::class, options: ['multiple' => true]);
        $refused = $factory->create(UploadType::class, options: ['multiple' => true, 'max_files' => 1]);

        // An empty file input is submitted as null
        $accepted->submit([$this->nativeFile('one', 'first.txt'), null, $this->nativeFile('two', 'second.txt')]);
        $refused->submit([$this->nativeFile('one', 'first.txt'), $this->nativeFile('two', 'second.txt')]);

        self::assertTrue($accepted->isSynchronized());
        self::assertSame(['first.txt', 'second.txt'], array_map(static fn (UploadedFile $upload): string => $upload->getClientOriginalName(), $accepted->getData()));
        self::assertFalse($refused->isSynchronized());
    }

    public function testRejectsMoreFilesThanFieldLimit(): void
    {
        $first = $this->stage('default', 'text/plain', 10);
        $second = $this->stage('default', 'text/plain', 10);
        $form = $this->formFactory->create(UploadType::class, options: [
            'multiple' => true,
            'max_files' => 1,
        ]);

        $form->submit(json_encode([
            ['token' => $this->tokenHandler->generate($first)],
            ['token' => $this->tokenHandler->generate($second)],
        ], \JSON_THROW_ON_ERROR));

        self::assertFalse($form->isSynchronized());
    }

    public function testSignedPolicyUsesTheCompleteFormPathForSameNamedLeafFields(): void
    {
        $signer = new UploadPolicySigner(new UriSigner('secret'));
        $factory = $this->factory(policySigner: $signer);
        $post = $factory->createNamedBuilder('post', FormType::class)
            ->add('attachment', UploadType::class)
            ->getForm();
        $profile = $factory->createNamedBuilder('profile', FormType::class)
            ->add('attachment', UploadType::class)
            ->getForm();

        $postToken = $post->createView()->children['attachment']->vars['stimulus_values']['policyToken'];
        $profileToken = $profile->createView()->children['attachment']->vars['stimulus_values']['policyToken'];

        self::assertSame('post.attachment', $signer->resolve($postToken)?->fieldName);
        self::assertSame('profile.attachment', $signer->resolve($profileToken)?->fieldName);
        self::assertNotSame($postToken, $profileToken);

        $upload = $this->stage('default', 'text/plain', 10, 'post.attachment');
        $token = $this->tokenHandler->generate($upload, new UploadContext(fieldName: 'post.attachment'));
        $post->submit(['attachment' => json_encode(['token' => $token], \JSON_THROW_ON_ERROR)]);
        self::assertTrue($post->get('attachment')->isSynchronized());
        self::assertEquals($upload, $post->get('attachment')->getData());

        $profile->get('attachment')->submit(json_encode(['token' => $token], \JSON_THROW_ON_ERROR));
        self::assertFalse($profile->get('attachment')->isSynchronized());
    }

    public function testCollectionEntriesShareANormalizedFormPath(): void
    {
        $signer = new UploadPolicySigner(new UriSigner('secret'));
        $upload = $this->stage('default', 'text/plain', 10, 'post.attachments.*');
        $post = $this->factory(policySigner: $signer)->createNamedBuilder('post', FormType::class)
            ->add('attachments', CollectionType::class, [
                'entry_type' => UploadType::class,
                'data' => [null],
            ])
            ->getForm();
        $post->get('attachments')->get('0')->setData($upload);

        $view = $post->createView()->children['attachments']->children[0];
        self::assertSame('post.attachments.*', $signer->resolve($view->vars['stimulus_values']['policyToken'])?->fieldName);
        $payload = json_decode($view->vars['value'], true, flags: \JSON_THROW_ON_ERROR);
        self::assertEquals($upload, $this->tokenHandler->resolve($payload['token'], new UploadContext(fieldName: 'post.attachments.*')));

        $post->submit(['attachments' => [json_encode(['token' => $payload['token']], \JSON_THROW_ON_ERROR)]]);
        self::assertTrue($post->get('attachments')->get('0')->isSynchronized());
        self::assertEquals($upload, $post->get('attachments')->get('0')->getData());
    }

    public function testPrototypeUploadedTokenResolvesInDynamicallyAddedRows(): void
    {
        $upload = $this->stage('default', 'text/plain', 10, 'post.attachments.*');
        $token = $this->tokenHandler->generate($upload, new UploadContext(fieldName: 'post.attachments.*'));

        $post = $this->formFactory->createNamedBuilder('post', FormType::class)
            ->add('attachments', CollectionType::class, [
                'entry_type' => UploadType::class,
                'allow_add' => true,
            ])
            ->getForm();

        $post->submit(['attachments' => ['3' => json_encode(['token' => $token], \JSON_THROW_ON_ERROR)]]);

        self::assertTrue($post->get('attachments')->get('3')->isSynchronized());
        self::assertEquals($upload, $post->get('attachments')->get('3')->getData());
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function optionsWideningTheUploaderPolicy(): iterable
    {
        yield 'larger maximum size' => [['uploader' => 'documents', 'max_size' => '2K']];
        yield 'wider MIME policy' => [['uploader' => 'images', 'allowed_types' => ['application/pdf']]];
    }

    /**
     * @param array<string, mixed> $options
     */
    #[DataProvider('optionsWideningTheUploaderPolicy')]
    public function testFieldCannotWidenTheUploaderPolicy(array $options): void
    {
        $factory = $this->factory($this->uploaders(['max_size' => 1024, 'allowed_types' => ['image/*']]));

        $this->expectException(InvalidOptionsException::class);
        $factory->create(UploadType::class, options: $options)->createView();
    }

    public function testSingleAndMultipleInitialDataRoundTripThroughSignedTokens(): void
    {
        $first = $this->stage('default', 'text/plain', 10, 'ux_upload');
        $second = $this->stage('default', 'text/plain', 20, 'ux_upload');

        $single = $this->formFactory->create(UploadType::class, $first);
        $singlePayload = json_decode($single->createView()->vars['value'], true, flags: \JSON_THROW_ON_ERROR);
        self::assertSame('file.bin', $singlePayload['meta']['filename']);
        self::assertSame('text/plain', $singlePayload['meta']['mimeType']);
        self::assertSame(10, $singlePayload['meta']['size']);
        $single->submit(json_encode(['token' => $singlePayload['token']], \JSON_THROW_ON_ERROR));
        self::assertEquals($first, $single->getData());

        $multiple = $this->formFactory->create(UploadType::class, [$first, 'ignored', $second], [
            'multiple' => true,
            'max_files' => 3,
        ]);
        $multiplePayload = json_decode($multiple->createView()->vars['value'], true, flags: \JSON_THROW_ON_ERROR);
        self::assertCount(2, $multiplePayload);
        $multiple->submit(json_encode($multiplePayload, \JSON_THROW_ON_ERROR));
        self::assertEquals([$first, $second], $multiple->getData());
    }

    public function testEmptyAndUnsupportedModelValuesNormalizePredictably(): void
    {
        $single = $this->formFactory->create(UploadType::class);
        $single->submit('');
        self::assertNull($single->getData());

        $multiple = $this->formFactory->create(UploadType::class, options: ['multiple' => true]);
        $multiple->submit('');
        self::assertSame([], $multiple->getData());

        self::assertSame('', $this->formFactory->create(UploadType::class, new \stdClass())->createView()->vars['value']);
        self::assertSame('', $this->formFactory->create(UploadType::class, new \stdClass(), ['multiple' => true])->createView()->vars['value']);
    }

    public function testMalformedSingleAndMultiplePayloadsAreRejected(): void
    {
        foreach ([
            ['options' => [], 'payload' => 'not-json'],
            ['options' => [], 'payload' => '{}'],
            ['options' => [], 'payload' => '{"token":"invalid"}'],
            ['options' => ['multiple' => true], 'payload' => '{"token":"invalid"}'],
            ['options' => ['multiple' => true], 'payload' => '[null]'],
            ['options' => ['multiple' => true], 'payload' => '[{"token":"invalid"}]'],
        ] as $case) {
            $form = $this->formFactory->create(UploadType::class, options: $case['options']);
            $form->submit($case['payload']);
            self::assertFalse($form->isSynchronized(), $case['payload']);
        }
    }

    public function testNamedUploaderConfigDrivesViewPolicyTranslationAndCsrf(): void
    {
        $uploader = $this->createStub(UploaderInterface::class);
        $uploader->method('getConfig')->willReturn([
            'max_size' => 2048,
            'allowed_types' => ['image/*'],
            'chunk_size' => 65536,
            'integrity_algorithm' => 'sha512',
            'compression' => true,
        ]);
        $container = $this->createStub(ContainerInterface::class);
        $container->method('has')->willReturnCallback(static fn (string $name): bool => 'images' === $name);
        $container->method('get')->willReturn($uploader);
        $router = $this->createStub(UrlGeneratorInterface::class);
        $router->method('generate')->willReturnCallback(static fn (string $route): string => '/'.$route);
        $translations = [];
        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(static function (string $message, array $parameters = [], ?string $domain = null) use (&$translations): string {
            $translations[] = [$message, $domain];

            return '[translated] '.strtr($message, array_map(strval(...), $parameters));
        });
        $csrf = $this->createStub(CsrfTokenManagerInterface::class);
        $csrf->method('getToken')->willReturn(new CsrfToken('ux_upload', 'csrf-value'));
        $factory = $this->factory(
            $container,
            new UploadPolicySigner(new UriSigner('secret')),
            $translator,
            $csrf,
            $router,
        );

        $view = $factory->createNamed('avatar', UploadType::class, options: [
            'uploader' => 'images',
            'max_files' => 2,
            'multiple' => true,
            'compression' => true,
        ])->createView();

        self::assertSame(2048, $view->vars['max_size']);
        self::assertSame(['image/*'], $view->vars['allowed_types']);
        self::assertSame('csrf-value', $view->vars['stimulus_values']['csrfToken']);
        self::assertSame('sha512', $view->vars['stimulus_values']['integrityAlgorithm']);
        self::assertSame('/ux_upload_direct', $view->vars['stimulus_values']['directUrl']);
        self::assertSame(65536, $view->vars['stimulus_values']['chunkSize']);
        self::assertTrue($view->vars['stimulus_values']['compression']);
        self::assertFalse($view->vars['stimulus_values']['showPreview']);
        self::assertSame('/ux_upload_init', $view->vars['stimulus_values']['initUrl']);
        self::assertSame('/ux_upload_remove', $view->vars['stimulus_values']['removeUrl']);
        self::assertStringStartsWith('[translated]', $view->vars['label_upload']);
        self::assertContains(['ux_upload.upload', 'UXUploadBundle'], $translations);
        self::assertContains(['ux_upload.help.max_files', 'UXUploadBundle'], $translations);
        $policyToken = $view->vars['stimulus_values']['policyToken'];
        self::assertNotNull($policyToken);
        self::assertSame(2048, new UploadPolicySigner(new UriSigner('secret'))->resolve($policyToken)?->maxSize);
    }

    public function testDefaultUploaderConfigNarrowsSubmittedValues(): void
    {
        $factory = $this->factory($this->uploaders([
            'max_size' => 100,
            'allowed_types' => ['image/*'],
            'integrity_algorithm' => 'sha384',
        ]));
        $view = $factory->create(UploadType::class)->createView();

        self::assertSame(100, $view->vars['max_size']);
        self::assertSame(['image/*'], $view->vars['allowed_types']);
        self::assertSame('sha384', $view->vars['stimulus_values']['integrityAlgorithm']);

        $tooLarge = $factory->create(UploadType::class);
        $tooLarge->submit($this->payload($this->stage('default', 'image/png', 101, 'ux_upload')));
        self::assertFalse($tooLarge->isSynchronized());

        $wrongType = $factory->create(UploadType::class);
        $wrongType->submit($this->payload($this->stage('default', 'text/plain', 10, 'ux_upload')));
        self::assertFalse($wrongType->isSynchronized());
    }

    public function testUnlimitedUploaderIsInheritedByTheField(): void
    {
        $signer = new UploadPolicySigner(new UriSigner('secret'));
        $factory = $this->factory($this->uploaders(['max_size' => 0, 'allowed_types' => []]), $signer);
        $form = $factory->create(UploadType::class, options: ['uploader' => 'unlimited']);
        $view = $form->createView();

        self::assertSame(0, $view->vars['max_size']);
        self::assertStringNotContainsString('0 B', $view->vars['help_text']);
        self::assertSame(0, $signer->resolve($view->vars['stimulus_values']['policyToken'])?->maxSize);

        $upload = $this->stage('unlimited', 'application/octet-stream', 10 * 1024 * 1024 * 1024, 'ux_upload');
        $form->submit($this->payload($upload));

        self::assertTrue($form->isSynchronized());
        self::assertEquals($upload, $form->getData());
    }

    public function testExplicitFieldLimitEqualToTheGlobalDefaultIsNotReplaced(): void
    {
        $factory = $this->factory($this->uploaders(['max_size' => 200 * 1024 * 1024, 'allowed_types' => []]));

        $view = $factory->create(UploadType::class, options: [
            'uploader' => 'large',
            'max_size' => '100M',
        ])->createView();

        self::assertSame(100 * 1024 * 1024, $view->vars['max_size']);
    }

    public function testRejectsInvalidUploaderService(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('has')->willReturn(true);
        $container->method('get')->willReturn(new \stdClass());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('must implement');

        $this->factory($container)->create(UploadType::class, options: ['uploader' => 'documents'])->createView();
    }

    public function testHelpTextFormatsSizesCountsAndMimeLabels(): void
    {
        $mimeTypes = [
            '.docx',
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/svg+xml',
            'application/pdf',
            'text/plain',
            'text/csv',
            'application/json',
            'application/zip',
            'image/*',
            'video/*',
            'audio/*',
            'application/vnd.custom',
            'README',
        ];

        $view = $this->formFactory->create(UploadType::class, options: [
            'max_size' => '2G',
            'max_files' => 3,
            'allowed_types' => $mimeTypes,
            'multiple' => true,
        ])->createView();
        self::assertStringContainsString('2 GiB', $view->vars['help_text']);
        self::assertStringContainsString('Up to 3 files', $view->vars['help_text']);
        self::assertStringNotContainsString('--', $view->vars['help_text']);
        foreach (['DOCX', 'JPEG', 'PNG', 'GIF', 'WebP', 'SVG', 'PDF', 'TXT', 'CSV', 'JSON', 'ZIP', 'Images', 'Videos', 'Audio', 'VND.CUSTOM', 'README'] as $label) {
            self::assertStringContainsString($label, $view->vars['help_text']);
        }

        self::assertStringContainsString('2 MiB', $this->formFactory->create(UploadType::class, options: ['max_size' => '2M'])->createView()->vars['help_text']);
        self::assertStringContainsString('2 KiB', $this->formFactory->create(UploadType::class, options: ['max_size' => '2K'])->createView()->vars['help_text']);
        self::assertStringContainsString('2 B', $this->formFactory->create(UploadType::class, options: ['max_size' => '2'])->createView()->vars['help_text']);
    }

    public function testTypeMetadataAndOptionValidation(): void
    {
        $type = new UploadType($this->createStub(UrlGeneratorInterface::class), $this->tokenHandler);
        self::assertSame(HiddenType::class, $type->getParent());
        self::assertSame('ux_upload', $type->getBlockPrefix());

        $this->expectException(InvalidOptionsException::class);
        $this->formFactory->create(UploadType::class, options: ['max_files' => 0]);
    }

    public function testLayoutAndPreviewAreIndependentViewOptions(): void
    {
        $compactForm = $this->formFactory->create(UploadType::class, options: [
            'widget_attr' => ['class' => 'upload-shell'],
        ]);
        $compact = $compactForm->createView();
        self::assertSame('compact', $compact->vars['layout']);
        self::assertFalse($compact->vars['show_preview']);
        self::assertSame(['class' => 'upload-shell'], $compact->vars['upload_attr']);
        self::assertFalse($compactForm->getConfig()->getOption('error_bubbling'));

        $dropzone = $this->formFactory->create(UploadType::class, options: [
            'layout' => 'dropzone',
            'show_preview' => true,
            'multiple' => true,
        ])->createView();
        self::assertSame('dropzone', $dropzone->vars['layout']);
        self::assertTrue($dropzone->vars['show_preview']);
        self::assertTrue($dropzone->vars['multiple']);
    }

    public function testRejectsUnknownLayout(): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->formFactory->create(UploadType::class, options: ['layout' => 'gallery']);
    }

    private function factory(
        ?ContainerInterface $uploaders = null,
        ?UploadPolicySigner $policySigner = null,
        ?TranslatorInterface $translator = null,
        ?CsrfTokenManagerInterface $csrfTokenManager = null,
        ?UrlGeneratorInterface $router = null,
    ): FormFactoryInterface {
        if (null === $router) {
            $router = $this->createStub(UrlGeneratorInterface::class);
            $router->method('generate')->willReturn('/upload/init');
        }
        $type = new UploadType($router, $this->tokenHandler, $uploaders, $translator, $csrfTokenManager, $policySigner);

        return Forms::createFormFactoryBuilder()
            ->addExtension(new PreloadedExtension([$type], []))
            ->getFormFactory();
    }

    /**
     * @param array{max_size: int, allowed_types: list<string>, integrity_algorithm?: string} $config
     */
    private function uploaders(array $config): ContainerInterface
    {
        $uploader = $this->createStub(UploaderInterface::class);
        $uploader->method('getConfig')->willReturn($config + [
            'chunk_size' => 65536,
            'integrity_algorithm' => 'sha256',
            'compression' => false,
        ]);
        $container = $this->createStub(ContainerInterface::class);
        $container->method('has')->willReturn(true);
        $container->method('get')->willReturn($uploader);

        return $container;
    }

    private function nativeFormFactory(bool $allowAnonymous = true): FormFactoryInterface
    {
        $router = $this->createStub(UrlGeneratorInterface::class);
        $router->method('generate')->willReturn('/upload/init');
        // A small chunk size makes each submitted file span several chunks
        $uploader = new Uploader($this->storage, $this->createStub(UploadUrlGeneratorInterface::class), new EventDispatcher(), chunkSize: 4);
        $uploaders = new ServiceLocator(['default' => static fn (): Uploader => $uploader]);
        $type = new UploadType($router, $this->tokenHandler, $uploaders, allowAnonymous: $allowAnonymous);

        return Forms::createFormFactoryBuilder()
            ->addExtension(new HttpFoundationExtension())
            ->addExtension(new PreloadedExtension([$type], []))
            ->getFormFactory();
    }

    private function nativeFile(string $content, string $name = 'notes.txt', string $mimeType = 'text/plain', int $error = \UPLOAD_ERR_OK): HttpUploadedFile
    {
        $this->temporaryFiles[] = $path = (string) tempnam(sys_get_temp_dir(), 'ux_upload_native_');
        file_put_contents($path, $content);

        return new HttpUploadedFile($path, $name, $mimeType, $error, true);
    }

    private function stage(string $uploader, string $mimeType, int $size, ?string $fieldName = null): UploadedFile
    {
        return new UploadedFileFactory(
            id: bin2hex(random_bytes(16)),
            uploader: $uploader,
            originalName: 'file.bin',
            mimeType: $mimeType,
            size: $size,
            fieldName: $fieldName,
        )->create($this->storage);
    }

    private function payload(UploadedFile $upload): string
    {
        return json_encode([
            'token' => $this->tokenHandler->generate($upload, new UploadContext(fieldName: $upload->getFieldName())),
        ], \JSON_THROW_ON_ERROR);
    }
}
