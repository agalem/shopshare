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

	public function save($listId, $product)
	{
		$this->db->beginTransaction();

		try {
			$currentDateTime = new \DateTime();
			$product['modifiedAt'] = $currentDateTime->format('Y-m-d H:i:s');
			$product['finalValue'] = $product['value']*$product['quantity'];
			$product['isBought'] = 0;
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

	public function buy($product) {
		$this->db->beginTransaction();

		try {
			$currentDateTime = new \DateTime();
			$product['modifiedAt'] = $currentDateTime->format('Y-m-d H:i:s');
			$product['isBought'] = 1;
			$product['finalValue'] = $product['value']*$product['quantity'];
			if(isset($product['id']) && ctype_digit((string) $product['id'])) {
				$productId = $product['id'];
				unset($product['id']);
				$this->db->update('products', $product, ['id' => $productId]);
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
		return $this->db->delete('products_lists', ['product_id' => $productId]);
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

		return $queryBuilder->select('p.id', 'p.name', 'p.value', 'p.quantity', 'p.isBought')
		                    ->from('products', 'p');
	}
}