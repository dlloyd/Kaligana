<?php

namespace AppBundle\Repository;

/**
 * ProductRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ProductRepository extends \Doctrine\ORM\EntityRepository
{
	public function findLastByType($typeId,$number) {
		$qb = $this->createQueryBuilder('p');
		$qb->where('p.productType = :type');
		$qb->orderBy('p.id', 'DESC');
		$qb->setMaxResults($number);
		$qb->setParameter('type',$typeId);
		

		return $qb->getQuery()->getResult();
	}

	public function findPageContentByType($typeId,$page) {
		$qb = $this->createQueryBuilder('p');
		$qb->where('p.productType = :type');
		$qb->orderBy('p.id', 'DESC');
		$qb->setFirstResult($page-1);
		$qb->setMaxResults(20);
		$qb->setParameter('type',$typeId);
		

		return $qb->getQuery()->getResult();
	}
}
