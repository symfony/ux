<?php

namespace App\Repository;

use App\Entity\Client;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Client>
 */
final class ClientRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Client::class);
    }

    /**
     * Returns the clients of the given page, wrapped in a Doctrine ORM
     * Paginator so the total count is computed in one query.
     */
    public function paginate(int $page, int $pageSize): Paginator
    {
        $query = $this->getEntityManager()
            ->createQuery('SELECT c FROM ' . Client::class . ' c ORDER BY c.id ASC')
            ->setFirstResult(max(0, ($page - 1) * $pageSize))
            ->setMaxResults($pageSize);

        return new Paginator($query, fetchJoinCollection: false);
    }
}
