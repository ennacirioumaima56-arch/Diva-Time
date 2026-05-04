<?php

namespace App\Repository;

use App\Entity\Message;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Message>
 */
class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    /**
     * @return Message[] Returns an array of Message objects
     */
    public function findAllLatest(): array
    {
        return $this->createQueryBuilder('m')
            ->orderBy('m.createdAt', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function countUnreadForUser(\App\Entity\User $user): int
    {
        $qb = $this->createQueryBuilder('m')
            ->select('count(m.id)');

        if ($user->getLastSeenMessagesAt()) {
            $qb->where('m.createdAt > :lastSeen')
               ->setParameter('lastSeen', $user->getLastSeenMessagesAt());
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
