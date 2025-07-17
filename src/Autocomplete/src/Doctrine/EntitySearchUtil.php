<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Autocomplete\Doctrine;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\Uuid;

/**
 * Adapted from EasyCorp/EasyAdminBundle.
 */
class EntitySearchUtil
{
    public function __construct(private EntityMetadataFactory $metadataFactory)
    {
    }

    /**
     * Adapted from easycorp/easyadmin EntityRepository.
     */
    public function addSearchClause(QueryBuilder $queryBuilder, string $query, string $entityClass, ?array $searchableProperties = null): void
    {
        $entityMetadata = $this->metadataFactory->create($entityClass);

        $lowercaseQuery = mb_strtolower($query);
        $isNumericQuery = is_numeric($query);
        $isSmallIntegerQuery = ctype_digit($query) && $query >= -32768 && $query <= 32767;
        $isIntegerQuery = ctype_digit($query) && $query >= -2147483648 && $query <= 2147483647;
        $isUuidQuery = class_exists(Uuid::class) && Uuid::isValid($query);
        $isUlidQuery = class_exists(Ulid::class) && Ulid::isValid($query);

        $dqlParameters = [
            // adding '0' turns the string into a numeric value
            'numeric_query' => is_numeric($query) ? 0 + $query : $query,
            'uuid_query' => $query,
            'text_query' => '%'.$lowercaseQuery.'%',
            'words_query' => explode(' ', $lowercaseQuery),
        ];

        $entitiesAlreadyJoined = [];
        $aliasAlreadyUsed = [];
        $searchableProperties = empty($searchableProperties) ? $entityMetadata->getAllPropertyNames() : $searchableProperties;
        $expressions = [];
        foreach ($searchableProperties as $propertyName) {
            if ($entityMetadata->isAssociation($propertyName)) {
                // support arbitrarily nested associations (e.g. foo.bar.baz.qux)
                $associatedProperties = explode('.', $propertyName);
                $numAssociatedProperties = \count($associatedProperties);

                if (1 === $numAssociatedProperties) {
                    throw new \InvalidArgumentException(\sprintf('The "%s" property included in the setSearchFields() method is not a valid search field. When using associated properties in search, you must also define the exact field used in the search (e.g. \'%s.id\', \'%s.name\', etc.)', $propertyName, $propertyName, $propertyName));
                }

                $originalPropertyName = $associatedProperties[0];
                $originalPropertyMetadata = $entityMetadata->getAssociationMetadata($originalPropertyName);
                $associatedEntityDto = $this->metadataFactory->create($originalPropertyMetadata['targetEntity']);

                for ($i = 0; $i < $numAssociatedProperties - 1; ++$i) {
                    $associatedEntityName = $associatedProperties[$i];
                    $associatedEntityAlias = SearchEscaper::escapeDqlAlias($associatedEntityName);
                    $associatedPropertyName = $associatedProperties[$i + 1];

                    $associatedParentName = null;
                    if (\array_key_exists($i - 1, $associatedProperties) && $queryBuilder->getRootAliases()[0] !== $associatedProperties[$i - 1]) {
                        $associatedParentName = $associatedProperties[$i - 1];
                    }

                    $associatedEntityAlias = $associatedParentName ? $associatedParentName.'_'.$associatedEntityAlias : $associatedEntityAlias;

                    if (!\in_array($associatedEntityName, $entitiesAlreadyJoined, true) || !\in_array($associatedEntityAlias, $aliasAlreadyUsed, true)) {
                        $parentEntityName = 0 === $i ? $queryBuilder->getRootAliases()[0] : $associatedProperties[$i - 1];
                        $queryBuilder->leftJoin($parentEntityName.'.'.$associatedEntityName, $associatedEntityAlias);
                        $entitiesAlreadyJoined[] = $associatedEntityName;
                        $aliasAlreadyUsed[] = $associatedEntityAlias;
                    }

                    if ($i < $numAssociatedProperties - 2) {
                        $propertyMetadata = $associatedEntityDto->getAssociationMetadata($associatedPropertyName);
                        $associatedEntityDto = $this->metadataFactory->create($propertyMetadata['targetEntity']);
                    }
                }

                $entityName = $associatedEntityAlias;
                $propertyName = $associatedPropertyName;
                $propertyDataType = $associatedEntityDto->getPropertyDataType($propertyName);
            } else {
                $entityName = $queryBuilder->getRootAliases()[0];
                $propertyDataType = $entityMetadata->getPropertyDataType($propertyName);
            }

            $isSmallIntegerProperty = 'smallint' === $propertyDataType;
            $isIntegerProperty = 'integer' === $propertyDataType;
            $isNumericProperty = \in_array($propertyDataType, ['number', 'bigint', 'decimal', 'float']);
            // 'citext' is a PostgreSQL extension (https://github.com/EasyCorp/EasyAdminBundle/issues/2556)
            $isTextProperty = \in_array($propertyDataType, ['string', 'text', 'citext', 'array', 'simple_array']);
            $isGuidProperty = \in_array($propertyDataType, ['guid', 'uuid']);
            $isUlidProperty = 'ulid' === $propertyDataType;

            // this complex condition is needed to avoid issues on PostgreSQL databases
            if (
                ($isSmallIntegerProperty && $isSmallIntegerQuery)
                || ($isIntegerProperty && $isIntegerQuery)
                || ($isNumericProperty && $isNumericQuery)
            ) {
                $expressions[] = $queryBuilder->expr()->eq(\sprintf('%s.%s', $entityName, $propertyName), ':query_for_numbers');
                $queryBuilder->setParameter('query_for_numbers', $dqlParameters['numeric_query']);
            } elseif ($isGuidProperty && $isUuidQuery) {
                $expressions[] = $queryBuilder->expr()->eq(\sprintf('%s.%s', $entityName, $propertyName), ':query_for_uuids');
                $queryBuilder->setParameter('query_for_uuids', $dqlParameters['uuid_query'], 'uuid' === $propertyDataType ? 'uuid' : null);
            } elseif ($isUlidProperty && $isUlidQuery) {
                $expressions[] = $queryBuilder->expr()->eq(\sprintf('%s.%s', $entityName, $propertyName), ':query_for_uuids');
                $queryBuilder->setParameter('query_for_uuids', $dqlParameters['uuid_query'], 'ulid');
            } elseif ($isTextProperty) {
                $expressions[] = $queryBuilder->expr()->like(\sprintf('LOWER(%s.%s)', $entityName, $propertyName), ':query_for_text');
                $queryBuilder->setParameter('query_for_text', $dqlParameters['text_query']);

                $expressions[] = $queryBuilder->expr()->in(\sprintf('LOWER(%s.%s)', $entityName, $propertyName), ':query_as_words');
                $queryBuilder->setParameter('query_as_words', $dqlParameters['words_query']);
            }
        }

        $queryBuilder->andWhere($queryBuilder->expr()->orX(...$expressions));
    }
}
