<?php
session_start();

if (!isset($_COOKIE['authToken']) || !isset($_COOKIE['role'])) {
    header('Location: login.php');
    exit();
}

$user_role = $_COOKIE['role'];
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📋 ระบบบันทึกปฏิบัติงาน</title>
    <link rel="stylesheet" href="./css/globals.css">
    <link rel="stylesheet" href="./css/navbars.css">
    <link rel="stylesheet" href="./css/forms.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        <?php if ($user_role === 'admin'): ?>
        .right-side {
            display: none;
        }
        .left-side {
            width: 100% !important;
        }
        <?php endif; ?>
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-title"><a href="dashboard.php">📋 ระบบบันทึกปฏิบัติงาน</a></div>
        <ul>
            <?php if ($user_role === 'admin'): ?>
                <li><a href="admin.php">🔧 แผงควบคุมผู้ดูแล</a></li>
            <?php endif; ?>
            
            <?php  if ($user_role === 'admin'): ?>
                <!-- <li><a href="view.php">dashboard</a></li> -->
            <?php endif; ?>
            <li><a href="settings.php">⚙️ การตั้งค่าผู้ใช้</a></li>
            <li><a href="logout.php">🚪 ออกจากระบบ</a></li>
        </ul>
    </nav>

    <div class="split-container">
        <div class="left-side">

        <div class="search-filter-container">
            <label for="searchCriteria">🔍 ค้นหาตาม:</label>
            <select id="searchCriteria">
                <option value="username">ผู้ส่ง</option>
                <option value="title">หัวข้อ</option>
                <option value="priority">ลำดับความสำคัญ</option>
                <option value="status">สถานะ</option>
                <option value="created_at">วันที่</option>
                <option value="file_name">ชื่อเอกสาร</option>
            </select>
            <input type="text" id="searchTerm" placeholder="กรอกข้อมูลค้นหา...">
            <button id="clearSearchButton">❌ ล้างการค้นหา</button> <!-- Clear search button -->
            <button id="searchButton">🔎 ค้นหา</button>
        </div>

            <div class="record-count-container">
                <span id="recordCount" class="record-count-label">📊 จำนวนบันทึกทั้งสิ้น: 0</span>
            </div>

            <table id="userTable">
                <thead>
                    <tr>
                        <th>📅 วันที่</th>
                        <th>⏰ เวลา</th>
                        <th class="username-column">👤 ชื่อผู้ใช้</th>
                        <th class="title-column">📝 หัวข้อ</th>
                        <th class="description-column">📄 รายละเอียด</th>
                        <th>⚙️ สถานะ</th>
                        <th>📎 เอกสาร</th>
                        <th>📄 รายละเอียดเพิ่มเติม</th>
                        <th>✏️ แก้ไข</th>
                        <th>🗑️ ลบ</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        
            <div class="recordCountSelect-class" style="margin-top: 20px;">
                <label for="recordCountSelect">📊 จำนวนรายการที่จะแสดง:</label>
                <select id="recordCountSelect">
                    <option value="10">10</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                    <option value="ทั้งหมด">ทั้งหมด</option>
                </select>
            </div>

            <div id="pagination-container" class="pagination" style="margin-top: 20px;"></div>
        </div>

        <?php if ($user_role !== 'admin'): ?>
        <div class="right-side">
            <div class="form-container">
                <h2>📝 แบบบันทึกการปฏิบัติงาน</h2>
                <form id="uploadForm" enctype="multipart/form-data">

                    <label for="title">📄 หัวข้อ</label>
                    <select id="title" name="title" required>
                        <!-- Dynamic title options will be inserted here by JS -->
                    </select>

                    <label for="description">📄 รายละเอียด</label>
                    <textarea id="description" name="description" required></textarea>

                    <label for="priority">⚡ ลำดับความสำคัญ</label>
                        <select id="priority" name="priority" required>
                            <!-- dynamic -->
                        </select>

                    <label for="status">⚙️ สถานะ</label>
                        <select id="status" name="status" required>
                            <!-- dynamic -->
                        </select>

                    <label for="body">💬 ความคิดเห็นเพิ่มเติม</label>
                    <textarea id="body" name="body"></textarea>

                    <label for="fileToUpload">📎 อัปโหลดไฟล์ (ถ้ามี)</label>
                    <input type="file" id="fileToUpload" name="fileToUpload">

                    <button type="submit">📤 ส่งข้อมูล</button>
                </form>
                <div id="error-message" style="color:red;"></div>
                <div id="fileList"></div>
            </div>
        </div>
        <?php endif; ?>
    </div>

     <!-- Modal for displaying row details -->
     <div id="detailModal" class="modal">
        <div class="modal-content">
            <span id="closeDetailModal" class="close-btn">&times;</span>
            <h2>📄 รายละเอียด</h2>
            <div id="modalDetails"></div>
        </div>
    </div>
    
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span id="closeModal" class="close-btn">&times;</span>
            <h2>✏️ แก้ไขข้อมูล</h2>
            <form id="editForm">
                <label for="editTitle">📄 หัวข้อ</label>
                <select id="editTitle" name="title">
                    <!-- Dynamic title options will be inserted here by JS -->
                </select>
                <label for="editDescription">📄 รายละเอียด</label>
                <textarea id="editDescription" name="description"></textarea>
                <label for="editPriority">⚡ ลำดับความสำคัญ</label>
                <select id="editPriority" name="priority">
                    <option value="low">ด่วนที่สุด</option>
                    <option value="medium">ด่วน</option>
                    <option value="high">ปกติ</option>
                </select>
                <label for="editStatus">⚙️ สถานะ</label>
                <select id="editStatus" name="status" required>
                    <option value="ดำเนินการแล้วเสร็จ">ดำเนินการแล้วเสร็จ</option>
                    <option value="ดำเนินการ">ดำเนินการ</option>
                    <option value="กำลังดำเนินการ">กำลังดำเนินการ</option>
                </select>
                <input type="hidden" id="editMessageId" name="message_id">
                <button type="submit">💾 บันทึกการแก้ไข</button>
                <div id="currentFileSection">
                    <div class="currentFileSelectionLabel">
                        <label>📁 ไฟล์ปัจจุบัน:</label>
                        <span id="currentFileName"></span>
                    </div>
                    <button id="deleteFileBtn">🗑️ ลบไฟล์</button>
                    <label for="newFileUpload">📎 อัปโหลดไฟล์ใหม่:</label>
                    <input type="file" id="newFileUpload" name="fileToUpload">
                </div>
            </form>
        </div>
    </div>

    <script src="main.js"></script>
    
</body>
</html>
