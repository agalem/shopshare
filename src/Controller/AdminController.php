<?php
/**
 * Created by PhpStorm.
 * User: agalempaszek
 * Date: 13.06.2018
 * Time: 22:52
 */

namespace Controller;

use Form\ChangePasswordType;
use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Repository\UserRepository;


class AdminController implements  ControllerProviderInterface {

	public function connect( Application $app ) {
		$controller = $app['controllers_factory'];

		$controller->get('/', [$this, 'managerAction'])
		           ->bind('admin_manager');

		$controller->match('/{id}/edit', [$this, 'editAction'])
		           ->method('GET|POST')
		           ->assert('id', '[1-9]\d*' )
		           ->bind('user_edit');

		return $controller;
	}

	public function managerAction(Application $app) {

		$userRepository =  new UserRepository($app['db']);


		return $app['twig']->render(
			'admin/admin.html.twig',
			['users' => $userRepository->findAllUsers()]
		);

	}

	public function editAction(Application $app, $id, Request $request) {

		$userRepository = new UserRepository($app['db']);
		$user = $userRepository->findUserById($id);

		if(!$user) {
			$app['session']->getFlashBag()->add(
				'messages',
				[
					'type' => 'warning',
					'message' => 'message.record_not_found',
				]
			);
			return $app->redirect($app['url_generator']->generate('admin_manager'));
		}


		$form = $app['form.factory']->createBuilder(ChangePasswordType::class)->getForm();
		$form->handleRequest($request);

		if($form->isSubmitted() && $form->isValid()){

			$newPassword = $form->getData();
			$newPassword['password'] = $app['security.encoder.bcrypt']->encodePassword($newPassword['password'], '');
			$userRepository->changePassword($id, $newPassword);
			$app['session']->getFlashBag()->add(
				'messages',
				[
					'type' => 'success',
					'message' => 'message.password_changed',
				]
			);

		}

		return $app['twig']->render(
			'admin/edit.html.twig',
			[
				'editedUser' => $user,
				'form' => $form->createView(),
			]
		);
	}

}