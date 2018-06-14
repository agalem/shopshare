<?php

namespace Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;

class ListsRepository {

	protected $db;

	public function __construct(Connection $db) {
		$this->db = $db;
		$this->productsRepository = new ProductsRepository($db);
		$this->userRepository = new UserRepository($db);
	}

	public function findAll($userId) {
		$queryBuilder = $this->queryAll();
		$queryBuilder->where('l.createdBy = :userId')
		             ->setParameter(':userId', $userId, \PDO::PARAM_INT);


		return $queryBuilder->execute()->fetchAll();
	}


	public function findOneById($id) {
		$queryBuilder = $this->queryAll();
		$queryBuilder->where('l.id = :id')
		             ->setParameter(':id', $id, \PDO::PARAM_INT);
		$result = $queryBuilder->execute()->fetch();


		return $result;
	}

	public function findLinkedLists($userId) {

		try {

			$linkedListsIds = $this->db->createQueryBuilder();
			$linkedListsIds->select('lu.list_id', 'lu.user_id')
			            ->from('lists_users', 'lu')
			            ->where('lu.user_id = :userId')
			            ->setParameter(':userId', $userId, \PDO::PARAM_INT);

			$linkedListsIds = $linkedListsIds->execute()->fetchAll();

			$linkedListsIds = array_column($linkedListsIds, 'list_id');

			$linkedLists = $this->queryAll();
			$linkedLists->where('l.id IN (:ids)')
			            ->setParameter(':ids', $linkedListsIds, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY);


			return $linkedLists->execute()->fetchAll();

		} catch (DBALException $exception) {
			throw $exception;
		}
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

		$this->removeLinkedProducts($list['id']);
		$this->db->delete('lists', ['id' => $list['id']]);

	}

	public function deleteConnection($listId) {
		$this->db->delete('lists_users', ['id_list' => $listId]);
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

	public function findUserProducts($listId, $userId) {
		$productsIds = $this->findLinkedProductsIds($listId);

		$queryBuilder = $this->db->createQueryBuilder();
		$queryBuilder->select('p.id', 'p.name', 'p.value', 'p.quantity', 'p.isBought', 'p.createdBy', 'p.lastModifiedBy', 'p.createdAt')
			->from('products', 'p')
			->where('p.id IN (:ids) AND p.createdBy = :userId')
			->setParameter(':ids', array_column($productsIds, 'product_id'),  \Doctrine\DBAL\Connection::PARAM_INT_ARRAY)
			->setParameter(':userId', $userId, \PDO::PARAM_INT);


		return $queryBuilder->execute()->fetchAll();

	}

	public function findOtherProducts($listId, $userId) {
		$productsIds = $this->findLinkedProductsIds($listId);

		$queryBuilder = $this->db->createQueryBuilder();
		$queryBuilder->select('p.id', 'p.name', 'p.value', 'p.quantity', 'p.isBought', 'p.createdBy', 'p.lastModifiedBy', 'p.createdAt')
		             ->from('products', 'p')
					->where('p.id IN (:ids) AND p.createdBy NOT LIKE :userId')
					->setParameter(':ids', array_column($productsIds, 'product_id'), Connection::PARAM_INT_ARRAY)
					->setParameter(':userId', $userId, \PDO::PARAM_INT);

		return $queryBuilder->execute()->fetchAll();
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


		return isset($result) ? $result : [];
	}


	public function addUser($listId, $userId) {

		$ifConnectionExists = $this->db->createQueryBuilder();
		$ifConnectionExists->select('lu.list_id', 'lu.user_id')
			->from('lists_users', 'lu')
			->where('lu.list_id = :listId AND lu.user_id = :userId')
			->setParameter(':listId', $listId, \PDO::PARAM_INT)
			->setParameter(':userId', $userId, \PDO::PARAM_INT);

		$ifConnectionExists->execute()->fetch() ? $ifConnectionExists = true : $ifConnectionExists = false;

		$newConnection['list_id'] = $listId;
		$newConnection['user_id'] = $userId;

		if(!$ifConnectionExists == true) {
			return [];
		} else {
			$this->db->insert( 'lists_users', $newConnection );
		}
		return $newConnection;

	}

	protected function removeLinkedProducts($listId) {
		$this->db->beginTransaction();

		try {

			$productsIds = $this->findLinkedProductsIds($listId);

			foreach ($productsIds as $productId) {
				$this->db->delete('products_actions', ['product_id' => $productId]);
			}

			$this->db->delete('products_lists', ['list_id' => $listId]);

			foreach ($productsIds as $productId) {
				$this->db->delete('products', ['id' => $productId]);
			}

			$this->db->commit();

		} catch (DBALException $exception) {
			throw $exception;
		}

	}

	protected function queryAll() {
		$queryBuilder = $this->db->createQueryBuilder();

		return $queryBuilder->select('l.id', 'l.name', 'l.maxCost', 'l.createdBy')
			->from('lists', 'l');
	}

}