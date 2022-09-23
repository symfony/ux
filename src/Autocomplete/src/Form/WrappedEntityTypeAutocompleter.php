<?php

namespace Symfony\UX\Autocomplete\Form;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyAccess\PropertyPathInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\UX\Autocomplete\Doctrine\EntityMetadata;
use Symfony\UX\Autocomplete\Doctrine\EntityMetadataFactory;
use Symfony\UX\Autocomplete\Doctrine\EntitySearchUtil;
use Symfony\UX\Autocomplete\EntityAutocompleterInterface;

/**
 * An entity auto-completer that wraps a form type to get its information.
 *
 * @internal
 */
final class WrappedEntityTypeAutocompleter implements EntityAutocompleterInterface
{
    private ?FormInterface $form = null;
    private ?EntityMetadata $entityMetadata = null;

    public function __construct(
        private string $formType,
        private FormFactoryInterface $formFactory,
        private EntityMetadataFactory $metadataFactory,
        private PropertyAccessorInterface $propertyAccessor,
        private EntitySearchUtil $entitySearchUtil
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

            return $queryBuilder;
        }

        // avoid filtering if there is no query
        if (!$query) {
            return $queryBuilder;
        }

        // Applying max result limit or not
        $queryBuilder->setMaxResults($this->getMaxResults());

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

        // 0 hardcoded as the "index", should not be relevant
        return $choiceLabel($entity, 0, $this->getValue($entity));
    }

    public function getValue(object $entity): string
    {
        return $this->getEntityMetadata()->getIdValue($entity);
    }

    public function isGranted(Security $security): bool
    {
        $securityOption = $this->getForm()->getConfig()->getOption('security');

        if (false === $securityOption) {
            return true;
        }

        if (\is_string($securityOption)) {
            return $security->isGranted($securityOption, $this);
        }

        if (\is_callable($securityOption)) {
            return $securityOption($security);
        }

        throw new \InvalidArgumentException('Invalid passed to the "security" option: it must be the boolean true, a string role or a callable.');
    }

    private function getFormOption(string $name): mixed
    {
        $form = $this->getForm();
        $formOptions = $form['autocomplete']->getConfig()->getOptions();

        return $formOptions[$name] ?? null;
    }

    private function getForm(): FormInterface
    {
        if (null === $this->form) {
            $this->form = $this->formFactory->create($this->formType);
        }

        return $this->form;
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
}
