<?php
require_once '../../config/db.php';

// Check if the request is POST and if the message ID is provided via POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message_id'])) {
    $messageId = $_POST['message_id'];

    // Connect to the database
    $db = (new Database())->connect();

    // Sanitize and collect the updated form data (POST)
    $title = htmlspecialchars($_POST['title'] ?? '', ENT_QUOTES, 'UTF-8');
    $description = htmlspecialchars($_POST['description'] ?? '', ENT_QUOTES, 'UTF-8');
    $priority = htmlspecialchars($_POST['priority'] ?? '', ENT_QUOTES, 'UTF-8');
    $status = htmlspecialchars($_POST['status'] ?? '', ENT_QUOTES, 'UTF-8');
    $body = htmlspecialchars($_POST['body'] ?? '', ENT_QUOTES, 'UTF-8');

    // Initialize variables for file upload
    $fileName = '';
    $uploadSuccess = true;

    // Check if a file is uploaded
    if (isset($_FILES['fileToUpload']) && $_FILES['fileToUpload']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['fileToUpload']['tmp_name'];
        $fileName = basename($_FILES['fileToUpload']['name']);
        $uploadDir = '../../uploads/';
        $destPath = $uploadDir . $fileName;

        // Validate file type (optional - add restrictions if needed)
        $allowedFileTypes = ['pdf', 'jpg', 'jpeg', 'png', 'docx'];
        $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
        
        if (!in_array($fileExtension, $allowedFileTypes)) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid file type. Allowed types are: ' . implode(', ', $allowedFileTypes)
            ]);
            exit();
        }

        // Move the uploaded file to the destination folder
        if (!move_uploaded_file($fileTmpPath, $destPath)) {
            $uploadSuccess = false;
            echo json_encode([
                'success' => false,
                'message' => 'Failed to upload file.'
            ]);
            exit();
        }
    }

    if ($uploadSuccess) {
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
    }
} else {
    // If request method is not POST or message ID is missing, return an error
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request or Message ID is missing'
    ]);
}
?>
