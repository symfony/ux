<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Upload\Form;

use Psr\Container\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\File\UploadedFile as HttpUploadedFile;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\Upload\Exception\ExceptionInterface;
use Symfony\UX\Upload\Exception\ValidationException;
use Symfony\UX\Upload\Policy\UploadPolicy;
use Symfony\UX\Upload\Policy\UploadPolicySigner;
use Symfony\UX\Upload\Security\AnonymousUploadContextResolver;
use Symfony\UX\Upload\Security\UploadContext;
use Symfony\UX\Upload\Security\UploadContextResolverInterface;
use Symfony\UX\Upload\Token\UploadTokenHandler;
use Symfony\UX\Upload\Upload\UploadedFile;
use Symfony\UX\Upload\Uploader;
use Symfony\UX\Upload\UploaderInterface;
use Symfony\UX\Upload\Util\SizeParser;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class UploadType extends AbstractType
{
    private const TRANSLATION_DOMAIN = 'UXUploadBundle';
    private const TRANSLATION_FALLBACKS = [
        'ux_upload.drop_files' => 'Drop files here or click to browse',
        'ux_upload.upload' => 'Upload',
        'ux_upload.cancel' => 'Cancel',
        'ux_upload.remove' => 'Remove',
        'ux_upload.retry' => 'Retry',
        'ux_upload.pending' => 'Pending',
        'ux_upload.cancelled' => 'Cancelled',
        'ux_upload.upload_failed' => 'Upload failed',
        'ux_upload.max_files_reached' => 'Maximum number of files reached',
        'ux_upload.file_too_large' => 'File too large (max %max_size%)',
        'ux_upload.file_type_not_allowed' => 'File type not allowed',
        'ux_upload.summary_all_complete' => '%count% files uploaded',
        'ux_upload.summary_uploading' => 'Uploading... %completed% of %total% complete',
        'ux_upload.summary_partial' => '%completed% of %total% uploaded, %failed% failed',
        'ux_upload.summary_uploading_with_errors' => 'Uploading... %completed% of %total% complete, %failed% failed',
        'ux_upload.summary_default' => '%completed% of %total% files uploaded',
        'ux_upload.dropzone_label' => 'File upload area. Drop files or press to browse.',
        'ux_upload.announce_started' => '%filename%: upload started',
        'ux_upload.announce_complete' => '%filename%: upload complete',
        'ux_upload.announce_failed' => '%filename%: upload failed',
        'ux_upload.announce_cancelled' => '%filename%: upload cancelled',
        'ux_upload.pause' => 'Pause',
        'ux_upload.resume' => 'Resume',
        'ux_upload.paused' => 'Paused',
        'ux_upload.announce_paused' => '%filename%: upload paused',
        'ux_upload.announce_resumed' => '%filename%: upload resumed',
        'ux_upload.upload_in_progress' => 'Wait for the upload to finish',
        'ux_upload.help.max_size' => 'Up to %size%',
        'ux_upload.help.max_files' => 'Up to %count% files',
    ];

    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly UploadTokenHandler $tokenHandler,
        private readonly ?ContainerInterface $uploaders = null,
        private readonly ?TranslatorInterface $translator = null,
        private readonly ?CsrfTokenManagerInterface $csrfTokenManager = null,
        private readonly ?UploadPolicySigner $policySigner = null,
        private readonly UploadContextResolverInterface $contextResolver = new AnonymousUploadContextResolver(),
        private readonly bool $allowAnonymous = false,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $fieldName = $this->getFieldName($builder);
        $updateFieldName = function (FormEvent $event) use (&$fieldName): void {
            $fieldName = $this->getFieldName($event->getForm());
        };
        $builder->addEventListener(FormEvents::PRE_SET_DATA, $updateFieldName);
        $builder->addEventListener(FormEvents::PRE_SUBMIT, $updateFieldName);
        $builder->addModelTransformer(new CallbackTransformer(
            fn (mixed $value): ?string => $this->toSubmittedValue($value, $options['multiple']),
            function (mixed $input) use ($options, &$fieldName): mixed {
                if ($input instanceof HttpUploadedFile || \is_array($input)) {
                    $files = $this->storeSubmittedFiles($input instanceof HttpUploadedFile ? [$input] : $input, $options, $fieldName);

                    return $options['multiple'] ? $files : ($files[0] ?? null);
                }

                if (!\is_string($input) || !$input) {
                    return $options['multiple'] ? [] : null;
                }

                $decoded = json_decode($input, true);

                if (!\is_array($decoded)) {
                    throw new TransformationFailedException('The submitted upload payload is invalid.');
                }

                if ($options['multiple']) {
                    /** @var int $maxFiles */
                    $maxFiles = $options['max_files'];
                    if (!array_is_list($decoded)) {
                        throw new TransformationFailedException('The submitted upload payload is invalid.');
                    }
                    if ($maxFiles > 0 && \count($decoded) > $maxFiles) {
                        throw new TransformationFailedException(\sprintf('At most %d files may be submitted.', $maxFiles));
                    }

                    $files = [];
                    foreach ($decoded as $entry) {
                        if (\is_array($entry) && isset($entry['token']) && \is_string($entry['token'])) {
                            $file = $this->tokenHandler->resolve($entry['token'], $this->getUploadContext($fieldName));
                            if ($file) {
                                $this->validateSubmittedUpload($file, $options);
                                $files[] = $file;
                            } else {
                                throw new TransformationFailedException('The submitted upload token is invalid or expired.');
                            }
                        } else {
                            throw new TransformationFailedException('The submitted upload payload is invalid.');
                        }
                    }

                    return $files;
                }

                if (isset($decoded['token']) && \is_string($decoded['token'])) {
                    $file = $this->tokenHandler->resolve($decoded['token'], $this->getUploadContext($fieldName));
                    if (null === $file) {
                        throw new TransformationFailedException('The submitted upload token is invalid or expired.');
                    }
                    $this->validateSubmittedUpload($file, $options);

                    return $file;
                }

                throw new TransformationFailedException('The submitted upload payload is invalid.');
            }
        ));
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        /** @var string $uploaderName */
        $uploaderName = $options['uploader'];
        $integrityAlgorithm = 'sha256';

        if (null !== ($uploader = $this->getUploader($uploaderName))) {
            $config = $uploader->getConfig();
            $integrityAlgorithm = $config['integrity_algorithm'];

            if ([] === $options['allowed_types'] && [] !== $config['allowed_types']) {
                $options['allowed_types'] = $config['allowed_types'];
            }
        }

        /** @var string $maxSize */
        $maxSize = $options['max_size'];
        $parsedMaxSize = SizeParser::parse($maxSize);
        /** @var list<string> $effectiveAllowedTypes */
        $effectiveAllowedTypes = $options['allowed_types'];
        /** @var int $effectiveMaxFiles */
        $effectiveMaxFiles = $options['max_files'];
        if (isset($config)) {
            foreach ($effectiveAllowedTypes as $allowedType) {
                if (!$this->isTypeRestrictionAllowed($allowedType, $config['allowed_types'])) {
                    throw new InvalidOptionsException(\sprintf('The "allowed_types" option "%s" is not allowed by the "%s" uploader policy.', $allowedType, $uploaderName));
                }
            }
        }
        $fieldName = $this->getFieldName($form);
        $policyToken = $this->policySigner?->issue($uploaderName, $parsedMaxSize, $effectiveAllowedTypes, $effectiveMaxFiles, $fieldName);

        $directUrl = $this->urlGenerator->generate('ux_upload_direct');
        $initUrl = $this->urlGenerator->generate('ux_upload_init');
        $removeUrl = $this->urlGenerator->generate('ux_upload_remove');
        $chunkSize = $config['chunk_size'] ?? 5 * 1024 * 1024;
        $compression = ($options['compression'] ?? false) && ($config['compression'] ?? true);

        if (!\is_string($form->getViewData())) {
            // Files were submitted without JavaScript: render the stored uploads, never the submitted objects
            $view->vars['value'] = $this->toSubmittedValue($form->getData(), $options['multiple']) ?? '';
        }

        // Pass view vars for the Twig template
        $view->vars['max_size'] = $parsedMaxSize;
        $view->vars['max_files'] = $options['max_files'];
        $view->vars['allowed_types'] = $options['allowed_types'];
        $view->vars['compression'] = $compression;
        $view->vars['multiple'] = $options['multiple'] ?? false;
        $view->vars['help_text'] = $options['help_text'] ?? $this->generateHelpText($options);
        $view->vars['auto_upload'] = $options['auto_upload'];
        $view->vars['show_preview'] = $options['show_preview'];
        $view->vars['layout'] = $options['layout'];
        $view->vars['uploader'] = $uploaderName;
        $view->vars['upload_attr'] = $options['widget_attr'];

        // Pre-compute all Stimulus controller values (including translated labels)
        // so templates don't need to duplicate the translation calls.
        $view->vars['stimulus_values'] = [
            'directUrl' => $directUrl,
            'chunkSize' => $chunkSize,
            'initUrl' => $initUrl,
            'removeUrl' => $removeUrl,
            'csrfToken' => $this->csrfTokenManager?->getToken('ux_upload')->getValue(),
            'maxSize' => $parsedMaxSize,
            'maxFiles' => $options['max_files'],
            'allowedTypes' => $options['allowed_types'],
            'compression' => $compression,
            'multiple' => $options['multiple'] ?? false,
            'required' => $options['required'] ?? false,
            'autoUpload' => $options['auto_upload'],
            'showPreview' => $options['show_preview'],
            'uploader' => $uploaderName,
            'integrityAlgorithm' => $integrityAlgorithm,
            'policyToken' => $policyToken,
            'labelPending' => $this->trans('ux_upload.pending'),
            'labelCancelled' => $this->trans('ux_upload.cancelled'),
            'labelUploadFailed' => $this->trans('ux_upload.upload_failed'),
            'labelMaxFiles' => $this->trans('ux_upload.max_files_reached'),
            'labelFileTooLarge' => $this->trans('ux_upload.file_too_large'),
            'labelFileTypeNotAllowed' => $this->trans('ux_upload.file_type_not_allowed'),
            'labelSummaryAllComplete' => $this->trans('ux_upload.summary_all_complete'),
            'labelSummaryUploading' => $this->trans('ux_upload.summary_uploading'),
            'labelSummaryPartial' => $this->trans('ux_upload.summary_partial'),
            'labelSummaryUploadingWithErrors' => $this->trans('ux_upload.summary_uploading_with_errors'),
            'labelSummaryDefault' => $this->trans('ux_upload.summary_default'),
            'labelAnnounceStarted' => $this->trans('ux_upload.announce_started'),
            'labelAnnounceComplete' => $this->trans('ux_upload.announce_complete'),
            'labelAnnounceFailed' => $this->trans('ux_upload.announce_failed'),
            'labelAnnounceCancelled' => $this->trans('ux_upload.announce_cancelled'),
            'labelPaused' => $this->trans('ux_upload.paused'),
            'labelAnnouncePaused' => $this->trans('ux_upload.announce_paused'),
            'labelAnnounceResumed' => $this->trans('ux_upload.announce_resumed'),
            'labelUploadInProgress' => $this->trans('ux_upload.upload_in_progress'),
        ];

        // Translated strings for template use (button labels, ARIA, etc.)
        $view->vars['label_dropzone_aria'] = $this->trans('ux_upload.dropzone_label');
        $view->vars['label_pending'] = $this->trans('ux_upload.pending');
        $view->vars['label_pause'] = $this->trans('ux_upload.pause');
        $view->vars['label_resume'] = $this->trans('ux_upload.resume');
        $view->vars['label_cancel'] = $this->trans('ux_upload.cancel');
        $view->vars['label_remove'] = $this->trans('ux_upload.remove');
        $view->vars['label_retry'] = $this->trans('ux_upload.retry');
        $view->vars['label_upload'] = $this->trans('ux_upload.upload');
        $view->vars['label_drop_files'] = $this->trans('ux_upload.drop_files');
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['multipart'] = true;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'uploader' => 'default',
            'max_size' => null,
            'max_files' => null,
            'allowed_types' => [],
            'help_text' => null,
            'auto_upload' => true,
            'show_preview' => false,
            'layout' => 'compact',
            'compression' => false,
            'multiple' => false,
            'widget_attr' => [],
            'required' => true,
            // The view data is the signed token, not the file: FileType's "data_class" cannot apply
            'data_class' => null,
            'error_bubbling' => false,
            'allow_file_upload' => true,
            'invalid_message' => 'The uploaded file reference is invalid or has expired.',
        ]);

        $resolver->setNormalizer('max_size', function (Options $options, ?string $maxSize): string {
            /** @var string $uploaderName */
            $uploaderName = $options['uploader'];
            $uploader = $this->getUploader($uploaderName);
            $uploaderMaxSize = $uploader?->getConfig()['max_size'] ?? Uploader::DEFAULT_MAX_SIZE;

            if (null === $maxSize) {
                return (string) $uploaderMaxSize;
            }

            $parsedMaxSize = SizeParser::parse($maxSize);
            if (null !== $uploader && $uploaderMaxSize > 0 && (0 === $parsedMaxSize || $parsedMaxSize > $uploaderMaxSize)) {
                throw new InvalidOptionsException(\sprintf('The "max_size" option cannot exceed the "%s" uploader limit of %d bytes.', $uploaderName, $uploaderMaxSize));
            }

            return $maxSize;
        });

        $resolver->setAllowedTypes('uploader', 'string');
        $resolver->setAllowedTypes('max_size', ['string', 'null']);
        $resolver->setAllowedTypes('max_files', ['int', 'null']);
        $resolver->setAllowedTypes('allowed_types', 'array');
        $resolver->setAllowedTypes('help_text', ['string', 'null']);
        $resolver->setAllowedTypes('auto_upload', 'bool');
        $resolver->setAllowedTypes('show_preview', 'bool');
        $resolver->setAllowedTypes('layout', 'string');
        $resolver->setAllowedValues('layout', ['compact', 'dropzone']);
        $resolver->setAllowedTypes('compression', 'bool');
        $resolver->setAllowedTypes('multiple', 'bool');
        $resolver->setAllowedTypes('widget_attr', 'array');
        $resolver->setAllowedValues('max_files', static fn (?int $maxFiles): bool => null === $maxFiles || $maxFiles >= 1);
        // 0 means "no limit": the default of a multiple field, as with FileType
        $resolver->setNormalizer('max_files', static fn (Options $options, ?int $maxFiles): int => $maxFiles ?? ($options['multiple'] ? 0 : 1));
    }

    private function toSubmittedValue(mixed $value, bool $multiple): ?string
    {
        if (!$value) {
            return null;
        }

        $entry = fn (UploadedFile $file): array => [
            'token' => $this->tokenHandler->generate($file, $this->getUploadContext($file->getFieldName())),
            'meta' => [
                'filename' => $file->getClientOriginalName(),
                'mimeType' => $file->getMimeType(),
                'size' => $file->getSize(),
            ],
        ];

        if ($multiple) {
            if (!\is_array($value)) {
                return null;
            }

            return json_encode(array_map($entry, array_values(array_filter($value, static fn (mixed $file): bool => $file instanceof UploadedFile)))) ?: null;
        }

        return $value instanceof UploadedFile ? (json_encode($entry($value)) ?: null) : null;
    }

    /**
     * @param array<mixed>         $files
     * @param array<string, mixed> $options
     *
     * @return list<UploadedFile>
     */
    private function storeSubmittedFiles(array $files, array $options, ?string $fieldName): array
    {
        $files = array_values(array_filter($files, static fn (mixed $file): bool => $file instanceof HttpUploadedFile));
        /** @var int $maxFiles */
        $maxFiles = $options['max_files'];
        if ($maxFiles > 0 && \count($files) > $maxFiles) {
            throw new TransformationFailedException(\sprintf('At most %d files may be submitted.', $maxFiles));
        }

        return array_map(fn (HttpUploadedFile $file): UploadedFile => $this->storeSubmittedFile($file, $options, $fieldName), $files);
    }

    /**
     * Sends a file submitted with the form through the uploader, as the
     * Stimulus controller does before submission.
     *
     * @param array<string, mixed> $options
     */
    private function storeSubmittedFile(HttpUploadedFile $file, array $options, ?string $fieldName): UploadedFile
    {
        if ($file instanceof UploadedFile) {
            $this->validateSubmittedUpload($file, $options);

            return $file;
        }

        if (!$file->isValid()) {
            throw $this->refusal($file->getErrorMessage(), 'The file could not be uploaded.');
        }

        /** @var string $uploaderName */
        $uploaderName = $options['uploader'];
        if (null === $uploader = $this->getUploader($uploaderName)) {
            throw new TransformationFailedException(\sprintf('Uploader "%s" is not available to store a file submitted with the form.', $uploaderName));
        }

        $context = $this->getUploadContext($fieldName);
        if (!$this->allowAnonymous && null === $context->ownerId && null === $context->tenantId) {
            throw new TransformationFailedException('Anonymous uploads are disabled.');
        }

        /** @var string $maxSize */
        $maxSize = $options['max_size'];
        /** @var list<string> $allowedTypes */
        $allowedTypes = $options['allowed_types'] ?: $uploader->getConfig()['allowed_types'];
        /** @var int $maxFiles */
        $maxFiles = $options['max_files'];
        $policy = new UploadPolicy($uploaderName, SizeParser::parse($maxSize), $allowedTypes, $maxFiles, time() + 60, $fieldName);
        $size = (int) $file->getSize();
        // The file is at hand: judge its content, not the type the client announced
        $mimeType = $file->getMimeType() ?? $file->getClientMimeType();

        try {
            if ($policy->maxSize > 0 && $size > $policy->maxSize) {
                throw new ValidationException(\sprintf('File size exceeds form policy limit of %d bytes.', $policy->maxSize));
            }
            if (!$policy->allows($mimeType)) {
                throw new ValidationException(\sprintf('MIME type "%s" is not allowed by the form policy.', $mimeType));
            }

            $pending = $uploader->initializeUpload($file->getClientOriginalName(), $size, $mimeType, context: $context, policy: $policy);
            try {
                $content = $file->openFile('rb');
                for ($index = 0; $index < $pending->totalChunks; ++$index) {
                    $uploader->storeChunk($pending->uploadId, $index, (string) $content->fread($pending->chunkSize));
                }

                $upload = $uploader->completeUpload($pending->uploadId);
            } catch (\Throwable $exception) {
                try {
                    $uploader->cancelUpload($pending->uploadId);
                } catch (\Throwable) {
                    // Preserve the original upload failure.
                }

                throw $exception;
            }
        } catch (ValidationException $exception) {
            throw $this->refusal($exception->getMessage(), $exception->getMessage(), $exception);
        } catch (ExceptionInterface $exception) {
            throw $this->refusal($exception->getMessage(), 'The file could not be uploaded.', $exception);
        }

        $this->validateSubmittedUpload($upload, $options);

        return $upload;
    }

    private function refusal(string $reason, string $message, ?\Throwable $previous = null): TransformationFailedException
    {
        return new TransformationFailedException($reason, 0, $previous, $message);
    }

    /**
     * @param array<string, mixed> $options
     */
    private function validateSubmittedUpload(UploadedFile $upload, array $options): void
    {
        /** @var string $uploaderName */
        $uploaderName = $options['uploader'];
        if (!hash_equals($uploaderName, $upload->getUploaderName())) {
            throw new TransformationFailedException(\sprintf('The submitted upload was created by uploader "%s", but this field requires "%s".', $upload->getUploaderName(), $uploaderName));
        }

        /** @var string $configuredMaxSize */
        $configuredMaxSize = $options['max_size'];
        $maxSize = SizeParser::parse($configuredMaxSize);
        /** @var list<string> $allowedTypes */
        $allowedTypes = $options['allowed_types'];

        if (null !== ($uploader = $this->getUploader($uploaderName))) {
            $uploaderConfig = $uploader->getConfig();
            if ([] === $allowedTypes) {
                $allowedTypes = $uploaderConfig['allowed_types'];
            }
        }

        if ($maxSize > 0 && $upload->getSize() > $maxSize) {
            throw new TransformationFailedException(\sprintf('The submitted upload exceeds the field limit of %d bytes.', $maxSize));
        }
        if ([] !== $allowedTypes && !$this->isTypeRestrictionAllowed($upload->getMimeType(), $allowedTypes)) {
            throw new TransformationFailedException(\sprintf('The submitted upload MIME type "%s" is not allowed by this field.', $upload->getMimeType()));
        }
    }

    /**
     * @param array<string, mixed> $options
     */
    private function generateHelpText(array $options): string
    {
        $parts = [];

        // Format max size (skip if unlimited or the global default)
        /** @var string $maxSize */
        $maxSize = $options['max_size'];
        $bytes = SizeParser::parse($maxSize);
        if (0 !== $bytes && Uploader::DEFAULT_MAX_SIZE !== $bytes) {
            $parts[] = $this->trans('ux_upload.help.max_size', ['%size%' => $this->formatBytes($bytes)]);
        }

        // Format allowed types (technical labels, not translated)
        /** @var string[] $allowedTypes */
        $allowedTypes = $options['allowed_types'];
        if ([] !== $allowedTypes) {
            $labels = array_map($this->formatMimeType(...), $allowedTypes);
            $parts[] = implode(', ', array_unique($labels));
        }

        // Format max files (skip if 1)
        /** @var int $maxFiles */
        $maxFiles = $options['max_files'];
        if ($maxFiles > 1) {
            $parts[] = $this->trans('ux_upload.help.max_files', ['%count%' => $maxFiles]);
        }

        return implode(' - ', $parts);
    }

    /**
     * @param list<string> $uploaderTypes
     */
    private function isTypeRestrictionAllowed(string $formType, array $uploaderTypes): bool
    {
        if ([] === $uploaderTypes || \in_array($formType, $uploaderTypes, true)) {
            return true;
        }
        foreach ($uploaderTypes as $allowed) {
            if (str_ends_with($allowed, '/*') && str_starts_with($formType, substr($allowed, 0, -1))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, string|int> $parameters
     */
    private function trans(string $id, array $parameters = []): string
    {
        $message = self::TRANSLATION_FALLBACKS[$id] ?? $id;

        if (null === $this->translator) {
            return strtr($message, array_map(strval(...), $parameters));
        }

        return $this->translator->trans($id, $parameters, self::TRANSLATION_DOMAIN);
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1024 * 1024 * 1024) {
            return round($bytes / (1024 * 1024 * 1024), 1).' GiB';
        }
        if ($bytes >= 1024 * 1024) {
            return round($bytes / (1024 * 1024), 1).' MiB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 1).' KiB';
        }

        return $bytes.' B';
    }

    private function formatMimeType(string $type): string
    {
        return match (true) {
            str_starts_with($type, '.') => strtoupper(ltrim($type, '.')),
            'image/jpeg' === $type => 'JPEG',
            'image/png' === $type => 'PNG',
            'image/gif' === $type => 'GIF',
            'image/webp' === $type => 'WebP',
            'image/svg+xml' === $type => 'SVG',
            'application/pdf' === $type => 'PDF',
            'text/plain' === $type => 'TXT',
            'text/csv' === $type => 'CSV',
            'application/json' === $type => 'JSON',
            'application/zip' === $type => 'ZIP',
            'image/*' === $type => 'Images',
            'video/*' === $type => 'Videos',
            'audio/*' === $type => 'Audio',
            str_contains($type, '/') => strtoupper(explode('/', $type)[1]),
            default => $type,
        };
    }

    private function getUploadContext(?string $fieldName): UploadContext
    {
        $identity = $this->contextResolver->resolve();

        return new UploadContext($identity->ownerId, $identity->tenantId, $fieldName);
    }

    private function getUploader(string $name): ?UploaderInterface
    {
        if (null === $this->uploaders || !$this->uploaders->has($name)) {
            return null;
        }

        $uploader = $this->uploaders->get($name);
        if (!$uploader instanceof UploaderInterface) {
            throw new \LogicException(\sprintf('Uploader service "%s" must implement "%s".', $name, UploaderInterface::class));
        }

        return $uploader;
    }

    private function getFieldName(FormBuilderInterface|FormInterface $form): string
    {
        if ($form instanceof FormBuilderInterface) {
            return $form->getName();
        }

        $names = [];
        for ($current = $form; null !== $current; $current = $current->getParent()) {
            if ('' !== $current->getName()) {
                array_unshift($names, $current->getName());
            }
        }

        // Collection entries share one field identity: numeric indexes and
        // prototype placeholders ("__name__") normalize to a wildcard so a
        // token uploaded through a JS-cloned prototype row still resolves in
        // the numbered row it lands in at submit time.
        return implode('.', array_map(
            static fn (string $name): string => preg_match('/^(?:\d+|__\w+__)$/', $name) ? '*' : $name,
            $names,
        ));
    }

    public function getParent(): string
    {
        return HiddenType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'ux_upload';
    }
}
