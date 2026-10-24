<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Autocomplete\Form;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\ChoiceList\Factory\Cache\ChoiceLabel;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyAccess\PropertyPathInterface;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\Service\ResetInterface;
use Symfony\UX\Autocomplete\Doctrine\EntityMetadata;
use Symfony\UX\Autocomplete\Doctrine\EntityMetadataFactory;
use Symfony\UX\Autocomplete\Doctrine\EntitySearchUtil;
use Symfony\UX\Autocomplete\ExcludedValuesAwareAutocompleterInterface;
use Symfony\UX\Autocomplete\OptionsAwareEntityAutocompleterInterface;

/**
 * An entity auto-completer that wraps a form type to get its information.
 *
 * @internal
 */
final class WrappedEntityTypeAutocompleter implements OptionsAwareEntityAutocompleterInterface, ExcludedValuesAwareAutocompleterInterface, ResetInterface
{
    use WrappedAutocompleterTrait;

    private ?FormInterface $form = null;
    private ?EntityMetadata $entityMetadata = null;
    private array $options = [];
    private array $excludedValues = [];

    public function __construct(
        private string $formType,
        private FormFactoryInterface $formFactory,
        private EntityMetadataFactory $metadataFactory,
        private PropertyAccessorInterface $propertyAccessor,
        private EntitySearchUtil $entitySearchUtil,
    ) {
    }

    public function getEntityClass(): string
    {
        return $this->getFormOption('class');
    }

    public function createFilteredQueryBuilder(EntityRepository $repository, string $query): QueryBuilder
    {
        $queryBuilder = $this->getFormOption('query_builder');
        $queryBuilder = $queryBuilder ?: $repository->createQueryBuilder('entity');

        if ($filterQuery = $this->getFilterQuery()) {
            $filterQuery($queryBuilder, $query, $repository);
            $this->addExcludedValuesClause($queryBuilder);

            return $queryBuilder;
        }

        // Applying max result limit or not
        $queryBuilder->setMaxResults($this->getMaxResults());

        $this->addExcludedValuesClause($queryBuilder);

        // avoid filtering if there is no query
        if (!$query) {
            return $queryBuilder;
        }

        $this->entitySearchUtil->addSearchClause(
            $queryBuilder,
            $query,
            $this->getEntityClass(),
            $this->getSearchableFields()
        );

        return $queryBuilder;
    }

    public function getLabel(object $entity): string
    {
        $choiceLabel = $this->getFormOption('choice_label');

        if (null === $choiceLabel) {
            return (string) $entity;
        }

        if (\is_string($choiceLabel) || $choiceLabel instanceof PropertyPathInterface) {
            return $this->propertyAccessor->getValue($entity, $choiceLabel);
        }

        if ($choiceLabel instanceof ChoiceLabel) {
            $choiceLabel = $choiceLabel->getOption();
        }

        // 0 hardcoded as the "index", should not be relevant
        return $choiceLabel($entity, 0, $this->getValue($entity));
    }

    public function getValue(object $entity): string
    {
        $choiceValue = $this->getFormOption('choice_value');

        if (\is_string($choiceValue) || $choiceValue instanceof PropertyPathInterface) {
            return $this->propertyAccessor->getValue($entity, $choiceValue);
        }

        if ($choiceValue instanceof \Closure) {
            return $choiceValue($entity);
        }

        return $this->getEntityMetadata()->getIdValue($entity);
    }

    public function getGroupBy(): mixed
    {
        return $this->getFormOption('group_by');
    }

    public function getAttributes(object $entity): array
    {
        $attributesOption = $this->getFormOption('additional_attributes');

        if (null === $attributesOption) {
            return [];
        }

        if (\is_array($attributesOption)) {
            return $attributesOption;
        }

        if (\is_callable($attributesOption)) {
            return $attributesOption($entity);
        }

        throw new \InvalidArgumentException('The "additional_attributes" option must be either an array or a callable.');
    }

    public function getTranslationDomain(): string|false|null
    {
        // the "choice_translation_domain" option is normalized to the form
        // "translation_domain" when it is true, and to false when translation
        // is disabled
        return $this->getFormOption('choice_translation_domain');
    }

    private function getFormOption(string $name): mixed
    {
        $form = $this->getForm();
        $formOptions = $form->getConfig()->getOptions();

        return $formOptions[$name] ?? null;
    }

    private function getSearchableFields(): ?array
    {
        return $this->getForm()->getConfig()->getOption('searchable_fields');
    }

    private function getFilterQuery(): ?callable
    {
        return $this->getForm()->getConfig()->getOption('filter_query');
    }

    private function getMaxResults(): ?int
    {
        return $this->getForm()->getConfig()->getOption('max_results');
    }

    private function getEntityMetadata(): EntityMetadata
    {
        if (null === $this->entityMetadata) {
            $this->entityMetadata = $this->metadataFactory->create($this->getEntityClass());
        }

        return $this->entityMetadata;
    }

    /**
     * Excludes the entities whose value (see getValue()) is one of the excluded values.
     *
     * This is skipped when the value cannot be mapped to a single field (e.g. a
     * "choice_value" callback or a composite identifier).
     */
    private function addExcludedValuesClause(QueryBuilder $queryBuilder): void
    {
        if (!$this->excludedValues || null === $field = $this->getValueField()) {
            return;
        }

        $type = $this->getEntityMetadata()->getPropertyDataType($field);
        $values = array_filter(array_map(fn (string $value) => $this->normalizeExcludedValue($value, $type), $this->excludedValues), static fn (mixed $value) => null !== $value);

        if (!$values) {
            return;
        }

        $parameters = [];
        foreach (array_values($values) as $i => $value) {
            $parameters[] = ':ux_autocomplete_excluded_'.$i;
            $queryBuilder->setParameter('ux_autocomplete_excluded_'.$i, $value, $type);
        }

        $queryBuilder->andWhere(\sprintf('%s.%s NOT IN (%s)', $queryBuilder->getRootAliases()[0], $field, implode(', ', $parameters)));
    }

    private function getValueField(): ?string
    {
        $choiceValue = $this->getFormOption('choice_value');

        if ($choiceValue instanceof \Closure) {
            return null;
        }

        if ($choiceValue instanceof PropertyPathInterface) {
            $choiceValue = (string) $choiceValue;
        }

        if (\is_string($choiceValue)) {
            return $this->getEntityMetadata()->hasField($choiceValue) ? $choiceValue : null;
        }

        $identifierFields = $this->getEntityMetadata()->getIdentifierFieldNames();

        if (1 !== \count($identifierFields) || !$this->getEntityMetadata()->hasField($identifierFields[0])) {
            return null;
        }

        return $identifierFields[0];
    }

    /**
     * Returns null for values that cannot be compared with the field, so they are ignored.
     */
    private function normalizeExcludedValue(string $value, string $type): mixed
    {
        return match ($type) {
            'smallint', 'integer', 'bigint' => false !== ($int = filter_var($value, \FILTER_VALIDATE_INT)) ? $int : null,
            'string', 'ascii_string', 'text' => $value,
            'guid' => preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value) ? $value : null,
            'uuid' => class_exists(Uuid::class) && Uuid::isValid($value, Uuid::FORMAT_ALL) ? Uuid::fromString($value) : null,
            'ulid' => class_exists(Ulid::class) && Ulid::isValid($value) ? Ulid::fromString($value) : null,
            default => null,
        };
    }
}
