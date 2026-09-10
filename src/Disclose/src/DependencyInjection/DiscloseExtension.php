<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Disclose\DependencyInjection;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\FlockStore;
use Symfony\UX\Disclose\Audit\DiscloseAuditLogger;
use Symfony\UX\Disclose\Checksum\ChecksumCalculator;
use Symfony\UX\Disclose\Context\DiscloseContextFactory;
use Symfony\UX\Disclose\Context\DiscloseContextSigner;
use Symfony\UX\Disclose\Context\DoctrinePersistenceContextProvider;
use Symfony\UX\Disclose\Controller\DiscloseController;
use Symfony\UX\Disclose\DiscloserRegistry;
use Symfony\UX\Disclose\DiscloseUrlGenerator;
use Symfony\UX\Disclose\EventListener\RevealBlockSubscriber;
use Symfony\UX\Disclose\RateLimiter\DiscloseRateLimiter;
use Symfony\UX\Disclose\Subject\DoctrinePersistenceSubjectResolver;
use Symfony\UX\Disclose\Subject\SubjectResolverRegistry;
use Symfony\UX\Disclose\Twig\DiscloseComponent;

/**
 * @author Jérôme Tamarelle <jerome@tamarelle.net>
 *
 * @internal
 */
final class DiscloseExtension extends ConfigurableExtension implements PrependExtensionInterface
{
    public function loadInternal(array $config, ContainerBuilder $container): void
    {
        $container->register('ux.disclose.checksum_calculator', ChecksumCalculator::class)
            ->setArguments(['%kernel.secret%'])
        ;

        $container->register('ux.disclose.context_signer', DiscloseContextSigner::class)
            ->setArguments([new Reference('ux.disclose.checksum_calculator'), $config['ttl']])
        ;

        $container->register('ux.disclose.url_generator', DiscloseUrlGenerator::class)
            ->setArguments([
                new Reference('router'),
                new Reference('ux.disclose.context_signer'),
            ])
        ;

        $container->register('ux.disclose.context_factory', DiscloseContextFactory::class)
            ->setArguments([new TaggedIteratorArgument('ux.disclose.context_provider')])
        ;

        // A bundle-owned lock factory makes the disclosure rate limiter
        // race-safe out of the box. The application can override the
        // "lock_factory" option of the limiter to plug its own lock store.
        $container->register('ux.disclose.lock_store', FlockStore::class)
            ->setArguments(['%kernel.cache_dir%/locks'])
        ;
        $container->register('ux.disclose.lock_factory', LockFactory::class)
            ->setArguments([new Reference('ux.disclose.lock_store')])
        ;

        $managerRegistries = [];
        if (ContainerBuilder::willBeAvailable('doctrine/orm', EntityManagerInterface::class, ['doctrine/doctrine-bundle'])) {
            $managerRegistries[] = new Reference('doctrine', ContainerInterface::NULL_ON_INVALID_REFERENCE);
        }
        if (ContainerBuilder::willBeAvailable('doctrine/mongodb-odm', DocumentManager::class, ['doctrine/mongodb-odm-bundle'])) {
            $managerRegistries[] = new Reference('doctrine_mongodb', ContainerInterface::NULL_ON_INVALID_REFERENCE);
        }

        if ($managerRegistries) {
            $container->register('ux.disclose.doctrine_persistence_context_provider', DoctrinePersistenceContextProvider::class)
                ->setArguments([$managerRegistries])
                ->addTag('ux.disclose.context_provider')
            ;
        }

        $container->register('ux.disclose.subject_resolver_registry', SubjectResolverRegistry::class)
            ->setArguments([new TaggedIteratorArgument('ux.disclose.subject_resolver')])
        ;

        $container->register('ux.disclose.discloser_registry', DiscloserRegistry::class)
            ->setArguments([new TaggedIteratorArgument('ux.disclose.discloser')])
        ;

        $rateLimiterFactories = [];
        foreach ($config['rate_limiter'] as $name) {
            $rateLimiterFactories[] = new Reference('limiter.' . $name, ContainerInterface::NULL_ON_INVALID_REFERENCE);
        }

        $rateLimiterArguments = [
            new Reference('security.helper', ContainerInterface::NULL_ON_INVALID_REFERENCE),
            $rateLimiterFactories,
        ];

        if ($config['rate_limiter_subject_factory']) {
            $rateLimiterArguments[] = new Reference($config['rate_limiter_subject_factory'], ContainerInterface::NULL_ON_INVALID_REFERENCE);
        }

        $container->register('ux.disclose.rate_limiter', DiscloseRateLimiter::class)
            ->setArguments($rateLimiterArguments)
        ;

        $container->register('ux.disclose.audit_logger', DiscloseAuditLogger::class)
            ->setArguments([new Reference($config['logger'])])
        ;

        $container->register('ux.disclose.controller', DiscloseController::class)
            ->setArguments([
                new Reference('ux.disclose.context_signer'),
                new Reference('ux.disclose.subject_resolver_registry'),
                new Reference('ux.disclose.discloser_registry'),
                new Reference('ux.disclose.rate_limiter'),
                new Reference('ux.disclose.audit_logger'),
                new Reference('event_dispatcher'),
                new Reference('twig'),
                new Reference('security.helper', ContainerInterface::NULL_ON_INVALID_REFERENCE),
            ])
            ->addTag('controller.service_arguments')
        ;

        $container->register('ux.disclose.twig.component', DiscloseComponent::class)
            ->setAutoconfigured(true)
            ->setShared(false)
            ->addMethodCall('setDiscloseUrlGenerator', [new Reference('ux.disclose.url_generator')])
            ->addMethodCall('setDiscloseContextFactory', [new Reference('ux.disclose.context_factory')])
        ;

        $container->register('ux.disclose.reveal_block_subscriber', RevealBlockSubscriber::class)
            ->setArguments([new Reference('twig')])
            ->addTag('kernel.event_subscriber')
        ;

        if (ContainerBuilder::willBeAvailable('doctrine/orm', EntityManagerInterface::class, ['doctrine/doctrine-bundle'])) {
            $container->register('ux.disclose.doctrine_orm_subject_resolver', DoctrinePersistenceSubjectResolver::class)
                ->setArguments([new Reference('doctrine', ContainerInterface::NULL_ON_INVALID_REFERENCE)])
                ->addTag('ux.disclose.subject_resolver')
            ;
        }

        if (ContainerBuilder::willBeAvailable('doctrine/mongodb-odm', DocumentManager::class, ['doctrine/mongodb-odm-bundle'])) {
            $container->register('ux.disclose.doctrine_odm_subject_resolver', DoctrinePersistenceSubjectResolver::class)
                ->setArguments([new Reference('doctrine_mongodb', ContainerInterface::NULL_ON_INVALID_REFERENCE)])
                ->addTag('ux.disclose.subject_resolver')
            ;
        }
    }

    public function prepend(ContainerBuilder $container): void
    {
        // Proposal by default so the disclosure endpoint is rate limited out of
        // the box. The Lock component is a hard requirement: consume() serializes
        // through the bundle-owned flock lock factory, so a burst of
        // simultaneous requests cannot race past the quota. The application may
        // override any key by configuring the "ux_disclose" limiter of the
        // framework bundle, exactly like any other rate limiter (for example
        // "policy: no_limit" disables the limit, or a custom "lock_factory"
        // plugs another lock store).
        $container->prependExtensionConfig('framework', [
            'rate_limiter' => [
                'ux_disclose' => [
                    'policy' => 'fixed_window',
                    'limit' => 10,
                    'interval' => '10 minutes',
                    'cache_pool' => 'cache.app',
                    'lock_factory' => 'ux.disclose.lock_factory',
                ],
            ],
        ]);

        if (!$this->isAssetMapperAvailable($container)) {
            return;
        }

        $container->prependExtensionConfig('framework', [
            'asset_mapper' => [
                'paths' => [
                    __DIR__ . '/../../assets/dist' => '@symfony/ux-disclose',
                ],
            ],
        ]);
    }

    private function isAssetMapperAvailable(ContainerBuilder $container): bool
    {
        if (!interface_exists(AssetMapperInterface::class)) {
            return false;
        }

        // check that FrameworkBundle 6.3 or higher is installed
        $bundlesMetadata = $container->getParameter('kernel.bundles_metadata');
        if (!isset($bundlesMetadata['FrameworkBundle'])) {
            return false;
        }

        return is_file($bundlesMetadata['FrameworkBundle']['path'] . '/Resources/config/asset_mapper.php');
    }
}
