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
use Symfony\Component\Security\Core\User\User;

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

		$controller->match('/{id}/deleteUser', [$this, 'deleteUserAction'])
		           ->method('GET|POST')
		           ->assert('id', '[1-9]\d*')
		           ->bind('list_delete_user');

		return $controller;
	}



	public function indexAction(Application $app) {
		$listsRepository = new ListsRepository($app['db']);
		$userRepository = new UserRepository($app['db']);

		$user = $this->getUsername($app);
		$userId = $this->getUserId($app, $user);

		$userRole = $userRepository->getUserRoles($userId)[0];


		if($userRole == 'ROLE_ADMIN') {
			return $app->redirect($app['url_generator']->generate('admin_manager'));
		}


		return $app['twig']->render(
			'lists/index.html.twig',
			[
				'lists' => $listsRepository->findAll($userId),
				'name' => $user,
				'linkedLists' => $listsRepository->findLinkedLists($userId)
			]
		);
	}

	public function viewAction(Application $app, $id) {
		$listsRepository = new ListsRepository($app['db']);
		$list = $listsRepository->findOneById($id);

		$username = $this->getUsername($app);
		$userId = $this->getUserId($app, $username);

		$isLinked = false;

		foreach ($listsRepository->findLinkedLists($userId) as $linkedList) {
			if($linkedList['id'] == $id) {
				$isLinked = true;
			}
		}

		if(!$list or ($list['createdBy'] != $userId and $isLinked == false)) {
			$app['session']->getFlashBag()->add(
				'messages',
				[
					'type' => 'warning',
					'message' => 'message.record_not_found',
				]
			);

			return $app->redirect($app['url_generator']->generate('lists_index'));

		}

		$activeList = $listsRepository->findOneById($id);
		$plannedSpendings = $activeList['maxCost'];



		return $app['twig']->render(
			'lists/view.html.twig',
			[

				'lists' => $listsRepository->findAll($userId),
				'activeList' => $listsRepository->findOneById($id),
				'userProducts' => $listsRepository->findUserProducts($id, $userId),//TODO
				'otherProducts' => $listsRepository->findOtherProducts($id, $userId),
				'plannedSpendings' => $plannedSpendings,
				'linkedLists' => $listsRepository->findLinkedLists($userId),
				'isLinked' => $isLinked,
				'listOwner' => $listsRepository->getListOwner($id),
				'sharedUsers' => $listsRepository->getSharedUsers($id, $username),
			]
		);
	}

	public function managerAction(Application $app) {
		$listsRepository = new ListsRepository($app['db']);

		$username = $this->getUsername($app);
		$userId = $this->getUserId($app, $username);



		return $app['twig']->render(
			'lists/manager.html.twig',
			[
				'lists' => $listsRepository->findAll($userId),
				'linkedLists' => $listsRepository->findLinkedLists($userId)
			]
		);
	}

	public function addAction(Application $app, Request $request) {
		$listsRepository = new ListsRepository($app['db']);
		$userRepository = new UserRepository($app['db']);

		$list = [];

		$form = $app['form.factory']->createBuilder(ListType::class, $list)->getForm();
		$form->handleRequest($request);

		$username = $this->getUsername($app);
		$userId = $this->getUserId($app, $username);

		$userRole = $userRepository->getUserRoles($userId)[0];



		if($userRole == 'ROLE_ADMIN') {
			return $app->redirect($app['url_generator']->generate('admin_manager'));
		}

		if($form->isSubmitted() && $form->isValid()) {
			$newList = $form->getData();

			$ifExists = $listsRepository->checkIfNameExists($newList['name'], $userId);

			if( $ifExists != []) {
				$app['session']->getFlashBag()->add(
					'messages',
					[
						'type' => 'danger',
						'message' => 'message.list_exist',
					]
				);

				return $app->redirect($app['url_generator']->generate('list_add'), 301);
			}

			$newList['createdBy'] = $userId;

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
				'lists' => $listsRepository->findAll($userId),
				'linkedLists' => $listsRepository->findLinkedLists($userId),
			]
		);
	}

	public function editAction(Application $app, $id, Request $request) {
		$listsRepository = new ListsRepository($app['db']);
		$list = $listsRepository->findOneById($id);

		$username = $this->getUsername($app);
		$userId = $this->getUserId($app, $username);


		$sharedUsers = $listsRepository->getSharedUsers($id, $username);

		$isLinked = false;


		foreach ($listsRepository->findLinkedLists($userId) as $linkedList) {
			if($linkedList['id'] == $id) {
				$isLinked = true;
			}
		}

		if(!$list or ($list['createdBy'] != $userId and $isLinked == false)) {
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
			$newList['lastModifiedBy'] = $userId;
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
				'lists' => $listsRepository->findAll($userId),
				'userProducts' => $listsRepository->findUserProducts($id, $userId),
				'linkedLists' => $listsRepository->findLinkedLists($userId),
				'isOwner' => ($list['createdBy'] == $userId) ? true : false,
				'isShared' => (count($sharedUsers) == 0) ? false : true,
				'sharedUsers' => $sharedUsers,
			]
		);
	}


	public function deleteAction(Application $app, $id, Request $request) {
		$listsRepository = new ListsRepository($app['db']);
		$list = $listsRepository->findOneById($id);

		$username = $this->getUsername($app);
		$userId = $this->getUserId($app, $username);

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

		foreach ($listsRepository->findLinkedLists($userId) as $linkedList) {
			if($linkedList['id'] == $id) {
				$isLinked = true;
			}
		}

		if($list['createdBy'] != $userId and $isLinked == false){
			$app['session']->getFlashBag()->add(
				'messages',
				[
					'type' => 'danger',
					'message' => 'message.not_owner',
				]
			);

			return $app->redirect($app['url_generator']->generate('lists_manager'));

		} else if ($list['createdBy'] != $userId and $isLinked == true) {

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
				'lists' => $listsRepository->findAll($userId),
				'linkedLists' => $listsRepository->findLinkedLists($userId),
			]
		);
	}


	public function shareAction(Application $app, $id, Request $request) {
		$userRepository = new UserRepository($app['db']);
		$listsRepository = new ListsRepository($app['db']);
		$list = $listsRepository->findOneById($id);

		$username = [];

		$user = $this->getUsername($app);
		$userId = $this->getUserId($app, $user);



		if(!$list || $list['createdBy'] != $userId) {
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


			$data = $form->getData();

			$userId = $this->getUserId($app, $data['user_login']);

			$userRole = $listsRepository->checkIfAdmin($data['user_login']);
			$userRole = $userRole['name'];

			$isOnList = $listsRepository->checkIfOnList($id, $data['user_login']);
			if(is_array($isOnList)) {
				$isOnList = count($isOnList) > 0 ? true : false;
			}

			$ifExists = $userRepository->checkIfExists($data['user_login']);

			if($ifExists == []) {

				$app['session']->getFlashBag()->add(
					'messages',
					[
						'type' => 'warning',
						'message' => 'message.user_not_found',
					]
				);

				return $app->redirect($app['url_generator']->generate('list_share', array('id' => $id)), 301);

			}

			if($user == $data['user_login']) {

				$app['session']->getFlashBag()->add(
					'messages',
					[
						'type' => 'warning',
						'message' => 'message.cannot_share_yourself',
					]
				);

				return $app->redirect($app['url_generator']->generate('list_share', array('id' => $id)), 301);

			}

			if($userRole == 'ROLE_ADMIN') {

				$app['session']->getFlashBag()->add(
					'messages',
					[
						'type' => 'warning',
						'message' => 'message.cannot_add_admin',
					]
				);

				return $app->redirect($app['url_generator']->generate('list_share', array('id' => $id)), 301);

			}

			if ($isOnList == true) {

				$app['session']->getFlashBag()->add(
					'messages',
					[
						'type' => 'warning',
						'message' => 'message.already_on_list',
					]
				);

				return $app->redirect($app['url_generator']->generate('list_share', array('id' => $id)), 301);

			}


			$listsRepository->addUser($id, $userId);

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
				'lists' => $listsRepository->findAll($userId),
				'linkedLists' => $listsRepository->findLinkedLists($userId),
				'form' => $form->createView(),
				'editedList' => $list,
				'sharedUsers' => $listsRepository->getSharedUsers($id, $user),
			]
		);
	}


	public function deleteUserAction(Application $app, $id, Request $request) {

		$listsRepository = new ListsRepository($app['db']);
		$list = $listsRepository->findOneById($id);

		$username = [];

		$user = $this->getUsername($app);
		$userId = $this->getUserId($app, $user);



		if(!$list || $list['createdBy'] != $userId) {
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


			$data = $form->getData();

			$userId = $this->getUserId($app, $data['user_login']);

			$userRole = $listsRepository->checkIfAdmin($data['user_login']);

			$isOnList = $listsRepository->checkIfOnList($id, $data['user_login']);
			if(is_array($isOnList)) {
				$isOnList = count($isOnList) > 0 ? true : false;
			}


			if ($isOnList == false) {

				$app['session']->getFlashBag()->add(
					'messages',
					[
						'type' => 'warning',
						'message' => 'message.user_not_found',
					]
				);

				return $app->redirect($app['url_generator']->generate('list_delete_user', array('id' => $id)), 301);

			}


			$listsRepository->removeUser($id, $userId);

			$app['session']->getFlashBag()->add(
				'messages',
				[
					'type' => 'success',
					'message' => 'message.user_successfully_added',
				]
			);

			return $app->redirect($app['url_generator']->generate('list_delete_user', array('id' => $id)), 301);
		}

		return $app['twig']->render(
			'lists/deleteUser.html.twig',
			[
				'lists' => $listsRepository->findAll($userId),
				'linkedLists' => $listsRepository->findLinkedLists($userId),
				'form' => $form->createView(),
				'editedList' => $list,
				'sharedUsers' => $listsRepository->getSharedUsers($id, $user),
			]
		);
	}


	public function addProductAction(Application $app, $id, Request $request) {
		$productsRepository = new ProductsRepository($app['db']);
		$listsRepository = new ListsRepository($app['db']);

		$list = $listsRepository->findOneById($id);
		$product = [];

		$username = $this->getUsername($app);
		$userId = $this->getUserId($app, $username);

		$isLinked = false;

		foreach ($listsRepository->findLinkedLists($userId) as $linkedList) {
			if($linkedList['id'] == $id) {
				$isLinked = true;
			}
		}

		if(!$list or ($list['createdBy'] != $userId and $isLinked == false)) {
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
			$productsRepository->save($id, $form->getData(), $userId);

			$app['session']->getFlashBag()->add(
				'messages',
				[
					'type' => 'success',
					'message' => 'message.element_successfully_added',
				]
			);

			return $app->redirect($app['url_generator']->generate('lists_view', array('id' => $id)), 301);
		}

		return $app['twig']->render(
			'products/add.html.twig',
			[
				'newProduct' => $product,
				'form' => $form->createView(),
				'lists' => $listsRepository->findAll($userId),
				'linkedLists' => $listsRepository->findLinkedLists($userId),
				'editedList' => $listsRepository->findOneById($id),
				'displayIsBought'=> false,
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