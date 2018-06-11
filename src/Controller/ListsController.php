<?php

namespace Controller;

use Form\ListConnectionType;
use Form\ProductType;
use Form\ListType;
use Repository\ProductsRepository;
use Repository\ListsRepository;
use Repository\UserRepository;
use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

class ListsController implements ControllerProviderInterface {

	public function connect( Application $app ) {
		$controller = $app['controllers_factory'];

		$controller->get('/', [$this, 'indexAction'])->bind('lists_index');

		$controller->get('/{id}', [$this, 'viewAction'])
		           ->assert('id', '[1-9]\d*' )
		           ->bind('lists_view');

		$controller->get('/manager', [$this, 'managerAction'])->bind('lists_manager');

		$controller->match('/add', [$this, 'addAction'])
		           ->method('POST|GET')
		           ->bind('list_add');

		$controller->match('{id}/add', [$this, 'addProductAction'])
		           ->method('POST|GET')
		           ->assert('id', '[1-9]\d*' )
		           ->bind('product_add');

		$controller->match('/{id}/edit', [$this, 'editAction'])
		           ->method('GET|POST')
		           ->assert('id', '[1-9]\d*' )
		           ->bind('list_edit');

		$controller->match('/{id}/delete', [$this, 'deleteAction'])
		           ->method('GET|POST')
		           ->assert('id', '[1-9]\d*' )
		           ->bind('list_delete');

		$controller->match('/{id}/share', [$this, 'shareAction'])
		           ->method('GET|POST')
		           ->assert('id', '[1-9]\d*')
		           ->bind('list_share');

		return $controller;
	}

	public function getUser(Application $app) {
		$token = $app['security.token_storage']->getToken();

		if(null !== $token) {
			$user = $token->getUsername();
		}

		return $user;
	}

	public function indexAction(Application $app) {
		$listsRepository = new ListsRepository($app['db']);

		$user = $this->getUser($app);



		return $app['twig']->render(
			'lists/index.html.twig',
			[
				'lists' => $listsRepository->findAll($user),
				'name' => $user,
				'linkedLists' => $listsRepository->findLinkedLists($user)
			]
		);
	}

	public function viewAction(Application $app, $id) {
		$listsRepository = new ListsRepository($app['db']);
		$list = $listsRepository->findOneById($id);

		$user = $this->getUser($app);

		$isLinked = false;

		foreach ($listsRepository->findLinkedLists($user) as $linkedList) {
			if($linkedList['id'] == $id) {
				$isLinked = true;
			}
		}

		if(!$list or ($list['createdBy'] != $user and $isLinked == false)) {
			$app['session']->getFlashBag()->add(
				'messages',
				[
					'type' => 'warning',
					'message' => 'message.record_not_found',
				]
			);

			return $app->redirect($app['url_generator']->generate('lists_index'));

		}

		$currentSpendigs = $listsRepository->getCurrentSpendings($id);
		$activeList = $listsRepository->findOneById($id);
		$plannedSpendings = $activeList['maxCost'];

		if($plannedSpendings == null) {
			$spendPercent = 0;
		} else {
			$spendPercent = round($currentSpendigs / $plannedSpendings * 100);
		}

		if($spendPercent < 50) {
			$progressBarClass = 'bg-success text-light';
		} else if ($spendPercent >= 50 && $spendPercent < 80) {
			$progressBarClass = 'bg-warning text-dark';
		} else {
			$progressBarClass = 'bg-danger text-light';
		}

		$user = $this->getUser($app);


		return $app['twig']->render(
			'lists/view.html.twig',
			[
				'currentSpendings' => $listsRepository->getCurrentSpendings($id),
				'lists' => $listsRepository->findAll($user),
				'activeList' => $listsRepository->findOneById($id),
				'userProducts' => $listsRepository->findUserProducts($id, $user),
				'otherProducts' => $listsRepository->findOtherProducts($id, $user),
				'plannedSpendings' => $plannedSpendings,
				'spendPercent' => $spendPercent,
				'progressBarClass' => $progressBarClass,
				'linkedLists' => $listsRepository->findLinkedLists($user),
			]
		);
	}

	public function managerAction(Application $app) {
		$listsRepository = new ListsRepository($app['db']);

		$user = $this->getUser($app);

		return $app['twig']->render(
			'lists/manager.html.twig',
			[
				'lists' => $listsRepository->findAll($user),
				'linkedLists' => $listsRepository->findLinkedLists($user)
			]
		);
	}

	public function addAction(Application $app, Request $request) {
		$listsRepository = new ListsRepository($app['db']);

		$list = [];

		$form = $app['form.factory']->createBuilder(ListType::class, $list)->getForm();
		$form->handleRequest($request);

		$user = $this->getUser($app);

		if($form->isSubmitted() && $form->isValid()) {
			$newList = $form->getData();
			$newList['createdBy'] = $user;

			$listsRepository->save($newList);

			$app['session']->getFlashBag()->add(
				'messages',
				[
					'type' => 'success',
					'message' => 'message.element_successfully_added',
				]
			);

			return $app->redirect($app['url_generator']->generate('lists_manager'), 301);
		}


		return $app['twig']->render(
			'lists/add.html.twig',
			[
				'newList' => $list,
				'form' => $form->createView(),
				'lists' => $listsRepository->findAll($user),
			]
		);
	}

	public function editAction(Application $app, $id, Request $request) {
		$listsRepository = new ListsRepository($app['db']);
		$list = $listsRepository->findOneById($id);

		$user = $this->getUser($app);

		$isLinked = false;

		foreach ($listsRepository->findLinkedLists($user) as $linkedList) {
			if($linkedList['id'] == $id) {
				$isLinked = true;
			}
		}

		if(!$list or ($list['createdBy'] != $user and $isLinked == false)) {
			$app['session']->getFlashBag()->add(
				'messages',
				[
					'type' => 'warning',
					'message' => 'message.record_not_found',
				]
			);
			return $app->redirect($app['url_generator']->generate('lists_index'));
		}
		$form = $app['form.factory']->createBuilder(ListType::class, $list)->getForm();
		$form->handleRequest($request);
		if($form->isSubmitted() && $form->isValid()){
			$newList = $form->getData();
			$newList['lastModifiedBy'] = $user;
			$listsRepository->save($newList);
			$app['session']->getFlashBag()->add(
				'messages',
				[
					'type' => 'success',
					'message' => 'message.element_successfully_edited',
				]
			);
			return $app->redirect($app['url_generator']->generate('lists_view', array('id' => $id)), 301);
		}
		return $app['twig']->render(
			'lists/edit.html.twig',
			[
				'editedList' => $list,
				'form' => $form->createView(),
				'lists' => $listsRepository->findAll($user),
				'userProducts' => $listsRepository->findUserProducts($id, $user),
				'linkedLists' => $listsRepository->findLinkedLists($user),
				'isOwner' => ($list['createdBy'] == $user) ? true : false,
			]
		);
	}


	public function deleteAction(Application $app, $id, Request $request) {
		$listsRepository = new ListsRepository($app['db']);
		$list = $listsRepository->findOneById($id);

		$user = $this->getUser($app);

		if(!$list) {
			$app['session']->getFlashBag()->add(
				'messages',
				[
					'type' => 'warning',
					'message' => 'message.record_not_found',
				]
			);

			return $app->redirect($app['url_generator']->generate('lists_manager'));
		}

		$isLinked = false;

		foreach ($listsRepository->findLinkedLists($user) as $linkedList) {
			if($linkedList['id'] == $id) {
				$isLinked = true;
			}
		}

		if($list['createdBy'] != $user and $isLinked == false){
			$app['session']->getFlashBag()->add(
				'messages',
				[
					'type' => 'danger',
					'message' => 'message.not_owner',
				]
			);

			return $app->redirect($app['url_generator']->generate('lists_manager'));

		} else if ($list['createdBy'] != $user and $isLinked == true) {

			$listsRepository->deleteConnection($id);

			$app['session']->getFlashBag()->add(
				'messages',
				[
					'type' => 'success',
					'message' => 'message.connection_deleted',
				]
			);

			return $app->redirect($app['url_generator']->generate('lists_manager'));
		}


		$form = $app['form.factory']->createBuilder(FormType::class, $list)->add('id', HiddenType::class)->getForm();
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {

			$listsRepository->deleteConnection($id);
			$listsRepository->delete($form->getData());

			$app['session']->getFlashBag()->add(
				'messages',
				[
					'type' => 'success',
					'message' => 'message.element_successfully_deleted',
				]
			);

			return $app->redirect(
				$app['url_generator']->generate('lists_manager'),
				301
			);
		}

		return $app['twig']->render(
			'lists/delete.html.twig',
			[
				'deletedList' => $list,
				'form' => $form->createView(),
				'lists' => $listsRepository->findAll($user),
			]
		);
	}


	public function shareAction(Application $app, $id, Request $request) {
		$userRepository = new UserRepository($app['db']);
		$listsRepository = new ListsRepository($app['db']);
		$list = $listsRepository->findOneById($id);

		$username = [];

		$user = $this->getUser($app);

		if(!$list || $list['createdBy'] != $user) {
			$app['session']->getFlashBag()->add(
				'messages',
				[
					'type' => 'warning',
					'message' => 'message.record_not_found',
				]
			);
			return $app->redirect($app['url_generator']->generate('lists_index'));
		}

		$form = $app['form.factory']->createBuilder(ListConnectionType::class, $username)->getForm();
		$form->handleRequest($request);

		if($form->isSubmitted() && $form->isValid()) {

			$listsRepository->addUser($id, $form->getData());

			$app['session']->getFlashBag()->add(
				'messages',
				[
					'type' => 'success',
					'message' => 'message.user_successfully_added',
				]
			);

			return $app->redirect($app['url_generator']->generate('list_share', array('id' => $id)), 301);
		}

		return $app['twig']->render(
			'lists/share.html.twig',
			[
				'lists' => $listsRepository->findAll($user),
				'form' => $form->createView(),
				'editedList' => $list,
			]
		);
	}


	public function addProductAction(Application $app, $id, Request $request) {
		$productsRepository = new ProductsRepository($app['db']);
		$listsRepository = new ListsRepository($app['db']);

		$list = $listsRepository->findOneById($id);
		$product = [];

		$user = $this->getUser($app);

		$isLinked = false;

		foreach ($listsRepository->findLinkedLists($user) as $linkedList) {
			if($linkedList['id'] == $id) {
				$isLinked = true;
			}
		}

		if(!$list or ($list['createdBy'] != $user and $isLinked == false)) {
			$app['session']->getFlashBag()->add(
				'messages',
				[
					'type' => 'warning',
					'message' => 'message.record_not_found',
				]
			);

			return $app->redirect($app['url_generator']->generate('lists_manager'));
		}

		$form = $app['form.factory']->createBuilder(ProductType::class, $product)->getForm();
		$form->handleRequest($request);

		if($form->isSubmitted() && $form->isValid()) {

			$listsRepository->updateModiefiedDate($id);
			$productsRepository->save($id, $form->getData(), $user);

			$app['session']->getFlashBag()->add(
				'messages',
				[
					'type' => 'success',
					'message' => 'message.element_successfully_added',
				]
			);

			return $app->redirect($app['url_generator']->generate('list_edit', array('id' => $id)), 301);
		}

		return $app['twig']->render(
			'products/add.html.twig',
			[
				'newProduct' => $product,
				'form' => $form->createView(),
				'lists' => $listsRepository->findAll($user),
				'editedList' => $listsRepository->findOneById($id),
				'displayIsBought'=> false,
			]
		);
	}
}