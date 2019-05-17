<?php

namespace AppBundle\Entity;

use DateTime;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\Expr\Join;

class UserRepository extends EntityRepository
{

    public function countRegistered($email)
    {
        $qb = $this->createQueryBuilder('u');
        return $qb->select('count(u)')
                        ->where('u.email = :email')
                        ->setParameter('email', $email)
                        ->andWhere($qb->expr()->isNotNull('u.registeredAt'))
                        ->getQuery()
                        ->getSingleScalarResult();
    }

    public function getPreregistered($email)
    {
        $qb = $this->createQueryBuilder('u');

        try {
            return $qb->where('u.email = :email')
                            ->setParameter('email', $email)
                            ->andWhere($qb->expr()->isNull('u.registeredAt'))
                            ->getQuery()
                            ->getSingleResult();
        } catch (NoResultException $ex) {
            
        }

        return null;
    }

    /**
     * @param DateTime $date
     * @return User[]
     */
    public function getRegisteredOnDate(DateTime $date)
    {
        $minDateTime = clone $date;
        $minDateTime->setTime(0, 0, 0);

        $maxDateTime = clone $date;
        $maxDateTime->setTime(23, 59, 59);

        return $this->createQueryBuilder('e')
                        ->join($join, $alias, $conditionType, $condition)
                        ->where('e.registeredAt >= :minDateTime')
                        ->andWhere('e.registeredAt <= :maxDateTime')
                        ->setParameters([
                            'minDateTime' => $minDateTime,
                            'maxDateTime' => $maxDateTime
                        ])
                        ->getQuery()
                        ->getResult();
    }

    /**
     * @param DateTime $date
     * @return User[]
     */
    public function getLandlordsRegisteredOnDate(DateTime $date)
    {
        $minDateTime = clone $date;
        $minDateTime->setTime(0, 0, 0);

        $maxDateTime = clone $date;
        $maxDateTime->setTime(23, 59, 59);

        $userIdsFromHouseCreations = $this->createQueryBuilder('e')
                ->select('distinct(hc.user) as user_id')
                ->from('AppBundle:HouseCreation', 'hc')
                ->getQuery()
                ->getResult();

        $userIdsFromHouses = $this->createQueryBuilder('e')
                ->select('distinct(h.landlord) as user_id')
                ->from('AppBundle:House', 'h')
                ->where('h.landlord is not null')
                ->getQuery()
                ->getResult();

        $userIds = array_unique(array_merge(array_column($userIdsFromHouseCreations, 'user_id'), array_column($userIdsFromHouses, 'user_id')));

        return $this->createQueryBuilder('e')
                        ->where('e.id in (:ids)')
                        ->andWhere('e.registeredAt >= :minDateTime')
                        ->andWhere('e.registeredAt <= :maxDateTime')
                        ->setParameters([
                            'ids' => $userIds,
                            'minDateTime' => $minDateTime,
                            'maxDateTime' => $maxDateTime
                        ])
                        ->getQuery()
                        ->getResult();
    }

    /**
     * Gets users that do not own houses
     * 
     * @param DateTime $registeredOnDate
     * @param string $registeredOnSection
     * @return User[]
     */
    public function getTenants(DateTime $registeredOnDate, $registeredOnSection)
    {
        $minDateTime = clone $registeredOnDate;
        $minDateTime->setTime(0, 0, 0);

        $maxDateTime = clone $registeredOnDate;
        $maxDateTime->setTime(23, 59, 59);

        return $this->createQueryBuilder('e')
                        ->leftJoin('e.ownedHouses', 'h')
                        ->where('e.registeredOnSection = :siteSection')
                        ->andWhere('e.registeredAt >= :minDateTime')
                        ->andWhere('e.registeredAt <= :maxDateTime')
                        ->andWhere('h.id is null')
                        ->setParameters([
                            'siteSection' => $registeredOnSection,
                            'minDateTime' => $minDateTime,
                            'maxDateTime' => $maxDateTime
                        ])
                        ->getQuery()
                        ->getResult();
    }

    /**
     * @param DateTime $date
     * @return User[]
     */
    public function getLandlordsLastSeenOn(DateTime $date)
    {
        $minDateTime = clone $date;
        $minDateTime->setTime(0, 0, 0);

        $maxDateTime = clone $date;
        $maxDateTime->setTime(23, 59, 59);

        $userIdsFromHouseCreations = $this->createQueryBuilder('e')
                ->select('distinct(hc.user) as user_id')
                ->from('AppBundle:HouseCreation', 'hc')
                ->getQuery()
                ->getResult();

        $userIdsFromHouses = $this->createQueryBuilder('e')
                ->select('distinct(h.landlord) as user_id')
                ->from('AppBundle:House', 'h')
                ->where('h.landlord is not null')
                ->getQuery()
                ->getResult();

        $userIds = array_unique(array_merge(array_column($userIdsFromHouseCreations, 'user_id'), array_column($userIdsFromHouses, 'user_id')));

        return $this->createQueryBuilder('e')
                        ->where('e.id in (:ids)')
                        ->andWhere('e.lastLogin >= :minDateTime')
                        ->andWhere('e.lastLogin <= :maxDateTime')
                        ->setParameters([
                            'ids' => $userIds,
                            'minDateTime' => $minDateTime,
                            'maxDateTime' => $maxDateTime
                        ])
                        ->getQuery()
                        ->getResult();
    }

}
