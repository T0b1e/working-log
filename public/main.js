document.addEventListener('DOMContentLoaded', () => {

    // Function to fetch priorities and statuses
    function fetchOptions() {
        fetch('utils/manage_tag.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    populateDropdown('priority', data.priorities);
                    populateDropdown('status', data.statuses);
                    populateDropdown('editPriority', data.priorities);
                    populateDropdown('editStatus', data.statuses);
                } else {
                    console.error('Failed to fetch options', data.error);
                }
            })
            .catch(error => console.error('Error fetching options:', error));
    }

    // Helper function to populate dropdowns
    function populateDropdown(dropdownId, options) {
        const dropdown = document.getElementById(dropdownId);
        dropdown.innerHTML = ''; // Clear existing options

        options.forEach(option => {
            const optionElement = document.createElement('option');
            optionElement.value = option.name; // Assuming options have a 'name' field
            optionElement.textContent = option.name;
            dropdown.appendChild(optionElement);
        });
    }

    // Call fetchOptions on page load to populate dropdowns
    window.onload = function() {
        fetchUserData();
        fetchOptions();  // Fetch and populate Priority and Status dropdowns
        document.getElementById('editModal').style.display = 'none';
        document.getElementById('detailModal').style.display = 'none';
    };

    const uploadForm = document.getElementById('uploadForm');

    if (uploadForm) {
        uploadForm.addEventListener('submit', function (event) {
            event.preventDefault();

            const fileInput = document.getElementById('fileToUpload');
            const errorMessage = document.getElementById('error-message');
            errorMessage.innerText = '';  // Clear previous error messages

            if (fileInput.files.length > 0) {
                const file = fileInput.files[0];
                const fileName = file.name;
                const fileExtension = fileName.split('.').pop().toLowerCase();

                // Check for forbidden file extensions
                const forbiddenExtensions = ['exe', 'zip', 'apk'];

                if (forbiddenExtensions.includes(fileExtension)) {
                    errorMessage.innerText = 'ไม่อนุญาตให้อัปโหลดไฟล์ประเภท .exe, .zip, หรือ .apk';
                    return;
                }
            }

            // If file validation passes, proceed with the form submission
            const formData = new FormData(uploadForm);
            if (fileInput.files.length > 0) {
                formData.append('fileToUpload', fileInput.files[0]);
            }

            fetch('utils/upload_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('อัปโหลดข้อมูลสำเร็จ');
                    fetchUserData();  // Refresh the user table after successful upload
                } else {
                    errorMessage.innerText = 'การอัปโหลดล้มเหลว: ' + data.message;
                }
            })
            .catch(error => {
                errorMessage.innerText = 'การอัปโหลดล้มเหลว!';
            });
        });
    }

    let cachedUserData = null; // Cache variable

    function fetchUserData(searchCriteria = '', searchTerm = '', page = 1, limit = 10) {
        const userTableBody = document.querySelector('#userTable tbody');
        const recordCountLabel = document.getElementById('recordCount');
        let totalRecords = 0;

        userTableBody.innerHTML = '';  // Clear existing rows
        const loadingMessage = document.createElement('tr');
        loadingMessage.innerHTML = `<td colspan="8" style="text-align:center;">กำลังโหลด...</td>`;
        userTableBody.appendChild(loadingMessage);

        const selectedLimit = document.getElementById('recordCountSelect').value;
        limit = (selectedLimit === 'ทั้งหมด') ? 0 : parseInt(selectedLimit);

        fetch(`utils/fetch_users.php?criteria=${searchCriteria}&term=${encodeURIComponent(searchTerm)}&page=${page}&limit=${limit}`)
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(data => {
                userTableBody.innerHTML = '';  // Clear loading message
                if (data.success) {
                    if (!searchCriteria && !searchTerm) {
                        cachedUserData = data; // Cache data
                    }
                    populateTable(data.data);
                    totalRecords = data.data.length;
                    recordCountLabel.textContent = `จำนวนบันทึกทั้งสิ้น: ${totalRecords}`;
                    updatePagination(data.totalPages, page);
                } else {
                    alert('ข้อผิดพลาด: ' + data.message);
                    recordCountLabel.textContent = 'จำนวนบันทึกทั้งสิ้น: 0';
                }
            })
            .catch(error => {
                userTableBody.innerHTML = '';
                const errorRow = document.createElement('tr');
                errorRow.innerHTML = `<td colspan="8" style="text-align:center; color: red;">เกิดข้อผิดพลาดในการโหลดข้อมูล</td>`;
                userTableBody.appendChild(errorRow);
                recordCountLabel.textContent = 'จำนวนบันทึกทั้งสิ้น: 0';
            });
    }

    function populateTable(data) {
        const userTableBody = document.querySelector('#userTable tbody');
        userTableBody.innerHTML = '';  // Clear the table

        data.forEach(user => {
            userTableBody.appendChild(createUserRow(user));
        });
    }

    function createUserRow(user) {
        const row = document.createElement('tr');
        const uploadDate = user.created_at ? new Date(user.created_at).toLocaleDateString() : 'ไม่พบเจอ';
        const uploadTime = user.created_at ? new Date(user.created_at).toLocaleTimeString() : 'ไม่พบเจอ';
        const fileLink = user.file_name;

        row.innerHTML = `
            <td>${uploadDate}</td>
            <td>${uploadTime}</td>
            <td>${user.username}</td>
            <td>${user.title || 'ไม่พบเจอ'}</td>
            <td>${user.description || 'ไม่พบเจอ'}</td>
            <td>${user.status || 'ไม่พบเจอ'}</td>
            <td>${fileLink}</td>
            <td><button class="detail-btn" data-id="${user.message_id}"><i class="fas fa-info-circle"></i></button></td> 
            <td><button class="edit-btn" data-id="${user.message_id}"><i class="fas fa-edit"></i></button></td>
            <td><button class="delete-btn" data-id="${user.message_id}"><i class="fas fa-trash-alt"></i></button></td>
        `;
        return row;
    }

    // Use Event Delegation to handle dynamic elements like edit, detail, delete buttons
    document.querySelector('#userTable').addEventListener('click', function (event) {
        const target = event.target;
        const messageId = target.closest('button').dataset.id;

        // Handle Detail
        if (target.classList.contains('detail-btn')) {
            fetchMessageAndDisplayModal(messageId, 'detail');
        }

        // Handle Edit
        if (target.classList.contains('edit-btn')) {
            fetchMessageAndDisplayModal(messageId, 'edit');
        }

        // Handle Delete
        if (target.classList.contains('delete-btn')) {
            handleDelete(messageId);
        }
    });

    // Handle message fetching and modal display for both detail and edit
    function fetchMessageAndDisplayModal(messageId, mode) {
        fetch(`utils/fetch_message.php?id=${messageId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (mode === 'detail') {
                        displayDetailsModal(data.message);
                    } else if (mode === 'edit') {
                        populateEditModal(data.message);
                        document.getElementById('editModal').style.display = 'block';
                    }
                } else {
                    alert('ข้อผิดพลาด: ไม่สามารถดึงข้อมูลได้');
                }
            })
            .catch(error => console.error('ข้อผิดพลาดระหว่างการดึงข้อมูล:', error));
    }

    function displayDetailsModal(message) {
        const modalDetails = document.getElementById('modalDetails');
        const fileLink = message.file_name ? 
            `<a href="../uploads/${message.file_name}" target="_blank">${message.file_name}</a>` 
            : 'ไม่มีไฟล์';

        modalDetails.innerHTML = `
            <p>หัวข้อ: ${message.title}</p>
            <p>รายละเอียด: ${message.description}</p>
            <p>สถานะ: ${message.status}</p>
            <p>ไฟล์: ${fileLink}</p>
        `;
        
        document.getElementById('detailModal').style.display = 'block';
    }

    // Define the populateEditModal function
    function populateEditModal(message) {
        document.getElementById('editTitle').value = message.title;
        document.getElementById('editDescription').value = message.description;
        document.getElementById('editPriority').value = message.priority;
        document.getElementById('editStatus').value = message.status;
        document.getElementById('editMessageId').value = message.message_id;

        if (message.file_name) {
            document.getElementById('currentFileName').textContent = message.file_name;
            document.getElementById('currentFileSection').style.display = 'block';
        } else {
            document.getElementById('currentFileSection').style.display = 'none';
        }
    }

    // Handle Delete
    function handleDelete(messageId) {
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

    function updatePagination(totalPages, currentPage) {
        const paginationContainer = document.getElementById('pagination-container');
        paginationContainer.innerHTML = '';

        for (let page = 1; page <= totalPages; page++) {
            const pageButton = document.createElement('button');
            pageButton.textContent = page;
            pageButton.classList.add('pagination-button');
            if (page === currentPage) {
                pageButton.classList.add('active');
            }
            pageButton.addEventListener('click', () => fetchUserData('', '', page));
            paginationContainer.appendChild(pageButton);
        }
    }

    document.getElementById('searchButton').addEventListener('click', function() {
        const searchCriteria = document.getElementById('searchCriteria').value;
        const searchTerm = document.getElementById('searchTerm').value;
        fetchUserData(searchCriteria, searchTerm);
    });

    document.getElementById('recordCountSelect').addEventListener('change', function() {
        fetchUserData();
    });

    // Close modals
    document.getElementById('closeDetailModal').addEventListener('click', function () {
        document.getElementById('detailModal').style.display = 'none';
    });

    document.getElementById('closeModal').addEventListener('click', function() {
        document.getElementById('editModal').style.display = 'none';
        document.getElementById('editForm').reset();
    });
});
