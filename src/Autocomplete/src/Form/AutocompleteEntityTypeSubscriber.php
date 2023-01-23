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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\Utility\PersisterHelper;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Helps transform ParentEntityAutocompleteType into a EntityType that will not load all options.
 *
 * @internal
 */
final class AutocompleteEntityTypeSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ?string $autocompleteUrl = null
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

        if (!isset($data['autocomplete']) || '' === $data['autocomplete']) {
            $options['choices'] = [];
        } else {
            /** @var EntityManagerInterface $em */
            $em = $options['em'];
            $repository = $em->getRepository($options['class']);

            $idField = $options['id_reader']->getIdField();
            $idType = PersisterHelper::getTypeOfField($idField, $em->getClassMetadata($options['class']), $em)[0];

            if ($options['multiple']) {
                $params = [];
                $idx = 0;

                foreach ($data['autocomplete'] as $id) {
                    $params[":id_$idx"] = new Parameter("id_$idx", $id, $idType);
                    ++$idx;
                }

                $queryBuilder = $repository->createQueryBuilder('o');

                if ($params) {
                    $queryBuilder
                        ->where(sprintf("o.$idField IN (%s)", implode(', ', array_keys($params))))
                        ->setParameters(new ArrayCollection($params));
                }

                $options['choices'] = $queryBuilder->getQuery()->getResult();
            } else {
                $options['choices'] = $repository->createQueryBuilder('o')
                    ->where("o.$idField = :id")
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
