<?php

namespace Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;

class ListsRepository {

	protected $db;

	public function __construct(Connection $db) {
		$this->db = $db;
		$this->productsRepository = new ProductsRepository($db);
	}

	public function findAll() {
		$queryBuilder = $this->queryAll();

		return $queryBuilder->execute()->fetchAll();
	}

	public function findOneById($id) {
		$queryBuilder = $this->queryAll();
		$queryBuilder->where('l.id = :id')
		             ->setParameter(':id', $id, \PDO::PARAM_INT);
		$result = $queryBuilder->execute()->fetch();


		return $result;
	}

	public function save($list) {
		$this->db->beginTransaction();

		try {
			$currentDateTime = new \DateTime();
			$list['modifiedAt'] = $currentDateTime->format('Y-m-d H:i:s');

			if(isset($list['id']) && ctype_digit((string) $list['id'])) {
				$listId = $list['id'];
				unset($list['id']);

				$this->db->update('lists', $list, ['id' => $listId]);
			} else {
				$list['createdAt'] = $currentDateTime->format('Y-m-d H:i:s');
				$this->db->insert('lists', $list);
			}
			$this->db->commit();
		} catch (DBALException $e) {
			$this->db->rollBack();
			throw $e;
		}
	}


	public function updateModiefiedDate($listId) {
		$currentDateTime = new \DateTime();
		$list['modifiedAt'] = $currentDateTime->format('Y-m-d H:i:s');
		$this->db->update('lists', $list, ['id' => $listId]);
	}

	public function delete($list) {
		$this->db->delete('lists', ['id' => $list['id']]);
		$this->removeLinkedProducts($list['id']);
	}

	public function getCurrentSpendings($listId) {

		$productsIds = $this->findLinkedProductsIds($listId);

		$queryBuilder = $this->db->createQueryBuilder();
		$queryBuilder->select('SUM(p.finalValue) AS finalValue')
		             ->from('products', 'p')
		             ->where('p.id IN (:ids)')
		             ->setParameter(':ids', $productsIds, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY);

		$result =  $queryBuilder->execute()->fetch();
		return $result['finalValue'];
	}

	public function findLinkedProducts($listId)
	{
		$productsIds = $this->findLinkedProductsIds($listId);

		return is_array($productsIds)
			? $this->productsRepository->findById($productsIds)
			: [];
	}

	public function getConnectedList($productId) {
		$queryBuilder = $this->db->createQueryBuilder();

		$queryBuilder->select('pl.list_id')
		             ->from('products_lists', 'pl')
		             ->where('pl.product_id = :productId')
		             ->setParameter(':productId', $productId);

		$result = $queryBuilder->execute()->fetch();
		return $result;
	}

	protected function findLinkedProductsIds($listId) {
		$queryBuilder = $this->db->createQueryBuilder()
			->select('pl.product_id')
			->from('products_lists', 'pl')
			->where('pl.list_id = :listId')
			->setParameter(':listId', $listId, \PDO::PARAM_INT);
		$result = $queryBuilder->execute()->fetchAll();

		return isset($result) ? array_column($result, 'product_id') : [];
	}

	protected function removeLinkedProducts($listId) {
		return $this->db->delete('products_lists', ['list_id' => $listId]);
	}

	protected function queryAll() {
		$queryBuilder = $this->db->createQueryBuilder();

		return $queryBuilder->select('l.id', 'l.name', 'l.maxCost')
			->from('lists', 'l');
	}

}