<?php
/**
 * Created by PhpStorm.
 * User: agalempaszek
 * Date: 01.06.2018
 * Time: 13:50
 */

namespace Provider;

use Doctrine\DBAL\Connection;
use Repository\UserRepository;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;

class UserProvider implements UserProviderInterface {

	protected $db;

	public function __construct(Connection $db) {
		$this->db = $db;
	}

	public function loadUserByUsername( $login ) {
		$userRepository = new UserRepository($this->db);
		$user = $userRepository->loadUserByLogin($login);

		return new User(
			$user['login'],
			$user['password'],
			$user['roles'],
			true,
			true,
			true,
			true
		);
	}

	public function refreshUser( UserInterface $user ) {
		if(!$user instanceof User) {
			throw new UnsupportedUserException(
				sprintf(
					'Instances of "%s" are not supported.',
					get_class($user)
				)
			);
		}

		return $this->loadUserByUsername($user->getUsername());
	}

	public function supportsClass( $class ) {
		return $class === 'Symfony\Component\Security\Core\User\User';
	}

}