<?php

namespace AppBundle\Repository;

/**
 * InvoiceRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class InvoiceRepository extends \Doctrine\ORM\EntityRepository
{

	public function getCount() {
		return $this->createQueryBuilder('i')
		 ->select('COUNT(i)')
		 ->getQuery()
		 ->getSingleScalarResult();
	}
	


}
