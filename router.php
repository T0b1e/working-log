<?php
// router.php

require_once 'config/db.php';
require_once '../src/controllers/AuthController.php';

// Create a database connection
$dbConnection = (new Database())->connect();

// Initialize AuthController
$authController = new AuthController($dbConnection);

// Basic routing logic
$request_uri = explode('?', $_SERVER['REQUEST_URI'], 2)[0];

switch ($request_uri) {
    case '/login':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $authController->login();
        } else {
            $authController->loginForm();
        }
        break;
    
    case '/signup':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $authController->signup();
        } else {
            $authController->signupForm();
        }
        break;
    
    case '/logout':
        $authController->logout();
        break;
    
    default:
        http_response_code(404);
        echo 'Page not found';
        break;
}
