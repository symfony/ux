<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Upload;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Symfony\Component\Translation\Translator;
use Symfony\UX\Upload\Command\CleanupCommand;
use Symfony\UX\Upload\Routing\UploadRouteLoader;
use Symfony\UX\Upload\Security\SymfonySecurityUploadContextResolver;
use Symfony\UX\Upload\Security\UploadContextResolverInterface;
use Symfony\UX\Upload\Storage\FlysystemStorage;
use Symfony\UX\Upload\Storage\LocalStorage;
use Symfony\UX\Upload\Storage\PrunableStorageInterface;
use Symfony\UX\Upload\Storage\StorageInterface;
use Symfony\UX\Upload\Url\SymfonyUploadUrlGenerator;
use Symfony\UX\Upload\Util\SizeParser;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class UXUploadBundle extends AbstractBundle
{
    /**
     * @param array<string, mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../config/services.php');

        /** @var array<string, string> $bundles */
        $bundles = $builder->hasParameter('kernel.bundles') ? $builder->getParameter('kernel.bundles') : [];
        if (isset($bundles['SecurityBundle']) && class_exists(Security::class)) {
            $securityContextResolver = new Definition(SymfonySecurityUploadContextResolver::class);
            $securityContextResolver->setArgument('$security', new Reference(Security::class, ContainerInterface::NULL_ON_INVALID_REFERENCE));
            $builder->setDefinition(SymfonySecurityUploadContextResolver::class, $securityContextResolver);
            $builder->setAlias(UploadContextResolverInterface::class, SymfonySecurityUploadContextResolver::class)->setPublic(true);
        }

        /** @var string|int $chunkSize */
        $chunkSize = $config['chunk_size'];
        if (\is_string($chunkSize)) {
            $chunkSize = SizeParser::parse($chunkSize);
        }
        if ($chunkSize < 64 * 1024) {
            throw new InvalidConfigurationException(\sprintf('The "chunk_size" must be at least 64K (65536 bytes), got %d.', $chunkSize));
        }
        if ($chunkSize > Uploader::MAX_CHUNK_SIZE) {
            throw new InvalidConfigurationException(\sprintf('The "chunk_size" cannot exceed %d bytes, got %d.', Uploader::MAX_CHUNK_SIZE, $chunkSize));
        }

        /** @var string|int $maxSize */
        $maxSize = $config['max_size'];
        if (\is_string($maxSize)) {
            $maxSize = SizeParser::parse($maxSize);
        }
        if ($maxSize < 0) {
            throw new InvalidConfigurationException(\sprintf('The "max_size" cannot be negative, got %d.', $maxSize));
        }

        /** @var list<string> $allowedTypes */
        $allowedTypes = $config['allowed_types'];
        /** @var array{directory: string} $localStorage */
        $localStorage = $config['local_storage'];

        $builder->getDefinition('ux_upload.listener.file_validation')
            ->setArgument('$allowedTypes', $allowedTypes)
            ->setArgument('$maxSize', $maxSize);

        $builder->getDefinition(SymfonyUploadUrlGenerator::class)
            ->setArgument('$signatureExpiry', $config['signature_expiry']);

        $builder->getDefinition(LocalStorage::class)
            ->setArgument(0, $localStorage['directory'])
            ->setArgument(1, $config['temp_dir'])
            ->setArgument('$completedPrefix', $config['completed_prefix'])
            ->setArgument('$lockFactory', new Reference('lock.factory'));
        /** @var string $tempDir */
        $tempDir = $config['temp_dir'];

        /** @var string $storageBackend */
        $storageBackend = $config['storage'];
        if ('distributed' === $config['deployment'] && !$config['shared_lock']) {
            throw new InvalidConfigurationException('A distributed UX Upload deployment requires "ux_upload.shared_lock: true" after configuring framework.lock with a shared store such as Redis, Doctrine DBAL or Semaphore.');
        }
        if ('distributed' === $config['deployment'] && 'local' === $storageBackend) {
            throw new InvalidConfigurationException('A distributed UX Upload deployment cannot use "storage: local" because chunks and assembled files are node-local. Configure "storage: flysystem" with shared storage.');
        }
        if ('flysystem' === $storageBackend) {
            if (!interface_exists(\League\Flysystem\FilesystemOperator::class)) {
                throw new InvalidConfigurationException('You have set "storage: flysystem" but league/flysystem is not installed. Run "composer require league/flysystem".');
            }

            /** @var string|null $flysystemService */
            $flysystemService = $config['flysystem_service'];
            if (null === $flysystemService || '' === $flysystemService) {
                throw new InvalidConfigurationException('You have set "storage: flysystem" but no "flysystem_service" was configured. Set "ux_upload.flysystem_service" to the ID of the Flysystem filesystem service to use (for example a service defined by league/flysystem-bundle). The filesystem is never selected by autowiring the "League\\Flysystem\\FilesystemOperator" type, because an application may configure several named filesystems.');
            }

            $flysystemDefinition = new Definition(FlysystemStorage::class);
            $flysystemDefinition->setArgument('$filesystem', new Reference($flysystemService));
            $flysystemDefinition->setArgument('$assemblyTempDir', $tempDir.'/flysystem-assembly');
            $flysystemDefinition->setArgument('$completedPrefix', $config['completed_prefix']);
            $flysystemDefinition->setArgument('$lockFactory', new Reference('lock.factory'));
            $builder->setDefinition(FlysystemStorage::class, $flysystemDefinition);
            $builder->setAlias(StorageInterface::class, FlysystemStorage::class);
            $builder->setAlias(PrunableStorageInterface::class, FlysystemStorage::class);
        }

        /** @var array<string, array<string, mixed>> $uploaders */
        $uploaders = $config['uploaders'] ?? [];
        /** @var array<string, Reference> $uploaderRefs */
        $uploaderRefs = [];

        $builder->getDefinition(Uploader::class)
            ->setArgument('$name', 'default')
            ->setArgument('$chunkSize', $chunkSize)
            ->setArgument('$parallelChunks', $config['parallel_chunks'])
            ->setArgument('$compressionEnabled', $config['compression'])
            ->setArgument('$maxSize', $maxSize)
            ->setArgument('$allowedTypes', $allowedTypes)
            ->setArgument('$completedTtl', $config['completed_ttl']);
        $builder->getDefinition(Uploader::class)
            ->setArgument('$maxPendingPerOwner', $config['max_pending_per_owner']);
        $builder->getDefinition(Uploader::class)
            ->setArgument('$distributedLockGuaranteed', 'single_node' === $config['deployment'] || $config['shared_lock']);
        $builder->getDefinition(Uploader::class)
            ->setArgument('$integrityAlgorithm', $config['integrity_algorithm']);

        $builder->setAlias('ux_upload.uploader.default', Uploader::class);
        $uploaderRefs['default'] = new Reference('ux_upload.uploader.default');

        /** @var int $globalParallelChunks */
        $globalParallelChunks = $config['parallel_chunks'];
        /** @var bool $globalCompression */
        $globalCompression = $config['compression'];

        foreach ($uploaders as $name => $uploaderConfig) {
            /** @var string|int|null $uploaderChunkSize */
            $uploaderChunkSize = $uploaderConfig['chunk_size'] ?? $chunkSize;
            if (\is_string($uploaderChunkSize)) {
                $uploaderChunkSize = SizeParser::parse($uploaderChunkSize);
            }
            if ($uploaderChunkSize < 64 * 1024) {
                throw new InvalidConfigurationException(\sprintf('The "uploaders.%s.chunk_size" must be at least 64K (65536 bytes), got %d.', $name, $uploaderChunkSize));
            }
            if ($uploaderChunkSize > Uploader::MAX_CHUNK_SIZE) {
                throw new InvalidConfigurationException(\sprintf('The "uploaders.%s.chunk_size" cannot exceed %d bytes, got %d.', $name, Uploader::MAX_CHUNK_SIZE, $uploaderChunkSize));
            }

            /** @var string|int|null $uploaderMaxSize */
            $uploaderMaxSize = $uploaderConfig['max_size'] ?? $maxSize;
            if (\is_string($uploaderMaxSize)) {
                $uploaderMaxSize = SizeParser::parse($uploaderMaxSize);
            }
            if ($uploaderMaxSize < 0) {
                throw new InvalidConfigurationException(\sprintf('The "uploaders.%s.max_size" cannot be negative, got %d.', $name, $uploaderMaxSize));
            }

            /** @var list<string> $uploaderAllowedTypes */
            $uploaderAllowedTypes = $uploaderConfig['allowed_types'] ?? $allowedTypes;
            /** @var int $uploaderParallelChunks */
            $uploaderParallelChunks = $uploaderConfig['parallel_chunks'] ?? $globalParallelChunks;
            /** @var bool $uploaderCompression */
            $uploaderCompression = $uploaderConfig['compression'] ?? $globalCompression;

            $serviceId = 'ux_upload.uploader.'.(string) $name;
            $definition = new Definition(Uploader::class);
            $definition->setAutowired(true);
            $definition->setArgument('$name', (string) $name);
            $definition->setArgument('$chunkSize', $uploaderChunkSize);
            $definition->setArgument('$parallelChunks', $uploaderParallelChunks);
            $definition->setArgument('$compressionEnabled', $uploaderCompression);
            $definition->setArgument('$maxSize', $uploaderMaxSize);
            $definition->setArgument('$allowedTypes', $uploaderAllowedTypes);
            $definition->setArgument('$integrityAlgorithm', $uploaderConfig['integrity_algorithm'] ?? $config['integrity_algorithm']);
            $definition->setArgument('$completedTtl', $uploaderConfig['completed_ttl'] ?? $config['completed_ttl']);
            $definition->setArgument('$maxPendingPerOwner', $uploaderConfig['max_pending_per_owner'] ?? $config['max_pending_per_owner']);
            $definition->setArgument('$distributedLockGuaranteed', 'single_node' === $config['deployment'] || $config['shared_lock']);
            $definition->setArgument('$lockFactory', new Reference('lock.factory'));
            $builder->setDefinition($serviceId, $definition);
            $uploaderRefs[(string) $name] = new Reference($serviceId);
        }

        $locatorDefinition = new Definition(ServiceLocator::class, [$uploaderRefs]);
        $locatorDefinition->addTag('container.service_locator');
        $builder->setDefinition('ux_upload.uploaders', $locatorDefinition);

        $builder->getDefinition('ux_upload.controller.upload')
            ->setArgument('$allowAnonymous', $config['allow_anonymous']);

        /** @var string|null $rateLimiter */
        $rateLimiter = $config['rate_limiter'];
        if (null !== $rateLimiter) {
            $builder->getDefinition('ux_upload.controller.upload')
                ->setArgument('$initRateLimiter', new Reference($rateLimiter));
        }

        $builder->getDefinition('ux_upload.token_handler')
            ->setArgument('$ttl', $config['form_token_ttl'])
            ->setArgument('$completedPrefix', $config['completed_prefix']);

        $routeLoaderDefinition = new Definition(UploadRouteLoader::class);
        $routeLoaderDefinition->addTag('routing.route_loader');
        $builder->setDefinition(UploadRouteLoader::class, $routeLoaderDefinition);

        if (class_exists(\Symfony\Component\Console\Command\Command::class)) {
            $cleanupDefinition = new Definition(CleanupCommand::class);
            $cleanupDefinition->setArgument('$storage', new Reference(PrunableStorageInterface::class));
            $cleanupDefinition->addTag('console.command');
            $builder->setDefinition('ux_upload.command.cleanup', $cleanupDefinition);
        }
    }

    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        /** @var array<string, string> $bundles */
        $bundles = $builder->hasParameter('kernel.bundles') ? $builder->getParameter('kernel.bundles') : [];
        if (isset($bundles['TwigBundle'])) {
            $builder->prependExtensionConfig('twig', [
                'form_themes' => ['@UXUpload/form_theme.html.twig'],
            ]);
        }

        $frameworkConfig = [];
        if (interface_exists(AssetMapperInterface::class)) {
            $frameworkConfig['asset_mapper'] = [
                'paths' => [
                    __DIR__.'/../assets/dist' => '@symfony/ux-upload',
                ],
            ];
        }
        if (class_exists(Translator::class)) {
            $frameworkConfig['translator'] = [
                'paths' => [
                    __DIR__.'/../translations',
                ],
            ];
        }
        if ([] !== $frameworkConfig) {
            $builder->prependExtensionConfig('framework', $frameworkConfig);
        }
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->enumNode('storage')->values(['local', 'flysystem'])->defaultValue('local')->info('Storage backend to use (local, flysystem)')->end()
                ->enumNode('deployment')->values(['single_node', 'distributed'])->defaultValue('single_node')->end()
                ->booleanNode('shared_lock')->defaultFalse()->info('Assert that framework.lock uses a store shared by every application node')->end()
                ->scalarNode('flysystem_service')->defaultNull()->info('Flysystem filesystem service ID to use when "storage: flysystem" (e.g. a service from league/flysystem-bundle). Required for the flysystem backend; never autowired by type.')->end()
                ->scalarNode('temp_dir')->defaultValue('%kernel.project_dir%/var/uploads/tmp')->info('Temporary directory for chunk assembly')->end()
                ->scalarNode('completed_prefix')->defaultValue('.tmp/completed')->cannotBeEmpty()->info('Single storage prefix for assembled temporary uploads')->end()
                ->scalarNode('chunk_size')->defaultValue('5M')->info('Size of each chunk (e.g. 5M, 10M)')->end()
                ->integerNode('parallel_chunks')->defaultValue(3)->min(1)->max(10)->end()
                ->integerNode('signature_expiry')->defaultValue(3600)->min(1)->info('Time in seconds before signed URLs expire')->end()
                ->integerNode('form_token_ttl')->defaultValue(86400)->min(1)->info('Maximum lifetime in seconds of the signed token stored in a submitted form; also capped by completed_ttl')->end()
                ->integerNode('completed_ttl')->defaultValue(Uploader::DEFAULT_COMPLETED_TTL)->min(60)->info('Time in seconds before an assembled temporary upload expires')->end()
                ->integerNode('max_pending_per_owner')->defaultValue(1000)->min(1)->end()
                ->booleanNode('compression')->defaultFalse()->info('Enable gzip compression for chunks')->end()
                ->enumNode('integrity_algorithm')->values(Uploader::INTEGRITY_ALGORITHMS)->defaultValue('sha256')->info('Hash algorithm used for optional end-to-end file integrity verification')->end()
                ->scalarNode('max_size')->defaultValue(Uploader::DEFAULT_MAX_SIZE)->info('Maximum file size enforced server-side (0 = no limit, e.g. "100M")')->end()
                ->arrayNode('allowed_types')->scalarPrototype()->end()->defaultValue([])->info('Allowed MIME types enforced server-side (empty = all types)')->end()
                ->booleanNode('allow_anonymous')->defaultFalse()->info('Accept uploads from visitors that Security cannot identify. Leave disabled unless the endpoints are meant to be public, and pair it with a rate_limiter and an UploadContextResolverInterface')->end()
                ->scalarNode('rate_limiter')->defaultNull()->info('Service ID of a named framework.rate_limiter factory used to throttle upload initialization (for example "limiter.ux_upload_init")')->end()
                ->arrayNode('local_storage')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('directory')->defaultValue('%kernel.project_dir%/var/uploads')->end()
                    ->end()
                ->end()
                ->arrayNode('uploaders')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('max_size')->defaultNull()->info('Maximum file size for this uploader (inherits global if null)')->end()
                            ->arrayNode('allowed_types')->scalarPrototype()->end()->defaultNull()->info('Allowed MIME types for this uploader (inherits global if omitted)')->end()
                            ->scalarNode('chunk_size')->defaultNull()->info('Chunk size for this uploader (inherits global if null)')->end()
                            ->integerNode('parallel_chunks')->defaultNull()->min(1)->max(10)->info('Number of parallel chunks for this uploader (inherits global if null)')->end()
                            ->booleanNode('compression')->defaultNull()->info('Enable compression for this uploader (inherits global if null)')->end()
                            ->enumNode('integrity_algorithm')->values(Uploader::INTEGRITY_ALGORITHMS)->defaultNull()->info('Integrity algorithm for this uploader (inherits global if null)')->end()
                            ->integerNode('completed_ttl')->defaultNull()->min(60)->info('Completed temporary upload TTL for this uploader (inherits global if null)')->end()
                            ->integerNode('max_pending_per_owner')->defaultNull()->min(1)->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }
}
