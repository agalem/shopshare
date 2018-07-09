<?php

namespace Controller;

use Form\AccountType;
use Form\LoginType;
use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Repository\UserRepository;
use Symfony\Component\Security\Core\User\User;


class AuthController implements ControllerProviderInterface
{

	public function connect(Application $app)
	{
		$controller = $app['controllers_factory'];

		$controller->match('login', [$this, 'loginAction'])
		           ->method('GET|POST')
		           ->bind('auth_login');

		$controller->get('logout', [$this, 'logoutAction'])
		           ->bind('auth_logout');

		$controller->match('create', [$this, 'createAction'])
		           ->method('GET|POST')
		           ->bind('auth_create');

		return $controller;
	}


	public function loginAction(Application $app, Request $request)
	{
		$user = ['login' => $app['session']->get('_security.last_username')];
		$form = $app['form.factory']->createBuilder(LoginType::class, $user)->getForm();

		return $app['twig']->render(
			'auth/login.html.twig',
			[
				'form' => $form->createView(),
				'error' => $app['security.last_error']($request),
			]
		);
	}


	public function logoutAction(Application $app)
	{
		$app['session']->clear();

		return $app['twig']->render('auth/logout.html.twig', []);
	}


	public  function createAction(Application $app, Request $request) {
		$user = [];

		$form=$app['form.factory']->createBuilder(AccountType::class, $user)->getForm();
		$form->handleRequest($request);

		if($form->isSubmitted() && $form->isValid()) {
			$usersRepository = new UserRepository($app['db']);
			$newUser = $form->getData();

			$ifExists = $usersRepository->getUserByLogin($newUser['login']);

			if ($ifExists != []) {

				$app['session']->getFlashBag()->add(
					'messages',
					[
						'type' => 'danger',
						'message' => 'message.username_exists',
					]
				);

				return $app->redirect($app['url_generator']->generate('admin_add'), 301);

			}

			$newUser['password'] = $app['security.encoder.bcrypt']->encodePassword($newUser['password'], '');
			$usersRepository->save($newUser);

			$app['session']->getFlashBag()->add(
				'messages',
				[
					'type' => 'success',
					'message' => 'message.user_created',
				]
			);

			return $app->redirect($app['url_generator']->generate('auth_login'), 301);
		}

		return $app['twig']->render(
			'auth/create.html.twig',
			[
				'user' => $user,
				'form' => $form->createView(),
			]
		);
	}
}