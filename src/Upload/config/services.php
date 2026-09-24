<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Symfony\UX\Upload\Controller\UploadController;
use Symfony\UX\Upload\EventListener\FileValidationListener;
use Symfony\UX\Upload\Form\FileUploadType;
use Symfony\UX\Upload\Policy\UploadPolicySigner;
use Symfony\UX\Upload\Security\AnonymousUploadContextResolver;
use Symfony\UX\Upload\Security\UploadContextResolverInterface;
use Symfony\UX\Upload\Storage\LocalStorage;
use Symfony\UX\Upload\Storage\PrunableStorageInterface;
use Symfony\UX\Upload\Storage\StorageInterface;
use Symfony\UX\Upload\Token\ResumeTokenHandler;
use Symfony\UX\Upload\Token\UploadTokenHandler;
use Symfony\UX\Upload\Upload\CompletedUploadAccess;
use Symfony\UX\Upload\Uploader;
use Symfony\UX\Upload\UploaderInterface;
use Symfony\UX\Upload\Url\SymfonyUploadUrlGenerator;
use Symfony\UX\Upload\Url\UploadUrlGeneratorInterface;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
            ->private()
    ;

    // -- Token ---------------------------------------------------------------

    $services->set(CompletedUploadAccess::class)
        ->arg('$storage', service(StorageInterface::class))
    ;
    $services->set('ux_upload.token_handler', UploadTokenHandler::class)
        ->arg('$signer', service('uri_signer'))
        ->arg('$access', service(CompletedUploadAccess::class))
        ->arg('$contextResolver', service(UploadContextResolverInterface::class))
    ;
    $services->set(UploadPolicySigner::class)
        ->arg('$signer', service('uri_signer'))
    ;
    $services->set(ResumeTokenHandler::class)
        ->arg('$signer', service('uri_signer'))
    ;
    // Autowiring alias so LiveComponent's ComponentWithUploadTrait (and any
    // user service) can type-hint UploadTokenHandler to resolve the upload token.
    $services->alias(UploadTokenHandler::class, 'ux_upload.token_handler');

    // -- Validation ----------------------------------------------------------

    $services->set('ux_upload.listener.file_validation', FileValidationListener::class)
        ->arg('$mimeTypes', service('mime_types'))
        ->arg('$uploaders', service('ux_upload.uploaders'))
        ->arg('$allowedTypes', [])
        ->arg('$maxSize', Uploader::DEFAULT_MAX_SIZE)
        ->arg('$logger', service('logger')->nullOnInvalid())
        ->tag('kernel.event_listener', [
            'event' => 'Symfony\UX\Upload\Event\UploadAssembledEvent',
            'priority' => FileValidationListener::PRIORITY,
        ])
    ;

    // -- URL generation ------------------------------------------------------

    $services->set(SymfonyUploadUrlGenerator::class)
        ->arg('$urlGenerator', service('router'))
        ->arg('$uriSigner', service('uri_signer'))
    ;

    $services->alias(UploadUrlGeneratorInterface::class, SymfonyUploadUrlGenerator::class)
        ->public()
    ;

    // -- Storage -------------------------------------------------------------

    $services->set(LocalStorage::class)
        ->arg('$directory', abstract_arg('storage directory, set in loadExtension()'))
        ->arg('$tempDir', abstract_arg('temp directory, set in loadExtension()'))
    ;

    $services->alias(StorageInterface::class, LocalStorage::class)
        ->public()
    ;

    $services->alias(PrunableStorageInterface::class, LocalStorage::class)
        ->public()
    ;

    $services->set(AnonymousUploadContextResolver::class);
    $services->alias(UploadContextResolverInterface::class, AnonymousUploadContextResolver::class)->public();

    // -- Uploader ------------------------------------------------------------

    $services->set(Uploader::class)
        ->arg('$storage', service(StorageInterface::class))
        ->arg('$urlGenerator', service(UploadUrlGeneratorInterface::class))
        ->arg('$dispatcher', service('event_dispatcher'))
        ->arg('$lockFactory', service('lock.factory'))
        ->arg('$completedUploadAccess', service(CompletedUploadAccess::class))
    ;

    $services->alias(UploaderInterface::class, Uploader::class)
        ->public()
    ;

    // -- Form ----------------------------------------------------------------

    $services->set('ux_upload.form.file_upload_type', FileUploadType::class)
        ->arg('$urlGenerator', service('router'))
        ->arg('$tokenHandler', service('ux_upload.token_handler'))
        ->arg('$uploaders', service('ux_upload.uploaders'))
        ->arg('$translator', service('translator')->nullOnInvalid())
        ->arg('$csrfTokenManager', service('security.csrf.token_manager')->nullOnInvalid())
        ->arg('$policySigner', service(UploadPolicySigner::class))
        ->arg('$contextResolver', service(UploadContextResolverInterface::class))
        ->tag('form.type')
    ;

    // -- Controller ----------------------------------------------------------

    $services->set('ux_upload.controller.upload', UploadController::class)
        ->arg('$uploader', service(UploaderInterface::class))
        ->arg('$urlGenerator', service(UploadUrlGeneratorInterface::class))
        ->arg('$tokenHandler', service('ux_upload.token_handler'))
        ->arg('$storage', service(StorageInterface::class))
        ->arg('$uploaders', service('ux_upload.uploaders'))
        ->arg('$logger', service('logger')->nullOnInvalid())
        ->arg('$csrfTokenManager', service('security.csrf.token_manager')->nullOnInvalid())
        ->arg('$contextResolver', service(UploadContextResolverInterface::class))
        ->arg('$policySigner', service(UploadPolicySigner::class))
        ->arg('$resumeTokenHandler', service(ResumeTokenHandler::class))
        ->tag('controller.service_arguments')
        ->public()
    ;

    $services->alias(UploadController::class, 'ux_upload.controller.upload')
        ->public()
    ;

    // The cleanup console command is registered in UXUploadBundle::loadExtension(),
    // gated behind class_exists() so the container compiles without symfony/console.
};
