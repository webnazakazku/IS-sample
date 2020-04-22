<?php

namespace Tests\App;

class Helpers
{

	public function getArrayResult($repo, $params)
	{
		$qb = $repo->createQueryBuilder('e');

		foreach ($params as $key => $param) {
			$qb->andWhere('e.' . $key . ' = :' . $key);
		}

		$qb->setParameters($params);
		$query = $qb->getQuery();

		$result = $query->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);

		foreach ($result as $key => $item) {
			unset($result[$key]['id']);
		}

		return $result;
	}

	public function getArraySingleResult($repo, $params)
	{
		$result = $this->getArrayResult($repo, $params);

		if ($result) {
			return $result[0];
		} else {
			return [];
		}
	}

	public function countResult($repo, $params)
	{
		$qb = $repo->createQueryBuilder('e')
			->select('count(e.id)');

		foreach ($params as $key => $param) {
			$qb->andWhere('e.' . $key . ' = :' . $key);
		}

		$qb->setParameters($params);
		$query = $qb->getQuery();

		$result = $query->getSingleScalarResult();

		return $result;
	}

	public function removeItems($em, $repo, $params)
	{
		$entities = $repo->findBy($params);
		foreach ($entities as $entity) {
			$em->remove($entity);
		}

		$em->flush();
	}
}
