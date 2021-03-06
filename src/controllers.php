<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Controller\ListsController;

//Request::setTrustedProxies(array('127.0.0.1'));

$app->get('/', function () use ($app) {
	return $app['twig']->render('index.html.twig');
})->bind('index_page');

$app->get('/about', function() use ($app) {
	return $app['twig']->render('about.html.twig');
})->bind('about_page');

$app->mount('/lists', new ListsController());
$app->mount('/product', new \Controller\ProductsController());
$app->mount('/auth', new \Controller\AuthController());
$app->mount('/wallet', new \Controller\WalletController());
$app->mount('/admin', new \Controller\AdminController());
$app->mount('/user', new \Controller\UserController());

$app->error(function (\Exception $e, Request $request, $code) use ($app) {
    if ($app['debug']) {
        return;
    }

    // 404.html, or 40x.html, or 4xx.html, or error.html
    $templates = array(
        'errors/'.$code.'.html.twig',
        'errors/'.substr($code, 0, 2).'x.html.twig',
        'errors/'.substr($code, 0, 1).'xx.html.twig',
        'errors/default.html.twig',
    );

    return new Response($app['twig']->resolveTemplate($templates)->render(array('code' => $code)), $code);
});
