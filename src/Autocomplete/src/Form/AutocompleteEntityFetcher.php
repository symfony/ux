<?php

namespace Symfony\UX\Autocomplete\Form;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\Utility\PersisterHelper;
use Symfony\Bridge\Doctrine\Form\ChoiceList\IdReader;
use Symfony\UX\Autocomplete\EntityFetcherInterface;

class AutocompleteEntityFetcher implements EntityFetcherInterface
{
    public function __construct(private EntityManagerInterface $entityManager, private IdReader $idReader)
    {
    }

    public function fetchMultipleEntities(string $entityClass, array $identifiers): array
    {
        $repository = $this->entityManager->getRepository($entityClass);

        $idField = $this->idReader->getIdField();
        $idType = PersisterHelper::getTypeOfField($idField, $this->entityManager->getClassMetadata($entityClass), $this->entityManager)[0];

        $params = [];
        $idx = 0;

        foreach ($identifiers as $id) {
            $params[":id_$idx"] = new Parameter("id_$idx", $id, $idType);
            ++$idx;
        }

        $queryBuilder = $repository->createQueryBuilder('o');

        if ($params) {
            $queryBuilder
                ->where(sprintf("o.$idField IN (%s)", implode(', ', array_keys($params))))
                ->setParameters(new ArrayCollection($params));
        }

        return $queryBuilder->getQuery()->getResult();
    }

    public function fetchSingleEntity(string $entityClass, mixed $identifier): ?object
    {
        $repository = $this->entityManager->getRepository($entityClass);

        $idField = $this->idReader->getIdField();
        $idType = PersisterHelper::getTypeOfField($idField, $this->entityManager->getClassMetadata($entityClass), $this->entityManager)[0];

        return $repository->createQueryBuilder('o')
            ->where("o.$idField = :id")
            ->setParameter('id', $identifier, $idType)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
