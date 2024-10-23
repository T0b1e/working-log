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
			// Get raw input and decode JSON
			$input = file_get_contents('php://input');
			$data = json_decode($input, true);

			$user = new User();
			$email = $data['email'];

			// Check if the email already exists
			if ($user->readByEmail($email)) {
				echo json_encode([
					'success' => false,
					'message' => 'Email is already in use'
				]);
				return;
			}

			// Capture additional fields from the decoded JSON
			$user->username = trim($data['username']);
			$user->email = trim($email);
			$user->password = password_hash(trim($data['password']), PASSWORD_DEFAULT); // Hash the password
			$user->department = trim($data['department']);
			$user->phone = trim($data['phone']);
			$user->address = trim($data['address']);

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
					header('Location: ../../public/login.php');
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
			// Get raw input and decode JSON
			$input = file_get_contents('php://input');
			$data = json_decode($input, true);

			// Check if email and password are set
			if (!isset($data['email']) || !isset($data['password'])) {
				echo json_encode([
					'success' => false,
					'message' => 'Email and password are required'
				]);
				exit();
			}

			$email = $data['email'];
			$password = $data['password'];

			$user = new User();
			$user_data = $user->readByEmail($email); // Fetch user by email

			if ($user_data === null) {
				// If no user is found with the given email
				echo json_encode([
					'success' => false,
					'message' => 'No account found with that email address'
				]);
				exit();
			}

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

				echo json_encode([
					'success' => true,
					'message' => 'Login successful'
				]);
				exit();
			} else {
				// Password is incorrect
				echo json_encode([
					'success' => false,
					'message' => 'Invalid email or password'
				]);
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