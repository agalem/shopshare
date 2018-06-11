<?php
/**
 * Created by PhpStorm.
 * User: agalempaszek
 * Date: 20.05.2018
 * Time: 18:12
 */

namespace Controller;

use Form\ProductType;
use Form\ListType;
use Repository\ProductsRepository;
use Repository\ListsRepository;
use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

class ProductsController implements ControllerProviderInterface {

	public function connect( Application $app ) {
		$controller = $app['controllers_factory'];

		$controller->match('/{id}/edit', [$this, 'editAction'])
		           ->method('GET|POST')
		           ->assert('id', '[1-9]\d*' )
		           ->bind('product_edit');

		$controller->match('/{id}/buy', [$this, 'buyAction'])
		           ->method('GET|POST')
		           ->assert('id', '[1-9]\d*' )
		           ->bind('product_buy');

		$controller->match('/{id}/delete', [$this, 'deleteAction'])
		           ->method('POST|GET')
		           ->assert('id', '[1-9]\d*' )
		           ->bind('product_delete');

		return $controller;
	}

	public function getUser(Application $app) {
		$token = $app['security.token_storage']->getToken();

		if(null !== $token) {
			$user = $token->getUsername();
		}

		return $user;
	}


	public function editAction(Application $app, $id, Request $request) {

		$productsRepository = new ProductsRepository($app['db']);
		$listsRepository = new ListsRepository($app['db']);
		$product = $productsRepository->findOneById($id);
		$connectedList = $listsRepository->getConnectedList($id);
		$listId = $connectedList['list_id'];

		$user = $this->getUser($app);

		if(!$product) {
			$app['session']->getFlashBag()->add(
				'messages',
				[
					'type' => 'warning',
					'message' => 'message.record_not_found',
				]
			);

			return $app->redirect($app['url_generator']->generate('list_edit', array('id' => $listId)));
		}

		if ($product['createdBy'] != $user) {
			$app['session']->getFlashBag()->add(
				'messages',
				[
					'type' => 'danger',
					'message' => 'message.not_owner',
				]
			);

			return $app->redirect($app['url_generator']->generate('list_edit', array('id' => $listId)));
		}

		$form = $app['form.factory']->createBuilder(ProductType::class, $product)->getForm();
		$form->handleRequest($request);

		if($form->isSubmitted() && $form->isValid()){

			$productsRepository->save($listId, $form->getData(), $user);

			$app['session']->getFlashBag()->add(
				'messages',
				[
					'type' => 'success',
					'message' => 'message.element_successfully_edited',
				]
			);

			return $app->redirect($app['url_generator']->generate('list_edit', array('id' => $listId)), 301);
		}

		return $app['twig']->render(
			'products/edit.html.twig',
			[
				'editedProduct' => $product,
				'form' => $form->createView(),
				'lists' => $listsRepository->findAll($user),
			]
		);
	}

	public function buyAction(Application $app, $id, Request $request) {

		$productsRepository = new ProductsRepository($app['db']);
		$listsRepository = new ListsRepository($app['db']);
		$product = $productsRepository->findOneById($id);
		$connectedList = $listsRepository->getConnectedList($id);
		$listId = $connectedList['list_id'];
		$listOwner = $listsRepository->findOneById($listId);

		$user = $this->getUser($app);

		$isLinked = false;

		foreach ($listsRepository->findLinkedLists($user) as $linkedList) {
			if($linkedList['id'] == $listId) {
				$isLinked = true;
			}
		}

		if(!$product or ($listOwner['createdBy'] != $user and $isLinked == false)) {
			$app['session']->getFlashBag()->add(
				'messages',
				[
					'type' => 'warning',
					'message' => 'message.record_not_found',
				]
			);

			return $app->redirect($app['url_generator']->generate('lists_view', array('id' => $listId)));
		}

		$form = $app['form.factory']->createBuilder(ProductType::class, $product)->getForm();
		$form->handleRequest($request);

		if($form->isSubmitted() && $form->isValid()){
			$productsRepository->buy($form->getData(), $user);


			$app['session']->getFlashBag()->add(
				'messages',
				[
					'type' => 'success',
					'message' => 'message.element_successfully_edited',
				]
			);

			return $app->redirect($app['url_generator']->generate('lists_view', array('id' => $listId)), 301);
		}

		return $app['twig']->render(
			'products/buy.html.twig',
			[
				'editedProduct' => $product,
				'form' => $form->createView(),
				'lists' => $listsRepository->findAll($user),
				'previousList' => $listId,
				'isBuyingForm' => true,
			]
		);
	}


	public function deleteAction(Application $app, $id, Request $request) {
		$productsRepository = new ProductsRepository($app['db']);
		$product = $productsRepository->findOneById($id);
		$listsRepository = new ListsRepository($app['db']);
		$connectedList = $listsRepository->getConnectedList($id);
		$listId = $connectedList['list_id'];

		$user = $this->getUser($app);

		if(!$product) {
			$app['session']->getFlashBag()->add(
				'messages',
				[
					'type' => 'warning',
					'message' => 'message.record_not_found',
				]
			);

			return $app->redirect($app['url_generator']->generate('list_edit', array('id' => $listId)));
		}

		if ($product['createdBy'] != $user) {
			$app['session']->getFlashBag()->add(
				'messages',
				[
					'type' => 'danger',
					'message' => 'message.not_owner',
				]
			);

			return $app->redirect($app['url_generator']->generate('list_edit', array('id' => $listId)));
		}

		$form = $app['form.factory']->createBuilder(FormType::class, $product)->add('id', HiddenType::class)->getForm();
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			$productsRepository->delete($form->getData());

			$app['session']->getFlashBag()->add(
				'messages',
				[
					'type' => 'success',
					'message' => 'message.element_successfully_deleted',
				]
			);

			return $app->redirect($app['url_generator']->generate('list_edit', array('id' => $listId)), 301);
		}

		return $app['twig']->render(
			'products/delete.html.twig',
			[
				'deletedProduct' => $product,
				'form' => $form->createView(),
				'lists' => $listsRepository->findAll($user),
			]
		);
	}

}