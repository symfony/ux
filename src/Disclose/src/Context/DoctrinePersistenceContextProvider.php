<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Disclose\Context;

use Doctrine\Persistence\ManagerRegistry;

/**
 * Builds disclose contexts out of objects managed by any doctrine/persistence
 * object manager (Doctrine ORM, MongoDB ODM, or another compatible source).
 *
 * This provider is only registered when a matching package is installed. It is
 * an example implementation: the context contract itself stays data source
 * agnostic.
 *
 * @author Jérôme Tamarelle <jerome@tamarelle.net>
 *
 * @internal
 */
final class DoctrinePersistenceContextProvider implements ContextProviderInterface
{
    /**
     * @param list<ManagerRegistry|null> $managerRegistries
     */
    public function __construct(private readonly array $managerRegistries = []) {}

    public function create(object $subject, ?string $field = null, array $extra = []): ?DiscloseContext
    {
        foreach ($this->managerRegistries as $registry) {
            if (null === $registry) {
                continue;
            }

            $objectManager = $registry->getManagerForClass($subject::class);
            if (null === $objectManager) {
                continue;
            }

            $metadata = $objectManager->getClassMetadata($subject::class);

            // ORM and ODM expose identifiers slightly differently: ORM returns
            // an associative array, ODM exposes a single getIdentifierValue().
            if (method_exists($metadata, 'getIdentifierValues')) {
                $ids = (array) $metadata->getIdentifierValues($subject);
                $id = 1 === \count($ids) ? reset($ids) : $ids;
            } elseif (method_exists($metadata, 'getIdentifierValue')) {
                $id = $metadata->getIdentifierValue($subject);
            } else {
                throw new \LogicException(\sprintf('The object metadata of class "%s" must implement "getIdentifierValues()" or "getIdentifierValue()" to build a disclose context.', $subject::class));
            }

            return DiscloseContext::create($subject::class, $id, $field, $extra);
        }

        return null;
    }
}
