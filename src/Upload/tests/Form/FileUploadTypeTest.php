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

use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\Upload\Form\FileUploadType;
use Symfony\UX\Upload\Policy\UploadPolicySigner;
use Symfony\UX\Upload\Security\UploadContext;
use Symfony\UX\Upload\Storage\InMemoryStorage;
use Symfony\UX\Upload\Token\UploadTokenHandler;
use Symfony\UX\Upload\Upload\CompletedUpload;
use Symfony\UX\Upload\UploaderInterface;

final class FileUploadTypeTest extends TestCase
{
    private InMemoryStorage $storage;
    private UploadTokenHandler $tokenHandler;
    private FormFactoryInterface $formFactory;

    protected function setUp(): void
    {
        $this->storage = new InMemoryStorage();
        $this->tokenHandler = new UploadTokenHandler(new UriSigner('secret'), $this->storage);
        $router = $this->createStub(UrlGeneratorInterface::class);
        $router->method('generate')->willReturn('/upload/init');
        $type = new FileUploadType($router, $this->tokenHandler);
        $this->formFactory = Forms::createFormFactoryBuilder()
            ->addExtension(new PreloadedExtension([$type], []))
            ->getFormFactory();
    }

    public function testRejectsUploadCreatedByAnotherUploader(): void
    {
        $upload = $this->stage('avatar', 'image/png', 10, 'ux_upload');
        $form = $this->formFactory->create(FileUploadType::class, options: ['uploader' => 'documents']);

        $form->submit($this->payload($upload));

        self::assertFalse($form->isSynchronized());
    }

    public function testRejectsUploadLargerThanFieldLimit(): void
    {
        $upload = $this->stage('default', 'application/pdf', 2048, 'ux_upload');
        $form = $this->formFactory->create(FileUploadType::class, options: ['max_size' => '1K']);

        $form->submit($this->payload($upload));

        self::assertFalse($form->isSynchronized());
    }

    public function testRejectsUploadOutsideFieldMimePolicy(): void
    {
        $upload = $this->stage('default', 'text/plain', 10, 'ux_upload');
        $form = $this->formFactory->create(FileUploadType::class, options: ['allowed_types' => ['image/*']]);

        $form->submit($this->payload($upload));

        self::assertFalse($form->isSynchronized());
    }

    public function testAcceptsUploadMatchingFieldMimeWildcard(): void
    {
        $upload = $this->stage('default', 'image/png', 10, 'ux_upload');
        $form = $this->formFactory->create(FileUploadType::class, options: ['allowed_types' => ['image/*']]);

        $form->submit($this->payload($upload));

        self::assertTrue($form->isSynchronized());
        self::assertEquals($upload, $form->getData());
    }

    public function testRejectsMoreFilesThanFieldLimit(): void
    {
        $first = $this->stage('default', 'text/plain', 10);
        $second = $this->stage('default', 'text/plain', 10);
        $form = $this->formFactory->create(FileUploadType::class, options: [
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
        $router = $this->createStub(UrlGeneratorInterface::class);
        $router->method('generate')->willReturn('/upload/init');
        $type = new FileUploadType($router, $this->tokenHandler, policySigner: $signer);
        $factory = Forms::createFormFactoryBuilder()
            ->addExtension(new PreloadedExtension([$type], []))
            ->getFormFactory();
        $post = $factory->createNamedBuilder('post', FormType::class)
            ->add('attachment', FileUploadType::class)
            ->getForm();
        $profile = $factory->createNamedBuilder('profile', FormType::class)
            ->add('attachment', FileUploadType::class)
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
        $router = $this->createStub(UrlGeneratorInterface::class);
        $router->method('generate')->willReturn('/upload/init');
        $type = new FileUploadType($router, $this->tokenHandler, policySigner: $signer);
        $factory = Forms::createFormFactoryBuilder()
            ->addExtension(new PreloadedExtension([$type], []))
            ->getFormFactory();
        $upload = $this->stage('default', 'text/plain', 10, 'post.attachments.*');
        $post = $factory->createNamedBuilder('post', FormType::class)
            ->add('attachments', CollectionType::class, [
                'entry_type' => FileUploadType::class,
                'data' => [null],
            ])
            ->getForm();
        $entry = $post->get('attachments')->get('0');
        $entry->setData($upload);

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
        // A JS-cloned prototype row uploads under the prototype's policy: the
        // token context carries the normalized path, not a numeric index.
        $upload = $this->stage('default', 'text/plain', 10, 'post.attachments.*');
        $token = $this->tokenHandler->generate($upload, new UploadContext(fieldName: 'post.attachments.*'));

        $post = $this->formFactory->createNamedBuilder('post', FormType::class)
            ->add('attachments', CollectionType::class, [
                'entry_type' => FileUploadType::class,
                'allow_add' => true,
            ])
            ->getForm();

        $post->submit(['attachments' => ['3' => json_encode(['token' => $token], \JSON_THROW_ON_ERROR)]]);

        self::assertTrue($post->get('attachments')->get('3')->isSynchronized());
        self::assertEquals($upload, $post->get('attachments')->get('3')->getData());
    }

    public function testCollectionUploadSurvivesEntryReindexing(): void
    {
        $signer = new UploadPolicySigner(new UriSigner('secret'));
        $router = $this->createStub(UrlGeneratorInterface::class);
        $router->method('generate')->willReturn('/upload/init');
        $type = new FileUploadType($router, $this->tokenHandler, policySigner: $signer);
        $factory = Forms::createFormFactoryBuilder()
            ->addExtension(new PreloadedExtension([$type], []))
            ->getFormFactory();
        $post = $factory->createNamedBuilder('post', FormType::class)
            ->add('attachments', CollectionType::class, [
                'entry_type' => FileUploadType::class,
                'data' => [null, null],
                'allow_delete' => true,
            ])
            ->getForm();

        $secondEntry = $post->createView()->children['attachments']->children[1];
        $originalFieldName = $signer->resolve($secondEntry->vars['stimulus_values']['policyToken'])?->fieldName;
        self::assertSame('post.attachments.*', $originalFieldName);

        $upload = $this->stage('default', 'text/plain', 10, $originalFieldName);
        $token = $this->tokenHandler->generate($upload, new UploadContext(fieldName: $originalFieldName));
        $post->submit([
            'attachments' => [
                json_encode(['token' => $token], \JSON_THROW_ON_ERROR),
            ],
        ]);

        self::assertTrue($post->get('attachments')->get('0')->isSynchronized());
        self::assertEquals($upload, $post->get('attachments')->get('0')->getData());
    }

    public function testUploaderLimitExpansionThrowsInvalidOptionsException(): void
    {
        $uploader = $this->createStub(UploaderInterface::class);
        $uploader->method('getConfig')->willReturn([
            'max_size' => 1024,
            'allowed_types' => ['image/*'],
            'chunk_size' => 65536,
            'integrity_algorithm' => 'sha256',
        ]);
        $container = $this->createStub(\Psr\Container\ContainerInterface::class);
        $container->method('has')->willReturn(true);
        $container->method('get')->willReturn($uploader);
        $router = $this->createStub(UrlGeneratorInterface::class);
        $type = new FileUploadType($router, $this->tokenHandler, $container);
        $factory = Forms::createFormFactoryBuilder()
            ->addExtension(new PreloadedExtension([$type], []))
            ->getFormFactory();

        $this->expectException(InvalidOptionsException::class);
        $factory->create(FileUploadType::class, options: [
            'uploader' => 'documents',
            'max_size' => '2K',
        ])->createView();
    }

    public function testSingleAndMultipleInitialDataRoundTripThroughSignedTokens(): void
    {
        $first = $this->stage('default', 'text/plain', 10, 'ux_upload');
        $second = $this->stage('default', 'text/plain', 20, 'ux_upload');

        $single = $this->formFactory->create(FileUploadType::class, $first);
        $singlePayload = json_decode($single->createView()->vars['value'], true, flags: \JSON_THROW_ON_ERROR);
        self::assertSame('file.bin', $singlePayload['meta']['filename']);
        self::assertSame('text/plain', $singlePayload['meta']['mimeType']);
        self::assertSame(10, $singlePayload['meta']['size']);
        $single->submit(json_encode(['token' => $singlePayload['token']], \JSON_THROW_ON_ERROR));
        self::assertEquals($first, $single->getData());

        $multiple = $this->formFactory->create(FileUploadType::class, [$first, 'ignored', $second], [
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
        $single = $this->formFactory->create(FileUploadType::class);
        $single->submit('');
        self::assertNull($single->getData());

        $multiple = $this->formFactory->create(FileUploadType::class, options: ['multiple' => true]);
        $multiple->submit('');
        self::assertSame([], $multiple->getData());

        self::assertSame('', $this->formFactory->create(FileUploadType::class, new \stdClass())->createView()->vars['value']);
        self::assertSame('', $this->formFactory->create(FileUploadType::class, new \stdClass(), ['multiple' => true])->createView()->vars['value']);
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
            $form = $this->formFactory->create(FileUploadType::class, options: $case['options']);
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
        $container = $this->createStub(\Psr\Container\ContainerInterface::class);
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
        $type = new FileUploadType(
            $router,
            $this->tokenHandler,
            $container,
            $translator,
            $csrf,
            new UploadPolicySigner(new UriSigner('secret')),
        );
        $factory = Forms::createFormFactoryBuilder()
            ->addExtension(new PreloadedExtension([$type], []))
            ->getFormFactory();

        $view = $factory->createNamed('avatar', FileUploadType::class, options: [
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
        $uploader = $this->createStub(UploaderInterface::class);
        $uploader->method('getConfig')->willReturn([
            'max_size' => 100,
            'allowed_types' => ['image/*'],
            'chunk_size' => 65536,
            'integrity_algorithm' => 'sha384',
            'compression' => false,
        ]);
        $container = $this->createStub(\Psr\Container\ContainerInterface::class);
        $container->method('has')->willReturn(true);
        $container->method('get')->willReturn($uploader);
        $router = $this->createStub(UrlGeneratorInterface::class);
        $router->method('generate')->willReturn('/upload');
        $type = new FileUploadType($router, $this->tokenHandler, $container);
        $factory = Forms::createFormFactoryBuilder()
            ->addExtension(new PreloadedExtension([$type], []))
            ->getFormFactory();
        $view = $factory->create(FileUploadType::class)->createView();

        self::assertSame(100, $view->vars['max_size']);
        self::assertSame(['image/*'], $view->vars['allowed_types']);
        self::assertSame('sha384', $view->vars['stimulus_values']['integrityAlgorithm']);

        $tooLarge = $factory->create(FileUploadType::class);
        $tooLarge->submit($this->payload($this->stage('default', 'image/png', 101, 'ux_upload')));
        self::assertFalse($tooLarge->isSynchronized());

        $wrongType = $factory->create(FileUploadType::class);
        $wrongType->submit($this->payload($this->stage('default', 'text/plain', 10, 'ux_upload')));
        self::assertFalse($wrongType->isSynchronized());
    }

    public function testUnlimitedUploaderIsInheritedByTheField(): void
    {
        $uploader = $this->createStub(UploaderInterface::class);
        $uploader->method('getConfig')->willReturn([
            'max_size' => 0,
            'allowed_types' => [],
            'chunk_size' => 5 * 1024 * 1024,
            'integrity_algorithm' => 'sha256',
            'compression' => false,
        ]);
        $container = $this->createStub(\Psr\Container\ContainerInterface::class);
        $container->method('has')->willReturn(true);
        $container->method('get')->willReturn($uploader);
        $router = $this->createStub(UrlGeneratorInterface::class);
        $router->method('generate')->willReturn('/upload');
        $signer = new UploadPolicySigner(new UriSigner('secret'));
        $type = new FileUploadType($router, $this->tokenHandler, $container, policySigner: $signer);
        $factory = Forms::createFormFactoryBuilder()
            ->addExtension(new PreloadedExtension([$type], []))
            ->getFormFactory();
        $form = $factory->create(FileUploadType::class, options: ['uploader' => 'unlimited']);
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
        $uploader = $this->createStub(UploaderInterface::class);
        $uploader->method('getConfig')->willReturn([
            'max_size' => 200 * 1024 * 1024,
            'allowed_types' => [],
            'chunk_size' => 5 * 1024 * 1024,
            'integrity_algorithm' => 'sha256',
            'compression' => false,
        ]);
        $container = $this->createStub(\Psr\Container\ContainerInterface::class);
        $container->method('has')->willReturn(true);
        $container->method('get')->willReturn($uploader);
        $type = new FileUploadType($this->createStub(UrlGeneratorInterface::class), $this->tokenHandler, $container);
        $factory = Forms::createFormFactoryBuilder()
            ->addExtension(new PreloadedExtension([$type], []))
            ->getFormFactory();

        $view = $factory->create(FileUploadType::class, options: [
            'uploader' => 'large',
            'max_size' => '100M',
        ])->createView();

        self::assertSame(100 * 1024 * 1024, $view->vars['max_size']);
    }

    public function testRejectsInvalidUploaderService(): void
    {
        $container = $this->createStub(\Psr\Container\ContainerInterface::class);
        $container->method('has')->willReturn(true);
        $container->method('get')->willReturn(new \stdClass());
        $type = new FileUploadType($this->createStub(UrlGeneratorInterface::class), $this->tokenHandler, $container);
        $factory = Forms::createFormFactoryBuilder()
            ->addExtension(new PreloadedExtension([$type], []))
            ->getFormFactory();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('must implement');

        $factory->create(FileUploadType::class, options: ['uploader' => 'documents'])->createView();
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

        $view = $this->formFactory->create(FileUploadType::class, options: [
            'max_size' => '2G',
            'max_files' => 3,
            'allowed_types' => $mimeTypes,
            'multiple' => true,
        ])->createView();
        self::assertStringContainsString('2 GB', $view->vars['help_text']);
        self::assertStringContainsString('Up to 3 files', $view->vars['help_text']);
        self::assertStringNotContainsString('--', $view->vars['help_text']);
        foreach (['DOCX', 'JPEG', 'PNG', 'GIF', 'WebP', 'SVG', 'PDF', 'TXT', 'CSV', 'JSON', 'ZIP', 'Images', 'Videos', 'Audio', 'VND.CUSTOM', 'README'] as $label) {
            self::assertStringContainsString($label, $view->vars['help_text']);
        }

        self::assertStringContainsString('2 MB', $this->formFactory->create(FileUploadType::class, options: ['max_size' => '2M'])->createView()->vars['help_text']);
        self::assertStringContainsString('2 KB', $this->formFactory->create(FileUploadType::class, options: ['max_size' => '2K'])->createView()->vars['help_text']);
        self::assertStringContainsString('2 B', $this->formFactory->create(FileUploadType::class, options: ['max_size' => '2'])->createView()->vars['help_text']);
    }

    public function testUploaderMimePolicyCannotBeExpanded(): void
    {
        $uploader = $this->createStub(UploaderInterface::class);
        $uploader->method('getConfig')->willReturn([
            'max_size' => 1024,
            'allowed_types' => ['image/*'],
            'chunk_size' => 65536,
            'integrity_algorithm' => 'sha256',
        ]);
        $container = $this->createStub(\Psr\Container\ContainerInterface::class);
        $container->method('has')->willReturn(true);
        $container->method('get')->willReturn($uploader);
        $type = new FileUploadType($this->createStub(UrlGeneratorInterface::class), $this->tokenHandler, $container);
        $factory = Forms::createFormFactoryBuilder()
            ->addExtension(new PreloadedExtension([$type], []))
            ->getFormFactory();

        $this->expectException(InvalidOptionsException::class);
        $factory->create(FileUploadType::class, options: [
            'uploader' => 'images',
            'allowed_types' => ['application/pdf'],
        ])->createView();
    }

    public function testTypeMetadataAndOptionValidation(): void
    {
        $type = new FileUploadType($this->createStub(UrlGeneratorInterface::class), $this->tokenHandler);
        self::assertSame(\Symfony\Component\Form\Extension\Core\Type\HiddenType::class, $type->getParent());
        self::assertSame('ux_upload', $type->getBlockPrefix());

        $this->expectException(InvalidOptionsException::class);
        $this->formFactory->create(FileUploadType::class, options: ['max_files' => 0]);
    }

    public function testLayoutAndPreviewAreIndependentViewOptions(): void
    {
        $compactForm = $this->formFactory->create(FileUploadType::class, options: [
            'widget_attr' => ['class' => 'upload-shell'],
        ]);
        $compact = $compactForm->createView();
        self::assertSame('compact', $compact->vars['layout']);
        self::assertFalse($compact->vars['show_preview']);
        self::assertSame(['class' => 'upload-shell'], $compact->vars['upload_attr']);
        self::assertFalse($compactForm->getConfig()->getOption('error_bubbling'));

        $dropzone = $this->formFactory->create(FileUploadType::class, options: [
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
        $this->formFactory->create(FileUploadType::class, options: ['layout' => 'gallery']);
    }

    private function stage(string $uploader, string $mimeType, int $size, ?string $fieldName = null): CompletedUpload
    {
        $now = new \DateTimeImmutable()->setTimestamp(time());
        $id = bin2hex(random_bytes(16));

        return new CompletedUpload(
            id: $id,
            uploader: $uploader,
            path: '.tmp/completed/'.($now->getTimestamp() + 3600).'-'.$id,
            originalName: 'file.bin',
            mimeType: $mimeType,
            size: $size,
            createdAt: $now,
            expiresAt: $now->modify('+1 hour'),
            fieldName: $fieldName,
            access: new \Symfony\UX\Upload\Upload\CompletedUploadAccess($this->storage),
        );
    }

    private function payload(CompletedUpload $upload): string
    {
        return json_encode([
            'token' => $this->tokenHandler->generate($upload, new UploadContext(fieldName: $upload->getFieldName())),
        ], \JSON_THROW_ON_ERROR);
    }
}
