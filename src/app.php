<?php

use Silex\Application;
use Silex\Provider\AssetServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\DoctrineServiceProvider;
use Silex\Provider\FormServiceProvider;
use Silex\Provider\ValidatorServiceProvider;
use \Silex\Provider\SessionServiceProvider;


$app = new Application();
$app->register(new ServiceControllerServiceProvider());
$app->register(new AssetServiceProvider());
$app->register(new TwigServiceProvider(), [
	'twig.path' => dirname(dirname(__FILE__)).'/templates'
]);

$app->register(new \Silex\Provider\LocaleServiceProvider());
$app->register(
	new \Silex\Provider\TranslationServiceProvider(),
	[
		'locale' => 'pl',
		'locale_fallbacks' => array('en'),
	]
);
$app->extend('translator', function ($translator, $app) {
	$translator->addResource('xliff', __DIR__.'/../translations/messages.en.xlf', 'en', 'messages');
	$translator->addResource('xliff', __DIR__.'/../translations/validators.en.xlf', 'en', 'validators');
	$translator->addResource('xliff', __DIR__.'/../translations/messages.pl.xlf', 'pl', 'messages');
	$translator->addResource('xliff', __DIR__.'/../translations/validators.pl.xlf', 'pl', 'validators');

	return $translator;
});

$app->register(new DoctrineServiceProvider(),
	[
		'db.options' => [
			'driver' => 'pdo_mysql',
			'host' => 'localhost',
			'dbname' => 'shop_lista_db',
			'user' => 'shop_lista_admin',
			'password' => 'HEHtSPzejPwexe',
			'charset' => 'utf8',
			'driverOptions' => [
				1002 => 'SET NAMES utf8',
			],
		],
	]);

$app->register(new FormServiceProvider());
$app->register(new ValidatorServiceProvider());
$app->register(new SessionServiceProvider());


return $app;
