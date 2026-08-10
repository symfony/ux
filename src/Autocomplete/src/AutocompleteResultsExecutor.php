<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Autocomplete;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\PropertyAccess\PropertyPathInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\Autocomplete\Doctrine\DoctrineRegistryWrapper;

/**
 * @author Ryan Weaver <ryan@symfonycasts.com>
 */
final class AutocompleteResultsExecutor
{
    private PropertyAccessorInterface $propertyAccessor;
    private ?Security $security;
    private ?TranslatorInterface $translator;

    public function __construct(
        private DoctrineRegistryWrapper $managerRegistry,
        $propertyAccessor,
        /* Security $security = null, TranslatorInterface $translator = null */
    ) {
        if ($propertyAccessor instanceof Security) {
            trigger_deprecation('symfony/ux-autocomplete', '2.8.0', 'Passing a "%s" instance as the second argument of "%s()" is deprecated, pass a "%s" instance instead.', Security::class, __METHOD__, PropertyAccessorInterface::class);
            $this->security = $propertyAccessor;
            $this->propertyAccessor = new PropertyAccessor();
            $this->translator = \func_num_args() >= 3 ? func_get_arg(2) : null;
        } else {
            $this->propertyAccessor = $propertyAccessor;
            $this->security = \func_num_args() >= 3 ? func_get_arg(2) : null;
            $this->translator = \func_num_args() >= 4 ? func_get_arg(3) : null;
        }
    }

    public function fetchResults(EntityAutocompleterInterface $autocompleter, string $query, int $page): AutocompleteResults
    {
        if ($this->security && !$autocompleter->isGranted($this->security)) {
            throw new AccessDeniedException('Access denied from autocompleter class.');
        }

        $queryBuilder = $autocompleter->createFilteredQueryBuilder(
            $this->managerRegistry->getRepository($autocompleter->getEntityClass()),
            $query
        );

        // if no max is set, set one
        if (!$queryBuilder->getMaxResults()) {
            $queryBuilder->setMaxResults(10);
        }

        $page = max(1, $page);

        $queryBuilder->setFirstResult(($page - 1) * $queryBuilder->getMaxResults());

        $paginator = new Paginator($queryBuilder);

        $nbPages = (int) ceil($paginator->count() / $queryBuilder->getMaxResults());
        $hasNextPage = $page < $nbPages;

        $results = [];

        if (!method_exists($autocompleter, 'getGroupBy') || null === $groupBy = $autocompleter->getGroupBy()) {
            foreach ($paginator as $entity) {
                $results[] = $this->formatResult($autocompleter, $entity);
            }

            return new AutocompleteResults($results, $hasNextPage);
        }

        if (\is_string($groupBy)) {
            $groupBy = new PropertyPath($groupBy);
        }

        if ($groupBy instanceof PropertyPathInterface) {
            $accessor = $this->propertyAccessor;
            $groupBy = static function ($choice) use ($accessor, $groupBy) {
                try {
                    return $accessor->getValue($choice, $groupBy);
                } catch (UnexpectedTypeException) {
                    return null;
                }
            };
        }

        if (!\is_callable($groupBy)) {
            throw new \InvalidArgumentException(\sprintf('Option "group_by" must be callable, "%s" given.', get_debug_type($groupBy)));
        }

        $translationDomain = method_exists($autocompleter, 'getTranslationDomain') ? $autocompleter->getTranslationDomain() : null;

        $optgroups = [];

        foreach ($paginator as $entity) {
            $result = $this->formatResult($autocompleter, $entity);

            $groupLabels = $groupBy($entity, $result['value'], $result['text']);

            if (null !== $groupLabels) {
                $groupLabels = \is_array($groupLabels) ? $groupLabels : [$groupLabels];
                $groupValues = [];

                foreach ($groupLabels as $groupLabel) {
                    $label = $this->translateGroupLabel($groupLabel, $translationDomain);
                    // the value ties the results to their optgroup: keep the untranslated
                    // one whenever it is available, so that it does not depend on the locale
                    $value = $groupLabel instanceof TranslatableInterface ? $label : (string) $groupLabel;

                    $groupValues[] = $value;
                    $optgroups[$value] ??= ['value' => $value, 'label' => $label];
                }

                $result['group_by'] = $groupValues;
            }

            $results[] = $result;
        }

        return new AutocompleteResults($results, $hasNextPage, array_values($optgroups));
    }

    private function translateGroupLabel(mixed $groupLabel, string|false|null $translationDomain): string
    {
        if (null === $this->translator || false === $translationDomain) {
            return (string) $groupLabel;
        }

        if ($groupLabel instanceof TranslatableInterface) {
            return $groupLabel->trans($this->translator);
        }

        return $this->translator->trans((string) $groupLabel, [], $translationDomain);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatResult(EntityAutocompleterInterface $autocompleter, object $entity): array
    {
        $attributes = [];
        if (method_exists($autocompleter, 'getAttributes')) {
            $attributes = $autocompleter->getAttributes($entity);
        }

        return [
            ...$attributes,
            'value' => $autocompleter->getValue($entity),
            'text' => $autocompleter->getLabel($entity),
        ];
    }
}
