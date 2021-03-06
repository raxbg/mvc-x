<?php
error_reporting(E_ALL);
date_default_timezone_set('UTC');

/*** CONSTANTS ***/
define ('DS', DIRECTORY_SEPARATOR);
define ('SITE_PATH', realpath(dirname(__FILE__)));
define ('SITE_HOST',$_SERVER['HTTP_HOST']);
define ('DIRNAME_X', 'x');
define ('NOTICE_DEBUG_GROUP', 'Notices');

/*** BOOTUP ***/
include '.mvcx/boot.php';

/*** APP LOADS ***/
$registry->set('lib', new lib);
$app->initialize();
$log = new Log(SITE_PATH . DS . 'app' . DS . $app->dir . DS . '.error_log');
$registry->set('log', $log);
$request = new Request();
$registry->set('request', $request);
$session = new Session();
$registry->set('session', $session);
$load = new Load($registry);
$registry->set('load', $load);
$app->load = $load;
$router = new Router($registry);
$registry->set('router', $registry);
$app->router = $router;
$app->router->loader();
