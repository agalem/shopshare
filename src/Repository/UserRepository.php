<?php
/**
 * Created by PhpStorm.
 * User: agalempaszek
 * Date: 01.06.2018
 * Time: 13:37
 */

namespace Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;


class UserRepository {

	protected $db;

	public function __construct(Connection $db) {
		$this->db = $db;
	}

	public function loadUserByLogin($login) {
		try {
			$user = $this->getUserByLogin($login);

			if(!$user || !count($user)) {
				throw new UsernameNotFoundException(
					sprintf('Username "%s" does not exist.', $login)
				);
			}

			$roles = $this->getUserRoles($user['id']);

			if(!$roles || !count($roles)) {
				throw new UsernameNotFoundException(
					sprintf('Username "%s" does not exist.', $login)
				);
			}

			return [
				'login' => $user['login'],
				'password' => $user['password'],
				'roles' => $roles,
			];
		} catch (DBALException $exception) {
			throw new UsernameNotFoundException(
				sprintf('Username "%s" does not exist.', $login)
			);
		} catch (UsernameNotFoundException $exception) {
			throw $exception;
		}
	}

	public function getUserByLogin($login)
	{
		try {
			$queryBuilder = $this->db->createQueryBuilder();
			$queryBuilder->select('u.id', 'u.login', 'u.password')
			             ->from('users', 'u')
			             ->where('u.login = :login')
			             ->setParameter(':login', $login, \PDO::PARAM_STR);

			return $queryBuilder->execute()->fetch();
		} catch (DBALException $exception) {
			return [];
		}
	}

	public function checkIfExists($login) {
		try {
			$queryBuilder = $this->db->createQueryBuilder();
			$queryBuilder->select('u.id', 'u.login')
				->from('users', 'u')
				->where('u.login = :login')
				->setParameter(':login', $login, \PDO::PARAM_STR);
			return $queryBuilder->execute()->fetch();
		} catch (DBALException $exception) {
			return [];
		}
	}

	public function getUserRoles($userId)
	{
		$roles = [];

		try {
			$queryBuilder = $this->db->createQueryBuilder();
			$queryBuilder->select('r.name')
			             ->from('users', 'u')
			             ->innerJoin('u', 'users_roles', 'r', 'u.role_id = r.id')
			             ->where('u.id = :id')
			             ->setParameter(':id', $userId, \PDO::PARAM_INT);
			$result = $queryBuilder->execute()->fetchAll();

			if ($result) {
				$roles = array_column($result, 'name');
			}

			return $roles;
		} catch (DBALException $exception) {
			return $roles;
		}
	}

	public function save($user) {

		try {
			if(isset($user['id']) && ctype_digit((string) $user['id'])) {
				$id = $user['id'];
				unset($user['id']);

				$this->db->update('users', $user, ['id' => $id]);

			} else {

				$this->db->insert('users', $user);
			}
		} catch (DBALException $exception) {
			return [];
		}


	}

}