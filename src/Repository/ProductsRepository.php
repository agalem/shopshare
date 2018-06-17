<?php

namespace Repository;


use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;

class ProductsRepository {

	protected $db;

	public function __construct( Connection $db ) {
		$this->db = $db;
	}

	public function findAll() {
		$queryBuilder = $this->queryAll();

		return $queryBuilder->execute()->fetchAll();
	}


	public function findOneById( $id ) {
		$queryBuilder = $this->queryAll();
		$queryBuilder->where( 'p.id = :id' )
		             ->setParameter( ':id', $id, \PDO::PARAM_INT );
		$result = $queryBuilder->execute()->fetch();

		return !$result ? [] : $result;
	}

	public function findOneByName($name) {
		$queryBuilder = $this->queryAll();

		$queryBuilder->where('p.name = :name')
		             ->setParameter(':name', $name, \PDO::PARAM_STR);
		$result = $queryBuilder->execute()->fetch();

		return !$result ? [] : $result;
	}

	public function findById($ids) {
		$queryBuilder = $this->queryAll();
		$queryBuilder->where('p.id IN (:ids)')
		             ->setParameter(':ids', $ids, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY);

		return $queryBuilder->execute()->fetchAll();
	}


	public function findUserProductsIds($userId) {

		$queryBuilder = $this->queryAll();
		$queryBuilder->where('p.createdBy = :userId ')
		             ->setParameter(':userId', $userId, \PDO::PARAM_INT);

		return array_column($queryBuilder->execute()->fetchAll(), 'id');

	}

	public function findBoughtForUserProductsIds($userId) {

		$queryBuilder = $this->queryAll();
		$queryBuilder->where('p.createdBy != :userId')
		             ->setParameter(':userId', $userId, \PDO::PARAM_INT);

		return array_column($queryBuilder->execute()->fetchAll(), 'id');

	}


	public function getBoughtByUser($productsIds, $userId) {

		$queryBuilder = $this->db->createQueryBuilder();
		$queryBuilder->select('pa.product_id', 'pa.modifiedBy', 'pa.quantity', 'pa.price', 'pa.message', 'p.name', 'u.login', 'pa.id')
					->from('products_actions', 'pa')
					->innerJoin('pa', 'products', 'p', 'p.id = pa.product_id')
					->innerJoin('pa', 'users', 'u', 'u.id = pa.modifiedBy')
					->where('pa.product_id IN (:ids) AND pa.modifiedBy != :userId')
					->setParameter(':ids', $productsIds, Connection::PARAM_INT_ARRAY)
					->setParameter(':userId', $userId, \PDO::PARAM_INT);

		return $queryBuilder->execute()->fetchAll();

	}


	public function getBoughtForUser($productsIds, $userId) {

		$queryBuilder = $this->db->createQueryBuilder();
		$queryBuilder->select('pa.product_id', 'pa.modifiedBy', 'pa.quantity', 'pa.price', 'pa.message', 'p.name', 'p.createdBy','u.login', 'pa.id')
					->from('products_actions', 'pa')
					->innerJoin('pa', 'products', 'p', 'p.id = pa.product_id')
					->innerJoin('p', 'users', 'u', 'u.id = p.createdBy')
					->where('pa.product_id IN (:ids) AND p.createdBy != :userId AND pa.modifiedBy = :userId')
					->setParameter(':ids', $productsIds, Connection::PARAM_INT_ARRAY)
					->setParameter(':userId', $userId, \PDO::PARAM_INT);

		return $queryBuilder->execute()->fetchAll();

	}



	public function getBoughtByUserForUser($productsIds, $userId) {

		$queryBuilder = $this->db->createQueryBuilder();
		$queryBuilder->select('pa.product_id', 'pa.modifiedBy', 'pa.quantity', 'pa.price', 'pa.message', 'p.name', 'u.login', 'pa.id')
		             ->from('products_actions', 'pa')
		             ->innerJoin('pa', 'products', 'p', 'p.id = pa.product_id')
		             ->innerJoin('pa', 'users', 'u', 'u.id = pa.modifiedBy')
		             ->where('pa.product_id IN (:ids) AND pa.modifiedBy = :userId')
		             ->setParameter(':ids', $productsIds, Connection::PARAM_INT_ARRAY)
		             ->setParameter(':userId', $userId, \PDO::PARAM_INT);


		return $queryBuilder->execute()->fetchAll();

	}

	public function isUserAction($id, $userId) {

		$queryBuilder = $this->db->createQueryBuilder();
		$queryBuilder->select('pa.product_id', 'pa.modifiedBy')
					->from('products_actions', 'pa')
					->where('pa.id = :id AND pa.modifiedBy = :userId')
					->setParameter(':id', $id, \PDO::PARAM_INT)
					->setParameter(':userId', $userId, \PDO::PARAM_INT);

		$result = $queryBuilder->execute()->fetch();

		return $result ? true : false;
	}

	public function findPaymentById($id) {

		$queryBuilder = $this->db->createQueryBuilder();
		$queryBuilder->select('pa.id', 'pa.product_id', 'pa.modifiedBy', 'pa.quantity', 'pa.price', 'pa.message', 'p.name', 'u.login')
					->from('products_actions', 'pa')
					->innerJoin('pa', 'products', 'p', 'pa.product_id = p.id')
					->innerJoin('pa', 'users', 'u', 'pa.modifiedBy = u.id')
					->where('pa.id = :id')
					->setParameter(':id', $id, \PDO::PARAM_INT);

		return $queryBuilder->execute()->fetch();

	}


	public function deletePayment($id) {

		try {

			$this->db->delete('products_actions', ['id' => $id]);

		} catch (DBALException $exception) {
			return [];
		}

	}

	public function save($listId, $product, $username)
	{
		$this->db->beginTransaction();

		try {
			$currentDateTime = new \DateTime();
			$product['modifiedAt'] = $currentDateTime->format('Y-m-d H:i:s');
			$product['finalValue'] = $product['value']*$product['quantity'];
			$product['isBought'] = 0;
			$product['createdBy'] = $username;
			$product['lastModifiedBy'] = $username;

			if(isset($product['id']) && ctype_digit((string) $product['id'])) {
				$productId = $product['id'];
				unset($product['id']);
				$this->removeLinkedProducts($productId);
				$this->addLinkedProducts($listId, $productId);
				$this->db->update('products', $product, ['id' => $productId]);
			} else {
				$product['createdAt'] = $currentDateTime->format('Y-m-d H:i:s');
				$product['finalValue'] = 0;
				$this->db->insert('products', $product);
				$productId = $this->db->lastInsertId();
				$this->addLinkedProducts($listId, $productId);
			}
			$this->db->commit();
		} catch (DBALException $e) {
			$this->db->rollBack();
			throw $e;
		}

	}

	public function delete($product) {
		$this->removeLinkedProducts($product['id']);
		$this->db->delete('products', ['id' => $product['id']]);
	}


	public function buy($product, $user) {
		$this->db->beginTransaction();

		$previousState = $this->findOneById($product['id']);
		$finalQuantity = $previousState['quantity'];
		$previousQuantity = $previousState['currentQuantity'];
		$previousValue = $previousState['finalValue'];
		$previousMessage = $previousState['message'];


		try {
			$currentDateTime = new \DateTime();
			$newProduct['modifiedAt'] = $currentDateTime->format('Y-m-d H:i:s');
			$newProduct['currentQuantity'] = $product['quantity'] + $previousQuantity;
			$newProduct['finalValue'] = $previousValue + ($product['value']*$product['quantity']);

			$newProduct['quantity'] = $finalQuantity;
			$newProduct['message'] = $previousMessage;

			if($finalQuantity  - $product['currentQuantity'] <= 0) {
				$newProduct['isBought'] = 1;
			}

			$newProduct['lastModifiedBy'] = $user;

			if(isset($product['id']) && ctype_digit((string) $product['id'])) {
				$productId = $product['id'];
				unset($product['id']);

				$this->db->update('products', $newProduct, ['id' => $productId]);


				$productAction = [];
				$productAction['product_id'] = $productId;
				$productAction['modifiedBy'] = $user;
				$productAction['quantity'] = $product['quantity'];
				$productAction['price'] = $product['value'];
				$productAction['message'] =  $product['message'];

				$this->db->insert('products_actions', $productAction);


			} else {
				$product['createdAt'] = $currentDateTime->format('Y-m-d H:i:s');
				$this->db->insert('products', $product);
			}
			$this->db->commit();
		} catch (DBALException $e) {
			$this->db->rollBack();
			throw $e;
		}
	}


	protected function removeLinkedProducts($productId) {
		$this->db->beginTransaction();

		try {

			$this->db->delete('products_actions', ['id_product' => $productId]);
			$this->db->delete('products_lists', ['product_id' => $productId]);

		} catch (DBALException $exception) {
			throw $exception;
		}

	}

	protected function addLinkedProducts($listId, $productsIds) {
		if(!is_array($productsIds)) {
			$productsIds = [$productsIds];
		}
		foreach ($productsIds as $productId) {
			$this->db->insert(
				'products_lists',
				[
					'product_id' => $productId,
					'list_id' => $listId,
				]
			);
		}
	}

	protected function queryAll() {
		$queryBuilder = $this->db->createQueryBuilder();

		return $queryBuilder->select('p.id', 'p.name', 'p.value', 'p.quantity', 'p.isBought', 'p.createdBy', 'p.currentQuantity', 'p.lastModifiedBy', 'p.message', 'p.modifiedAt', 'p.finalValue','p.createdAt' )
		                    ->from('products', 'p');
	}
}