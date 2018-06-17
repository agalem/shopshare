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
use Repository\ListsRepository;
use Repository\ProductsRepository;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\Request;

class WalletController implements ControllerProviderInterface {

	public function connect( Application $app ) {
		$controller = $app['controllers_factory'];

		$controller->get('/', [$this, 'walletAction'])->bind('user_wallet');
		$controller->match('/{id}/delete', [$this, 'deleteAction'])
					->method('GET|POST')
					->assert('id', '[1-9]\d*')
					->bind('payment_delete');


		return $controller;

	}


	public function walletAction(Application $app) {

		$listsRepository = new ListsRepository($app['db']);

		$username = $this->getUsername($app);
		$userId = $this->getUserId($app, $username);

		$productsRepository = new  ProductsRepository($app['db']);

		$productsIds = $productsRepository->findUserProductsIds($userId);
		$productsByUser = $productsRepository->getBoughtByUser($productsIds, $userId);

		$boughtProductsIds = $productsRepository->findBoughtForUserProductsIds($userId);
		$productsForUser = $productsRepository->getBoughtForUser($boughtProductsIds, $userId);

		$productsByUserForUser = $productsRepository->getBoughtByUserForUser($productsIds, $userId);


		return $app['twig']->render(
			'wallet/index.html.twig',
			[
				'byUser' => $productsByUser,
				'forUser' => $productsForUser,
				'byUserForUser' => $productsByUserForUser,
				'lists' => $listsRepository->findAll($userId),
				'linkedLists' => $listsRepository->findLinkedLists($userId),
			]
		);

	}


	public function deleteAction(Application $app, $id, Request $request) {

		$productsRepository = new ProductsRepository($app['db']);
		$listsRepository = new ListsRepository($app['db']);

		$username = $this->getUsername($app);
		$userId = $this->getUserId($app, $username);

		$isUserAction = $productsRepository->isUserAction($id, $userId);
		$payment = $productsRepository->findPaymentById($id);

		if(!$payment || $isUserAction == false) {
			$app['session']->getFlashBag()->add(
				'messages',
				[
					'type' => 'warning',
					'message' => 'message.record.not_found',
				]
			);

			return $app->redirect($app['url_generator']->generate('user_wallet'));
		}


		$form = $app['form.factory']->createBuilder(FormType::class, $payment)->add('id', HiddenType::class)->getForm();
		$form->handleRequest($request);


		if($form->isSubmitted() && $form->isValid()) {

			$productsRepository->deletePayment($id);

			$app['session']->getFlashBag()->add(
				'messages',
				[
					'type' => 'success',
					'message' => 'message.element_successfully_deleted',
				]
			);

			return $app->redirect(
				$app['url_generator']->generate('user_wallet'),
				301
			);

		}


		return $app['twig']->render(
			'wallet/delete.html.twig' ,
			[
				'deletedPayment' => $payment,
				'form' => $form->createView(),
				'lists' => $listsRepository->findAll($userId),
				'linkedLists' => $listsRepository->findLinkedLists($userId),
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