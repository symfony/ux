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

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Utility\PersisterHelper;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Helps transform ParentEntityAutocompleteType into a EntityType that will not load all options.
 *
 * @internal
 *
 * @deprecated since Symfony UX 2.13
 */
final class AutocompleteEntityTypeSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ?string $autocompleteUrl = null,
    ) {
    }

    public function preSetData(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData() ?: [];

        $options = $form->getConfig()->getOptions();
        $options['compound'] = false;
        $options['choices'] = is_iterable($data) ? $data : [$data];
        // pass to AutocompleteChoiceTypeExtension
        $options['autocomplete'] = true;
        $options['autocomplete_url'] = $this->autocompleteUrl;
        unset($options['searchable_fields'], $options['security'], $options['filter_query']);

        $form->add('autocomplete', EntityType::class, $options);
    }

    public function preSubmit(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();
        $options = $form->get('autocomplete')->getConfig()->getOptions();

        /** @var EntityManagerInterface $em */
        $em = $options['em'];
        $repository = $em->getRepository($options['class']);
        $queryBuilder = $options['query_builder'] ?: $repository->createQueryBuilder('o');
        $rootAlias = $queryBuilder->getRootAliases()[0];

        if (!isset($data['autocomplete']) || '' === $data['autocomplete']) {
            $options['choices'] = [];
        } else {
            $idField = $options['id_reader']->getIdField();
            $idType = PersisterHelper::getTypeOfField($idField, $em->getClassMetadata($options['class']), $em)[0];

            if ($options['multiple']) {
                $params = [];
                $idx = 0;

                foreach ($data['autocomplete'] as $id) {
                    $params[":id_$idx"] = [$id, $idType];
                    ++$idx;
                }

                if ($params) {
                    $queryBuilder
                        ->andWhere(\sprintf("$rootAlias.$idField IN (%s)", implode(', ', array_keys($params))))
                    ;
                    foreach ($params as $key => $param) {
                        $queryBuilder->setParameter($key, $param[0], $param[1]);
                    }
                }

                $options['choices'] = $queryBuilder->getQuery()->getResult();
            } else {
                $options['choices'] = $queryBuilder
                    ->andWhere("$rootAlias.$idField = :id")
                    ->setParameter('id', $data['autocomplete'], $idType)
                    ->getQuery()
                    ->getResult();
            }
        }

        // reset some critical lazy options
        unset($options['em'], $options['loader'], $options['empty_data'], $options['choice_list'], $options['choices_as_values']);

        $form->add('autocomplete', EntityType::class, $options);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SET_DATA => 'preSetData',
            FormEvents::PRE_SUBMIT => 'preSubmit',
        ];
    }
}
