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
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\ChoiceList\Factory\Cache\ChoiceLabel;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyAccess\PropertyPathInterface;
use Symfony\Contracts\Service\ResetInterface;
use Symfony\UX\Autocomplete\Doctrine\EntityMetadata;
use Symfony\UX\Autocomplete\Doctrine\EntityMetadataFactory;
use Symfony\UX\Autocomplete\Doctrine\EntitySearchUtil;
use Symfony\UX\Autocomplete\OptionsAwareEntityAutocompleterInterface;

/**
 * An entity auto-completer that wraps a form type to get its information.
 *
 * @internal
 */
final class WrappedEntityTypeAutocompleter implements OptionsAwareEntityAutocompleterInterface, ResetInterface
{
    private ?FormInterface $form = null;
    private ?EntityMetadata $entityMetadata = null;
    private array $options = [];

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

            return $queryBuilder;
        }

        // Applying max result limit or not
        $queryBuilder->setMaxResults($this->getMaxResults());

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

    public function getGroupBy(): mixed
    {
        return $this->getFormOption('group_by');
    }

    private function getFormOption(string $name): mixed
    {
        $form = $this->getForm();
        // Remove when dropping support for ParentEntityAutocompleteType
        $form = $form->has('autocomplete') ? $form->get('autocomplete') : $form;
        $formOptions = $form->getConfig()->getOptions();

        return $formOptions[$name] ?? null;
    }

    private function getForm(): FormInterface
    {
        if (null === $this->form) {
            $this->form = $this->formFactory->create($this->formType, options: $this->options);
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

    public function setOptions(array $options): void
    {
        if (null !== $this->form) {
            throw new \LogicException('The options can only be set before the form is created.');
        }

        $this->options = $options;
    }

    public function reset(): void
    {
        unset($this->form);
        $this->form = null;
    }
}
