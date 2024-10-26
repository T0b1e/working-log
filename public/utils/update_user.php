<?php
require_once '../src/models/User.php';
session_start();

// Check if the user is authenticated
if (!isset($_COOKIE['authToken']) || !isset($_COOKIE['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get user ID from the cookie
$user_id = $_COOKIE['user_id'];
$user = new User();

// Fetch current user data
$userData = $user->readById($user_id);
if (!$userData) {
    echo "<p style='color: red;'>❌ ไม่พบข้อมูลผู้ใช้.</p>";
    exit();
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    // Sanitize input
    $username = filter_var($_POST['username'], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    $address = filter_var($_POST['address'], FILTER_SANITIZE_STRING);
    $phone = filter_var($_POST['phone'], FILTER_SANITIZE_STRING);

    if ($username && $email) {
        // Assign values to User object
        $user->user_id = $user_id;
        $user->username = $username;
        $user->email = $email;
        $user->address = $address;
        $user->phone = $phone;

        // Update profile
        if ($user->update()) {
            echo "<p style='color: green;'>✅ ข้อมูลผู้ใช้ถูกอัปเดตเรียบร้อย.</p>";
        } else {
            echo "<p style='color: red;'>❌ เกิดข้อผิดพลาดในการอัปเดตข้อมูลผู้ใช้.</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ โปรดกรอกข้อมูลที่จำเป็น (ชื่อผู้ใช้และอีเมล).</p>";
    }
}

// Handle password update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_password') {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];

    // Verify current password
    if (password_verify($currentPassword, $userData['password'])) {
        // Update to new password
        $user->user_id = $user_id;
        $user->password = password_hash($newPassword, PASSWORD_DEFAULT);

        if ($user->updatePassword()) {
            echo "<p style='color: green;'>🔒 รหัสผ่านถูกเปลี่ยนสำเร็จ.</p>";
        } else {
            echo "<p style='color: red;'>❌ ไม่สามารถเปลี่ยนรหัสผ่านได้.</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ รหัสผ่านปัจจุบันไม่ถูกต้อง.</p>";
    }
}
