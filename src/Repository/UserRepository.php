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

	public function getUserByLogin($login) {
		try {
			$queryBuilder = $this->db->createQueryBuilder();
			$queryBuilder->select('u.id', 'u.login', 'ui.password', 'ui.role_id')
				->from('users', 'u')
				->innerJoin('u', 'users_info', 'ui', 'u.id = ui.id_user')
				->where('u.login = :login')
				->setParameter(':login', $login, \PDO::PARAM_STR);
			return $queryBuilder->execute()->fetch();
		} catch (DBALException $exception) {
			return [];
		}
	}

	public function getUserRoles($userId) {
		$roles = [];

		try {
			$queryBuilder = $this->db->createQueryBuilder();
			$queryBuilder->select('r.name')
			             ->from('users_info', 'ui')
			             ->innerJoin('ui', 'users_roles', 'r', 'ui.role_id = r.id')
			             ->where('ui.id_user = :id')
			             ->setParameter(':id', $userId, \PDO::PARAM_INT);
			$result = $queryBuilder->execute()->fetchAll();

			if($result) {
				$roles = array_column($result, 'name');
			}
			return $roles;
		} catch (DBALException $exception) {
			return $roles;
		}
	}

}