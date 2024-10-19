<?php
require_once '../src/models/User.php'; // Include User model
session_start();

// Check if the user is authenticated
if (!isset($_COOKIE['authToken']) || !isset($_COOKIE['user_id'])) {
    header('Location: login.php'); // Redirect if not authenticated
    exit();
}

// Get user ID from the cookie
$user_id = $_COOKIE['user_id'];

// Fetch the current user's data
$user = new User();
$userData = $user->readById($user_id);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $username = filter_var($_POST['username'], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    $address = filter_var($_POST['address'], FILTER_SANITIZE_STRING);
    $phone = filter_var($_POST['phone'], FILTER_SANITIZE_STRING);

    // Ensure required fields are not empty
    if ($username && $email) {
        // Update the user's profile
        $user->user_id = $user_id;
        $user->username = $username;
        $user->email = $email;
        $user->address = $address;
        $user->phone = $phone;

        // Save changes 
        if ($user->update()) {
            echo "<p style='color: green;'>✅ ข้อมูลผู้ใช้ถูกอัปเดตเรียบร้อย.</p>";
        } else {
            echo "<p style='color: red;'>❌ เกิดข้อผิดพลาดในการอัปเดตข้อมูลผู้ใช้.</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ โปรดกรอกข้อมูลที่จำเป็น (ชื่อผู้ใช้และอีเมล).</p>";
    }
}
?>
