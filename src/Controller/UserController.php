<?php
/**
 * Created by PhpStorm.
 * User: agalempaszek
 * Date: 18.06.2018
 * Time: 15:56
 */

namespace Controller;

use Form\LoginType;
use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\Request;
use Repository\UserRepository;
use Repository\ListsRepository;

class UserController implements ControllerProviderInterface {

	public function connect( Application $app ) {
		$controller = $app['controllers_factory'];

		$controller->match('/edit', [$this, 'editAction'])
					->method('GET|POST')
		           ->bind('user_edit_self');

		$controller->match('/delete', [$this, 'deleteAction'])
					->method('GET|POST')
					->bind('user_delete_self');

		return $controller;
	}


	public function editAction(Application $app, Request $request) {

		$listsRepository = new ListsRepository($app['db']);
		$userRepository = new UserRepository($app['db']);
		$username = $this->getUsername($app);
		$userId = $this->getUserId($app, $username);

		$form = $app['form.factory']->createBuilder(LoginType::class)->getForm();
		$form->handleRequest($request);

		if($form->isSubmitted() && $form->isValid()){

			$data = $form->getData();
			$newUser['login'] = $data['login'];
			$newUser['password'] = $app['security.encoder.bcrypt']->encodePassword($data['password'], '');
			$newUser['role_id'] = "2";

			$userRepository->updateUserData($userId, $newUser);

			$app['session']->getFlashBag()->add(
				'messages',
				[
					'type' => 'success',
					'message' => 'message.user_updated',
				]
			);

			return $app->redirect($app['url_generator'])->generate('user_edit_self');
		}

		return $app['twig']->render(
			'user/manager.html.twig',
			[
				'editedUserName' => $username,
				'form' => $form->createView(),
				'lists' => $listsRepository->findAll($userId),
				'linkedLists' => $listsRepository->findLinkedLists($userId),
			]
		);

	}


	public function deleteAction(Application $app, Request $request) {

		$userRepository = new UserRepository($app['db']);
		$listsRepository = new ListsRepository($app['db']);

		$username = $this->getUsername($app);
		$userId = $this->getUserId($app, $username);

		$user = $userRepository->findUserById($userId);

		$form = $app['form.factory']->createBuilder(FormType::class, $user)->add('id', HiddenType::class)->getForm();
		$form->handleRequest($request);

		if($form->isSubmitted() && $form->isValid()) {

			$userRepository->deleteConnectedProducts($userId);
			$userRepository->deleteConnectedLists($userId);
			$userRepository->delete($userId);



			$app['session']->getFlashBag()->add(
				'messages',
				[
					'type' => 'success',
					'message' => 'message.user_successfully_deleted',
				]
			);

			return $app->redirect(
				$app['url_generator']->generate('auth_logout'),
				301
			);

		}

		return $app['twig']->render(
			'user/delete.html.twig',
			[
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