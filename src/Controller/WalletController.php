<?php
/**
 * Created by PhpStorm.
 * User: agalempaszek
 * Date: 14.06.2018
 * Time: 21:08
 */

namespace Controller;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Repository\UserRepository;
use Repository\ProductsRepository;

class WalletController implements ControllerProviderInterface {

	public function connect( Application $app ) {
		$controller = $app['controllers_factory'];

		$controller->get('/', [$this, 'walletAction'])->bind('user_wallet');

		return $controller;

	}


	public function walletAction(Application $app) {

		$username = $this->getUsername($app);
		$userId = $this->getUserId($app, $username);

		$productsRepository = new  ProductsRepository($app['db']);

		$productsIds = $productsRepository->findUserProductsIds($userId);
		$productsForUser = $productsRepository->getBoughtUser($productsIds);

		$boughtProductsIds = $productsRepository->findBoughtByUserProductsIds($userId);
		$productsByUser = $productsRepository->getBoughtUser($boughtProductsIds);


		return $app['twig']->render(
			'wallet/index.html.twig',
			[
				'forUser' => $productsForUser,
				'byUser' => $productsByUser,
			]
		);

	}


	private function getUsername(Application $app) {

		$token = $app['security.token_storage']->getToken();

		if(null !== $token) {
			$user = $token->getUsername();
		}

		return $user;
	}

	private function getUserId(Application $app, $username) {

		$userRepository = new UserRepository($app['db']);

		$userId = $userRepository->getUserByLogin($username);

		return $userId['id'];

	}

}