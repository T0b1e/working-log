<?php
require_once __DIR__ . '/../../vendor/autoload.php'; // Include Composer autoload
require_once __DIR__ . '/../models/User.php'; // Corrected path to User.php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Dotenv\Dotenv;

class AuthController {

    private $db;
    private $secretKey;

    public function __construct() {
        // Initialize the database connection
        $database = new Database();
        $this->db = $database->connect();

        // Load environment variables
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../..');
        $dotenv->load();

        // Get the JWT secret key from .env
        $this->secretKey = $_ENV['JWT_SECRET'];
    }

    // Handle user signup
    public function signup() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $user = new User();
            $email = $_POST['email'];

            // Check if the email already exists
            if ($user->readByEmail($email)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Email is already in use'
                ]);
                return;
            }

            // Capture additional fields from POST request
            $user->username = $_POST['username'];
            $user->email = $email;
            $user->password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash the password
            $user->department = $_POST['department'];
            $user->phone = $_POST['phone'];
            $user->address = $_POST['address'];

            // Set the default role to 'users'
            $user->role = 'user';

            // Validate required fields
            if (empty($user->username) || empty($user->email) || empty($user->password) || empty($user->department) || empty($user->phone) || empty($user->address)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'All fields are required.'
                ]);
                return;
            }

            // Proceed with user creation
            if ($user->create()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Signup successful'
                ]);

                // Redirect to login page
                header('Location: /working-log/public/login.php');
                exit();
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Signup failed'
                ]);
            }
        }
    }

    // Handle user login with JWT token generation
    public function login() {

        session_start();
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $email = $_POST['email'];
            $password = $_POST['password'];
    
            $user = new User();
            $user_data = $user->readByEmail($email); // Fetch user by email

            if ($user_data && password_verify($password, $user_data['password'])) {
                // Generate JWT token
                $issuedAt = time();
                $expirationTime = $issuedAt + (60 * 60); // JWT valid for 1 hour
                $payload = [
                    'iss' => 'localhost', // Issuer
                    'iat' => $issuedAt, // Issued at
                    'exp' => $expirationTime, // Expiration time
                    'user_id' => $user_data['user_id'],
                    'username' => $user_data['username'],
                    'role' => $user_data['role']
                ];
    
                $jwt = JWT::encode($payload, $this->secretKey, 'HS256');
    
                // Set JWT as HttpOnly cookie
                setcookie('authToken', $jwt, $expirationTime, '/', '', false, true);
                setcookie('user_id', $user_data['user_id'], $expirationTime, '/', '', false, true);
                setcookie('role', $user_data['role'], $expirationTime, '/', '', false, true); // Store role in cookie
    
                // Important: Call exit after header() to stop further script execution
                // header('Location: /working-log/public/dashboard.php');
            header("Location: ../../public/dashboard.php");
                exit();
            } else {
                if (session_status() == PHP_SESSION_NONE) {
                    session_start();
                }
                // Set error message in session
                $_SESSION['login_error'] = 'Invalid username or password';
                // Redirect back to login page
                header('Location: ../../public/login.php');
                exit();
            }
        }
    }    
    
    // Middleware to authenticate requests using JWT
    public function authenticate() {
        if (isset($_COOKIE['authToken'])) {
            $jwt = $_COOKIE['authToken'];
    
            try {
                $decoded = JWT::decode($jwt, new Key($this->secretKey, 'HS256'));
                return $decoded;  // You can access $decoded->user_id, $decoded->username, etc.
            } catch (Exception $e) {
                http_response_code(401);
                echo json_encode(['message' => 'Unauthorized']);
                exit();
            }
        } else {
            http_response_code(401);
            echo json_encode(['message' => 'Token not provided']);
            exit();
        }
    }

    // Handle user logout
    public function logout() {
        setcookie('authToken', '', time() - 3600, '/');
        setcookie('user_id', '', time() - 3600, '/');
        setcookie('role', '', time() - 3600, '/');
        session_start();
        session_unset();
        session_destroy();
        header('Location: login.php');
    }
}

// Route the request based on the action parameter
if (isset($_GET['action'])) {
    $authController = new AuthController();

    if ($_GET['action'] == 'login') {
        $authController->login();
    } elseif ($_GET['action'] == 'signup') {
        $authController->signup();
    } elseif ($_GET['action'] == 'logout') {
        $authController->logout();
    }
}