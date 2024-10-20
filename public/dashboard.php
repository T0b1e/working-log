<?php


session_start();

// ตรวจสอบว่าผู้ใช้ได้รับการตรวจสอบสิทธิ์หรือไม่
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
    <title>ระบบบันทึกงาน</title>
    <link rel="stylesheet" href="./css/globals.css">
    <link rel="stylesheet" href="./css/navbars.css">
    <link rel="stylesheet" href="./css/forms.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <!-- แถบนำทาง -->
    <nav class="navbar">
        <div class="navbar-title"><a href="dashboard.php">ระบบบันทึกปฎิบัติงาน</a></div>
        <ul>
            <?php if ($user_role === 'admin'): ?>
                <li><a href="admin.php">🔧 แผงควบคุมผู้ดูแล</a></li>
            <?php endif; ?>
            <li><a href="settings.php">การตั้งค่าผู้ใช้งาน</a></li>
            <li><a href="logout.php">ออกจากระบบ</a></li>
        </ul>
    </nav>

    <!-- เนื้อหาหลักแบ่งเป็นสองส่วน -->
    <div class="split-container">
        <!-- ด้านซ้าย (ตารางแสดงรายการ) -->
        <div class="left-side">
            <div class="search-filter-container">
                <label for="searchCriteria">ค้นหาตาม:</label>
                <select id="searchCriteria">
                    <option value="username">ผู้ส่ง</option>
                    <option value="title">หัวข้อ</option>
                    <option value="priority">ลำดับความสำคัญ</option>
                    <option value="status">สถานะ</option>
                    <option value="created_at">วันที่</option>
                    <option value="file_name">ชื่อเอกสาร</option>
                </select>
                <label for="searchTerm"></label>
                <input type="text" id="searchTerm" placeholder="กรอกข้อมูลค้นหา...">
                <button id="searchButton">ค้นหา</button>
            </div>

            <table id="userTable">
                <thead>
                    <tr>
                        <th>วันที่</th>
                        <th>เวลา</th>
                        <th class="username-column">ชื่อผู้ใช้</th>
                        <th>หัวข้อ</th>
                        <th class="description-column">รายละเอียด</th>
                        <th>สถานะ</th>
                        <th>เอกสาร</th>
                        <th>แก้ไข</th>
                        <th>ลบ</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- แถวจะถูกสร้างและเพิ่มที่นี่ -->
                </tbody>
            </table>
        </div>

        <!-- ด้านขวา (ฟอร์มสำหรับการอัปโหลด) -->
        <div class="right-side">
            <div class="form-container">
                <h2>แบบบันทึกการปฎิบัติงาน</h2>
                <form id="uploadForm" enctype="multipart/form-data">
                    <label for="title">หัวข้อ</label>
                    <select id="title" name="title" required>
                        <option value="เรื่องทั่วไป">เรื่องทั่วไป</option>
                        <option value="แก้ไข">แก้ไข</option>
                        <option value="ตรวจสอบ">ตรวจสอบ</option>
                        <option value="รายงานปัญหา">รายงานปัญหา</option>
                        <option value="ร้องทุกข์">ร้องทุกข์</option>
                        <option value="ร้องเรียน">ร้องเรียน</option>
                    </select>

                    <label for="description">รายละเอียด</label>
                    <textarea id="description" name="description" required></textarea>

                    <label for="priority">ลำดับความสำคัญ</label>
                    <select id="priority" name="priority" required>
                        <option value="ด่วนที่สุด">ด่วนที่สุด</option>
                        <option value="ด่วน">ด่วน</option>
                        <option value="ปกติ">ปกติ</option>
                    </select>

                    <label for="status">สถานะ</label>
                    <select id="status" name="status" required>
                        <option value="ดำเนินการแล้วเสร็จ">ดำเนินการแล้วเสร็จ</option>
                        <option value="ดำเนินการ">ดำเนินการ</option>
                        <option value="กำลังดำเนินการ">กำลังดำเนินการ</option>
                    </select>

                    <label for="body">ความคิดเห็นเพิ่มเติม</label>
                    <textarea id="body" name="body"></textarea>

                    <label for="fileToUpload">อัปโหลดไฟล์ (ถ้ามี)</label>
                    <input type="file" id="fileToUpload" name="fileToUpload">

                    <button type="submit">ส่งข้อมูล</button>
                </form>
                <div id="error-message" style="color:red;"></div>
                <div id="fileList"></div>
            </div>
        </div>
    </div>

    <!-- โมดอลสำหรับการแก้ไขรายการที่มีอยู่ -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span id="closeModal" class="close-btn">&times;</span>
            <h2>แก้ไขข้อมูล</h2>
            <form id="editForm">
                <label for="editTitle">หัวข้อ</label>
                <input type="text" id="editTitle" name="title" required>
                <label for="editDescription">รายละเอียด</label>
                <textarea id="editDescription" name="description" required></textarea>
                <label for="editPriority">ลำดับความสำคัญ</label>
                <select id="editPriority" name="priority" required>
                    <option value="low">ด่วนที่สุด</option>
                    <option value="medium">ด่วน</option>
                    <option value="high">ปกติ</option>
                </select>
                <label for="editStatus">สถานะ</label>
                <select id="editStatus" name="status" required>
                    <option value="ดำเนินการแล้วเสร็จ">ดำเนินการแล้วเสร็จ</option>
                    <option value="ดำเนินการ">ดำเนินการ</option>
                    <option value="กำลังดำเนินการ">กำลังดำเนินการ</option>
                </select>
                <input type="hidden" id="editMessageId" name="message_id">
                <button type="submit">บันทึกการแก้ไข</button>
                <div id="currentFileSection">
                    <label>ไฟล์ปัจจุบัน:</label>
                    <span id="currentFileName"></span>
                    <button id="deleteFileBtn">ลบไฟล์</button>
                    <label for="newFileUpload">อัปโหลดไฟล์ใหม่:</label>
                    <input type="file" id="newFileUpload" name="fileToUpload">
                </div>
            </form>
        </div>
    </div>

    <!-- JavaScript และ AJAX -->
    <script>
        document.getElementById('uploadForm').addEventListener('submit', function (event) {
            event.preventDefault();

            try {
                const formData = new FormData();
                formData.append('title', document.getElementById('title').value);
                formData.append('description', document.getElementById('description').value);
                formData.append('priority', document.getElementById('priority').value);
                formData.append('status', document.getElementById('status').value);
                formData.append('body', document.getElementById('body').value);

                const file = document.getElementById('fileToUpload').files[0];
                if (file) {
                    formData.append('fileToUpload', file);
                }

                fetch('utils/upload_handler.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        console.log("อัปโหลดสำเร็จ"); // สำคัญ: บันทึกการอัปโหลด
                        alert('อัปโหลดข้อมูลสำเร็จ');
                        fetchUserData(); // อัปเดตตารางข้อมูล
                    } else {
                        document.getElementById('error-message').innerText = 'การอัปโหลดล้มเหลว: ' + data.message;
                    }
                })
                .catch(error => {
                    console.error('ข้อผิดพลาดระหว่างการอัปโหลด:', error); // สำคัญ: บันทึกข้อผิดพลาด
                    document.getElementById('error-message').innerText = 'การอัปโหลดล้มเหลว!';
                });
            } catch (error) {
                console.error("ข้อผิดพลาดที่ไม่คาดคิด: ", error); // สำคัญ: ข้อผิดพลาดที่ไม่คาดคิด
            }
        });

        function fetchUserData(searchCriteria = '', searchTerm = '') {
            console.log('กำลังดึงข้อมูลผู้ใช้...'); // สำคัญ: ตรวจสอบบันทึกการดึงข้อมูล

            const loadingMessage = document.createElement('tr');
            loadingMessage.innerHTML = `<td colspan="8" style="text-align:center;">กำลังโหลด...</td>`;
            document.querySelector('#userTable tbody').appendChild(loadingMessage);

            fetch(`utils/fetch_users.php?criteria=${searchCriteria}&term=${encodeURIComponent(searchTerm)}`)
                .then(response => response.json())
                .then(data => {
                    const userTableBody = document.querySelector('#userTable tbody');
                    userTableBody.innerHTML = ''; // ล้างแถวที่มีอยู่

                    if (data.success) {
                        if (data.data.length === 0) {
                            const noDataRow = document.createElement('tr');
                            noDataRow.innerHTML = `<td colspan="8" style="text-align:center;">ไม่พบข้อมูล</td>`;
                            userTableBody.appendChild(noDataRow);
                        } else {
                            data.data.forEach(user => {
                                userTableBody.appendChild(createUserRow(user));
                            });
                            attachEventHandlers(); // แนบฟังก์ชันการจัดการปุ่ม
                        }
                    } else {
                        alert('ข้อผิดพลาด: ' + data.message);
                    }
                })
                .catch(error => console.error('ข้อผิดพลาดระหว่างการดึงข้อมูลผู้ใช้:', error));
        }

        document.getElementById('searchButton').addEventListener('click', function() {
            const searchCriteria = document.getElementById('searchCriteria').value;
            const searchTerm = document.getElementById('searchTerm').value;
            fetchUserData(searchCriteria, searchTerm);
        });

        window.onload = function() {
            fetchUserData(); // ดึงข้อมูลผู้ใช้เริ่มต้น
            document.getElementById('editModal').style.display = 'none';
        };

        function createUserRow(user) {
            const row = document.createElement('tr');
            const uploadDate = user.created_at ? new Date(user.created_at).toLocaleDateString() : 'ไม่พบเจอ';
            const uploadTime = user.created_at ? new Date(user.created_at).toLocaleTimeString() : 'ไม่พบเจอ';
            const fileLink = user.file_name ? `<a href="../uploads/${user.file_name}" target="_blank">${user.file_name}</a>` : 'ไม่มีไฟล์';

            row.innerHTML = `
                <td>${uploadDate}</td>
                <td>${uploadTime}</td>
                <td>${user.username}</td>
                <td>${user.title || 'ไม่พบเจอ'}</td>
                <td>${user.description || 'ไม่พบเจอ'}</td>
                <td>${user.status || 'ไม่พบเจอ'}</td>
                <td>${fileLink}</td>
                <td><button class="edit-btn" data-id="${user.message_id}"><i class="fas fa-edit"></i></button></td>
                <td><button class="delete-btn" data-id="${user.message_id}"><i class="fas fa-trash-alt"></i></button></td>
            `;
            return row;
        }

        function attachEventHandlers() {
            document.querySelectorAll('.edit-btn').forEach(button => {
                button.addEventListener('click', handleEdit);
            });

            document.querySelectorAll('.delete-btn').forEach(button => {
                button.addEventListener('click', handleDelete);
            });
        }

        function handleEdit(event) {
            const messageId = event.target.getAttribute('data-id');

            fetch(`utils/fetch_message.php?id=${messageId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        populateEditModal(data.message);
                        document.getElementById('editModal').style.display = 'block';

                        const editForm = document.getElementById('editForm');
                        const newEditForm = editForm.cloneNode(true); 
                        editForm.parentNode.replaceChild(newEditForm, editForm);

                        newEditForm.addEventListener('submit', function(event) {
                            event.preventDefault();

                            const messageId = document.getElementById('editMessageId').value;
                            const formData = new FormData(newEditForm);

                            fetch(`utils/update_message.php?id=${messageId}`, {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    alert('แก้ไขข้อมูลสำเร็จ');
                                    document.getElementById('editModal').style.display = 'none';
                                    fetchUserData();
                                } else {
                                    alert('ข้อผิดพลาด: ' + data.message);
                                }
                            })
                            .catch(error => console.error('ข้อผิดพลาดระหว่างการแก้ไขข้อมูล:', error));
                        });
                    } else {
                        alert("ข้อผิดพลาด: ไม่สามารถดึงข้อมูลเพื่อแก้ไขได้");
                    }
                })
                .catch(error => console.error("ข้อผิดพลาดระหว่างการดึงข้อมูล:", error));
        }

        document.getElementById('closeModal').addEventListener('click', function() {
            document.getElementById('editModal').style.display = 'none';
            document.getElementById('editForm').reset();
        });

        function handleDelete(event) {
            const messageId = event.target.getAttribute('data-id');

            if (confirm('คุณแน่ใจหรือไม่ว่าต้องการลบข้อมูลนี้?')) {
                fetch(`utils/delete_message.php?id=${messageId}`, { method: 'POST' })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('ลบข้อมูลสำเร็จ');
                            fetchUserData();
                        } else {
                            alert('ลบข้อมูลล้มเหลว: ' + data.message);
                        }
                    })
                    .catch(error => console.error('ข้อผิดพลาดระหว่างการลบข้อมูล:', error));
            }
        }
    </script>
</body>
</html>
