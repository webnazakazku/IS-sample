<?php
declare(strict_types=1);

namespace App\Repositories;

use Doctrine;
use Doctrine\ORM\EntityRepository;

class InvoiceRepository extends EntityRepository
{

	public function fetchAcceptedInvoicesFromForeign($userId, $from, $to)
	{
		$qb = $this->createQueryBuilder('i');
		$qb->where('i.user = :user')
			->andWhere('i.type = :type')
			->andWhere('i.supplierCountry != :country')
			->andWhere('i.issuedDate >= :from')
			->andWhere('i.issuedDate <= :to')
			->setParameter('user', $userId)
			->setParameter('type', 'accepted')
			->setParameter('country', 'CZ')
			->setParameter('from', $from)
			->setParameter('to', $to);

		return $qb->getQuery()
				->getResult();
	}

	public function fetchInvoicesArray($userId)
	{
		$qb = $this->createQueryBuilder('i')
			->select('i')
			->where('i.user = :userId')
			->setParameter('userId', $userId);

		$result = $qb->getQuery()
			->setHint(Doctrine\ORM\Query::HINT_INCLUDE_META_COLUMNS, true)
			->getArrayResult();

		$return = [];
		foreach ($result as $item) {
			$return[$item['id']] = $item;
		}

		return $return;
	}
}
