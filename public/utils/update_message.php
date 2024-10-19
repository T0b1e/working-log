<?php
require_once '../../config/db.php';

// Check if the message ID is provided via GET request
if (isset($_GET['id'])) {
    $messageId = $_GET['id'];

    // Connect to the database
    $db = (new Database())->connect();

    // Collect the updated form data (POST)
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $priority = $_POST['priority'] ?? '';
    $status = $_POST['status'] ?? '';
    $body = $_POST['body'] ?? '';

    // Check if a file is uploaded
    $fileName = '';
    if (isset($_FILES['fileToUpload']) && $_FILES['fileToUpload']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['fileToUpload']['tmp_name'];
        $fileName = $_FILES['fileToUpload']['name'];
        $uploadDir = '../../uploads/';
        $destPath = $uploadDir . $fileName;

        // Move the uploaded file to the destination folder
        if (move_uploaded_file($fileTmpPath, $destPath)) {
            // If file is uploaded, set the file name to be updated in the database
            $fileName = $fileName;
        }
    }

    // Prepare the SQL query to update the message
    $query = "UPDATE messages SET title = :title, description = :description, priority = :priority, status = :status, body = :body";

    // Include the file name in the update query only if a new file was uploaded
    if (!empty($fileName)) {
        $query .= ", file_name = :file_name";
    }

    $query .= " WHERE message_id = :message_id";

    // Prepare the statement
    $stmt = $db->prepare($query);

    // Bind parameters
    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':priority', $priority);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':body', $body);
    $stmt->bindParam(':message_id', $messageId);

    // Bind the file name if a new file is uploaded
    if (!empty($fileName)) {
        $stmt->bindParam(':file_name', $fileName);
    }

    // Execute the query and return the result as JSON
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Message updated successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update message'
        ]);
    }
} else {
    // If message ID is not provided, return an error
    echo json_encode([
        'success' => false,
        'message' => 'Message ID is missing'
    ]);
}
?>
