<?php
declare(strict_types=1);

use Bramus\Router\Router;
use Forum\Controllers\ForumController;

$router = new Router();
$router->setBasePath('/forum');

$ctrl = fn() => new ForumController();

$router->get('/',                                 fn()       => $ctrl()->home());
$router->get('/discussions/(\d+)',                fn($id)    => $ctrl()->discussion($id));
$router->get('/register',                         fn()       => $ctrl()->registerForm());
$router->post('/register',                        fn()       => $ctrl()->register());
$router->get('/login',                            fn()       => $ctrl()->loginForm());
$router->post('/login',                           fn()       => $ctrl()->login());
$router->post('/logout',                          fn()       => $ctrl()->logout());
$router->post('/discussions/(\d+)/comments',      fn($id)    => $ctrl()->postComment($id));

$router->run();

