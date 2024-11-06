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
$username = htmlspecialchars($userData['username'] ?? 'ผู้ใช้งาน'); // Sanitize output
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📋 ระบบบันทึกปฏิบัติงาน</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="./css/globals.css">
    <link rel="stylesheet" href="./css/navbars.css">
    <link rel="stylesheet" href="./css/forms.css">
    
    <!-- Inline Styles for Conditional Display -->
    <style>
        <?php if ($user_role === 'admin'): ?>
        .right-side {
            display: none;
        }
        .left-side {
            width: 100% !important;
        }
        <?php endif; ?>

        .navbar-center {
            flex-grow: 1;
            display: flex;
            justify-content: center;
            font-weight: bold;
            font-size: 1.1em;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="navbar-title"><a href="dashboard.php">📋 ระบบบันทึกปฏิบัติงาน</a></div>

        <div class="navbar-center">
            ผู้ใช้: <?php echo $username; ?>
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

    <!-- Main Content Container -->
    <div class="split-container">
        <!-- Left Side: Search Filters and Data Table -->
        <div class="left-side">

            <!-- Search Filters -->
            <div class="search-filter-container">
                <div class="search-row">
                    <!-- Search Criteria -->
                    <div class="search-group">
                        <label for="searchCriteria">🔍 ค้นหาตาม:</label>
                        <select id="searchCriteria">
                            <option value="username">ผู้ส่ง</option>
                            <option value="title">หัวข้อ</option>
							<option value="description">รายละเอียด</option> 
                            <option value="priority">ลำดับความสำคัญ</option>
                            <option value="status">สถานะ</option>
                            <option value="file_name">ชื่อเอกสาร</option>
                        </select>
                    </div>

                    <!-- Search Term -->
                    <div class="search-group">
                        <label for="searchTerm">กรอกข้อมูล:</label>
                        <input type="text" id="searchTerm" placeholder="กรอกข้อมูลค้นหา...">
                    </div>
                </div>
                <div class="search-buttons">
                    <button id="clearSearchButton">❌ ล้างการค้นหา</button>
                    <button id="searchButton">🔎 ค้นหา</button>
                </div>
            </div>

            <!-- Record Count Display -->
            <div class="record-count-container">
                <span id="recordCount" class="record-count-label">📊 จำนวนบันทึกทั้งสิ้น: 0</span>
            </div>

			<!-- Data Table -->
			<div class="table-container">
				<table id="userTable">
					<thead>
						<tr>
							<th>#</th> <!-- Index Column -->
							<th>เวลา</th>
							<th>วันที่จัดทำ</th>
							<th>วันที่สิ้นสุด</th>
							<th class="username-column">ชื่อผู้ใช้</th>
							<th class="title-column">หัวข้อ</th>
							<th class="description-column hide-on-mobile">รายละเอียด</th>
							<th>สถานะ</th>
							<th class="document-column hide-on-mobile">เอกสาร</th>
							<th class="action-column">ดูข้อมูล</th>
							<th class="action-column">แก้ไข</th>
							<th class="action-column">ลบ</th>
						</tr>
					</thead>
					<tbody></tbody>
				</table>
			</div>

            <!-- Record Count Selection -->
            <div class="recordCountSelect-class">
                <label for="recordCountSelect">📊 จำนวนรายการที่จะแสดง:</label>
                <select id="recordCountSelect">
                    <option value="10" selected>10</option> <!-- Default to 10 -->
                    <option value="50">50</option>
                    <option value="100">100</option>
                    <option value="ทั้งหมด">ทั้งหมด</option>
                </select>
            </div>

		<!-- Pagination -->
		<div id="pagination-container" class="pagination">
			<!-- Dynamic buttons will be injected here by JavaScript -->
		</div>

        </div>

        <!-- Right Side: Upload Form (Hidden for Admin) -->
        <?php if ($user_role !== 'admin'): ?>
        <div class="right-side">
            <div class="form-container">
                <h2>📝 แบบบันทึกการปฏิบัติงาน</h2>
                <form id="uploadForm" enctype="multipart/form-data">
                    <!-- Title Selection -->
                    <label for="title">📄 หัวข้อ</label>
                    <select id="title" name="title" required>
                        <!-- Dynamic options inserted via JavaScript -->
                    </select>
                    
                     <!-- Date Fields -->
                    <div class="date-fields">
                        <div class="form-group">
                            <label for="start_date">📅 วันที่จัดทำ</label>
                            <input type="date" id="start_date" name="start_date" required>
                        </div>
                        <div class="form-group">
                            <label for="end_date">📅 วันที่สิ้นสุด</label>
                            <input type="date" id="end_date" name="end_date">
                        </div>
                    </div>

                    <!-- Description -->
                    <label for="description">📄 รายละเอียด</label>
                    <textarea id="description" name="description"></textarea>

                    <!-- Priority Selection -->
                    <label for="priority">⚡ ลำดับความสำคัญ</label>
                    <select id="priority" name="priority" required>
                        <!-- Dynamic options inserted via JavaScript -->
                    </select>

                    <!-- Status Selection -->
                    <label for="status">⚙️ สถานะ</label>
                    <select id="status" name="status" required>
                        <!-- Dynamic options inserted via JavaScript -->
                    </select>

                    <!-- Additional Comments -->
                    <label for="body">💬 ความคิดเห็นเพิ่มเติม</label>
                    <textarea id="body" name="body"></textarea>

                    <!-- File Upload -->
                    <label for="fileToUpload">📎 อัปโหลดไฟล์ (ถ้ามี)</label>
                    <input type="file" id="fileToUpload" name="fileToUpload">

                    <!-- Progress Bar -->
                    <progress id="uploadProgress" class="styled-progress" value="0" max="100" style="display: none;"></progress>

                    <!-- Submit and Clear Buttons -->
                    <button type="submit">📤 ส่งข้อมูล</button>
                    <button type="button" id="clearUploadButton" style="margin-top: 10px">❌ ลบไฟล์</button>

                </form>
                <div id="error-message" class="error-message"></div>
                <div id="fileList"></div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Modal for Displaying Details -->
    <div id="detailModal" class="modal" role="dialog" aria-labelledby="detailModalTitle" aria-modal="true">
        <div class="modal-content">
            <span id="closeDetailModal" class="close-btn" aria-label="Close Modal">&times;</span>
            <h2 id="detailModalTitle">📄 รายละเอียด</h2>
            <div id="modalDetails">
                <!-- Modal Content Here -->
            </div>
        </div>
    </div>

    <!-- Modal for Editing a Message -->
	<div id="editModal" class="modal" role="dialog" aria-labelledby="editModalTitle" aria-modal="true">
		<div class="modal-content">
			<span id="closeModal" class="close-btn" aria-label="Close Modal">&times;</span>
			<h2 id="editModalTitle">✏️ แก้ไขข้อมูล</h2>
			<form id="editForm">

				<!-- Hidden Field for Message ID -->
				<input type="hidden" id="editMessageId" name="message_id">

				<!-- Title Selection -->
				<label for="editTitle">📄 หัวข้อ</label>
				<select id="editTitle" name="title" >
					<!-- Dynamic options inserted via JavaScript -->
				</select>
				
				<!-- Date Fields -->
				<div class="date-fields">
					<div class="form-group">
						<label for="editStartDate">📅 วันที่จัดทำ</label>
						<input type="date" id="editStartDate" name="start_date">
					</div>
					<div class="form-group">
						<label for="editEndDate">📅 วันที่สิ้นสุด</label>
						<input type="date" id="editEndDate" name="end_date">
					</div>
				</div>

				<!-- Description -->
				<label for="editDescription">📄 รายละเอียด</label>
				<textarea id="editDescription" name="description"></textarea>

				<!-- Priority Selection -->
				<label for="editPriority">⚡ ลำดับความสำคัญ</label>
				<select id="editPriority" name="priority">
					<!-- Dynamic options inserted via JavaScript -->
				</select>

				<!-- Status Selection -->
				<label for="editStatus">⚙️ สถานะ</label>
				<select id="editStatus" name="status">
					<!-- Dynamic options inserted via JavaScript -->
				</select>
				
				<!-- Description -->
				<label for="editBody">💬 ความคิดเห็นเพิ่มเติม</label>
				<textarea id="editBody" name="body"></textarea>

				<!-- Current File Section -->
				<div id="currentFileSection" style="display: none;">
					<div class="currentFileSelectionLabel">
						<label>📁 ไฟล์ปัจจุบัน:</label>
						<span id="currentFileName"></span>
					</div>
					<button type="button" id="deleteFileBtn">🗑️ ลบไฟล์</button>
					<label for="newFileUpload">📎 อัปโหลดไฟล์ใหม่:</label>
					<input type="file" id="newFileUpload" name="fileToUpload">
				</div>

				<!-- Submit Button -->
				<button type="submit">💾 บันทึกการแก้ไข</button>
			</form>
		</div>

    </div>

    <!-- JavaScript File -->
    <script src="main.js"></script>
</body>
</html>
