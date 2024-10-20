<?php
session_start();

// ตรวจสอบว่าผู้ใช้ได้รับการตรวจสอบสิทธิ์หรือไม่
if (!isset($_COOKIE['authToken']) || !isset($_COOKIE['role'])) {
    header('Location: login.php');
    exit();
}

$user_role = $_COOKIE['role'];
?>

<?php
require_once '../src/controllers/AuthController.php'; // Include AuthController
require_once '../src/models/User.php'; // Include User model

// Initialize session and get the logged-in user's ID from the cookie
session_start();
if (!isset($_COOKIE['authToken'])) {
    header('Location: login.php'); // Redirect to login if not authenticated
    exit();
}

// Get user_id from cookie
$user_id = $_COOKIE['user_id'];

// Fetch user details
$user = new User();
$userData = $user->readById($user_id);

if (!$userData) {
    echo "ไม่พบผู้ใช้.";
    exit();
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];

    // Fetch user data again to verify current password
    $user_data = $user->read($user_id);

    if (password_verify($currentPassword, $user_data['password'])) {
        // Update password
        $user->password = password_hash($newPassword, PASSWORD_DEFAULT);
        $user->user_id = $user_id;

        if ($user->update()) {
            echo "<p style='color: green;'>🔒 รหัสผ่านถูกเปลี่ยนสำเร็จ.</p>";
        } else {
            echo "<p style='color: red;'>❌ ไม่สามารถเปลี่ยนรหัสผ่านได้.</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ รหัสผ่านปัจจุบันไม่ถูกต้อง.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔧 การตั้งค่าบัญชีผู้ใช้</title>
    <link href="https://fonts.googleapis.com/css2?family=TH+Sarabun+New:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./css/settings.css">
    <link rel="stylesheet" href="./css/globals.css">
    <link rel="stylesheet" href="./css/navbars.css">
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="navbar-title"><a href="dashboard.php">🔙 กลับสู่แดชบอร์ด</a></div>
    </nav>

    <div class="settings-container">
        <h1>⚙️ การตั้งค่าบัญชีผู้ใช้</h1>

        <!-- User Information Form -->
        <div class="user-profile">
            <h2>👤 แก้ไขข้อมูลผู้ใช้</h2>
            <form action="update_user.php" method="POST">
                <div>
                    <label for="username">👤 ชื่อผู้ใช้</label>
                    <input type="text" name="username" id="username" value="<?php echo $userData['username']; ?>" required>
                </div>
                <div>
                    <label for="email">📧 อีเมล</label>
                    <input type="email" name="email" id="email" value="<?php echo $userData['email']; ?>" required>
                </div>
                <div>
                    <label for="address">🏠 ที่อยู่</label>
                    <input type="text" name="address" id="address" value="<?php echo $userData['address'] ?? ''; ?>">
                </div>
                <div>
                    <label for="phone">📞 เบอร์โทรศัพท์</label>
                    <input type="text" name="phone" id="phone" value="<?php echo $userData['phone'] ?? ''; ?>">
                </div>
                <div>
                    <label>🏢 แผนก</label>
                    <input type="text" value="<?php echo $userData['department'] ?? 'ยังไม่ได้ระบุ'; ?>" disabled>
                </div>
                <div>
                    <label>⚖️ บทบาท</label>
                    <input type="text" value="<?php echo $userData['role']; ?>" disabled>
                </div>
                <button type="submit">💾 บันทึกการเปลี่ยนแปลง</button>
            </form>
        </div>

        <!-- Password Change Form -->
        <div class="password-change">
            <h2>🔒 เปลี่ยนรหัสผ่าน</h2>
            <form action="settings.php" method="POST">
                <div>
                    <label for="current_password">🔑 รหัสผ่านปัจจุบัน</label>
                    <input type="password" name="current_password" id="current_password" required>
                </div>
                <div>
                    <label for="new_password">🔑 รหัสผ่านใหม่</label>
                    <input type="password" name="new_password" id="new_password" required>
                </div>
                <button type="submit">🔄 เปลี่ยนรหัสผ่าน</button>
            </form>
        </div>
    </div>
</body>
</html>
