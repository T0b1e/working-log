<?php
session_start();
require_once '../src/models/User.php'; // Include User model

// Check authentication
if (!isset($_COOKIE['authToken']) || !isset($_COOKIE['role'])) {
    header('Location: login.php');
    exit();
}

$user_role = $_COOKIE['role'];
$user_id = $_COOKIE['user_id'];

// Fetch username from database using user_id
$user = new User();
$userData = $user->readById($user_id);
$username = $userData['username'] ?? 'ผู้ใช้งาน'; // Default to 'ผู้ใช้งาน' if username not found
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔧 การตั้งค่าบัญชีผู้ใช้</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./css/settings.css">
    <link rel="stylesheet" href="./css/globals.css">
    <link rel="stylesheet" href="./css/navbars.css">
</head>
<body>
  	<!-- Navbar -->
    <nav class="navbar">
        <div class="navbar-title"><a href="dashboard.php">📋 ระบบบันทึกปฏิบัติงาน</a></div>

        <div class="navbar-center">
            ผู้ใช้: <?php echo htmlspecialchars($username); ?>
        </div>

        <ul>
            <?php if ($user_role === 'admin'): ?>
                <li><a href="admin.php">🔧 แผงควบคุมผู้ดูแล</a></li>
			    <li><a href="view.php">📊 รายงาน</a></li>
            <?php endif; ?>
            <li><a href="settings.php">⚙️ การตั้งค่าผู้ใช้</a></li>
            <li><a href="logout.php">🚪 ออกจากระบบ</a></li>
        </ul>
    </nav>

    <div class="settings-container">
        <h1>⚙️ การตั้งค่าบัญชีผู้ใช้</h1>

        <!-- User Information Form -->
        <div class="user-profile">
            <h2>👤 แก้ไขข้อมูลผู้ใช้</h2>
            <form action="update_user.php" method="POST">
                <input type="hidden" name="action" value="update_profile">
                <div>
                    <label for="username">👤 ชื่อผู้ใช้</label>
                    <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($userData['username']); ?>" required>
                </div>
                <div>
                    <label for="email">📧 อีเมล</label>
                    <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($userData['email']); ?>" required>
                </div>
                <div>
                    <label for="address">🏠 ที่อยู่</label>
                    <input type="text" name="address" id="address" value="<?php echo htmlspecialchars($userData['address'] ?? ''); ?>">
                </div>
                <div>
                    <label for="phone">📞 เบอร์โทรศัพท์</label>
                    <input type="text" name="phone" id="phone" value="<?php echo htmlspecialchars($userData['phone'] ?? ''); ?>">
                </div>
                <div>
                    <label>🏢 แผนก</label>
                    <input type="text" value="<?php echo htmlspecialchars($userData['department'] ?? 'ยังไม่ได้ระบุ'); ?>" disabled>
                </div> 
                <div>
                    <label>⚖️ บทบาท</label>
                    <input type="text" value="<?php echo htmlspecialchars($userData['role']); ?>" disabled>
                </div>
                <button type="submit">💾 บันทึกการเปลี่ยนแปลง</button>
            </form>
        </div>

        <!-- Password Change Form -->
        <div class="password-change">
            <h2>🔒 เปลี่ยนรหัสผ่าน</h2>
            <form action="update_user.php" method="POST">
                <input type="hidden" name="action" value="update_password">
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
