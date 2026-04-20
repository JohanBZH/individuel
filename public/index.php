<?php

/**
 * Front controller
 *
 * PHP version 7.0
 */

session_start();

/**
 * Composer
 */
require dirname(__DIR__) . '/vendor/autoload.php';


/**
 * Error and Exception handling
 */
error_reporting(E_ALL);
set_error_handler('Core\Error::errorHandler');
set_exception_handler('Core\Error::exceptionHandler');


/**
 * Routing
 */
$router = new Core\Router();

// Add the routes
$router->add('', ['controller' => 'Home', 'action' => 'index']);
$router->add('login', ['controller' => 'User', 'action' => 'login']);
$router->add('register', ['controller' => 'User', 'action' => 'register']);
$router->add('logout', ['controller' => 'User', 'action' => 'logout', 'private' => true]);
$router->add('account', ['controller' => 'User', 'action' => 'account', 'private' => true]);
// Legacy product routes removed — repurposed for reservation app
// (if you need redirects, we can add explicit redirects to /reservation here)
// Reservation routes (new)
$router->add('reservation', ['controller' => 'Reservation', 'action' => 'index', 'private' => true]);
$router->add('reservation/{id:\d+}', ['controller' => 'Reservation', 'action' => 'show']);
$router->add('reservation/edit/{id:\\d+}', ['controller' => 'Reservation', 'action' => 'edit', 'private' => true]);
$router->add('myreservations', ['controller' => 'Reservation', 'action' => 'my', 'private' => true]);
// cancel (owner or admin)
$router->add('reservation/cancel/{id:\d+}', ['controller' => 'Reservation', 'action' => 'cancel', 'private' => true]);
// admin listing
$router->add('reservations', ['controller' => 'Reservation', 'action' => 'admin', 'private' => true]);
$router->add('buildings', ['controller' => 'Building', 'action' => 'index']);
$router->add('{controller}/{action}');

/*
 * Gestion des erreurs dans le routing
 */
try {
    $router->dispatch($_SERVER['QUERY_STRING']);
} catch(Exception $e){
    switch($e->getMessage()){
        case 'You must be logged in':
            header('Location: /login');
            exit;
        case 'Vous ne pouvez pas modifier cette réservation.':
            $_SESSION['flash_message'] = "Accès non autorisé, contactez l'administrateur.rice";
            header('Location: /');
            exit;
        default:
            throw $e;
    }
}
