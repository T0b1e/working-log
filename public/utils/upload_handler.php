<?php
require_once '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Start session if it's not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Get the actual user_id from session or cookies
    $user_id = isset($_COOKIE['user_id']) ? intval($_COOKIE['user_id']) : null;

    if (!$user_id) {
        echo json_encode(['success' => false, 'message' => 'User not authenticated']);
        exit();
    }

    // Get POST data
    $title = $_POST['title'];
    $description = $_POST['description'];
    $priority = $_POST['priority'];  // Priority in Thai
    $status = $_POST['status'];      // Status in Thai
    $body = $_POST['body'];          // No need to json_decode unless it's an actual JSON string
    $file = isset($_FILES['fileToUpload']) ? $_FILES['fileToUpload'] : null;

    try {
        // Create database connection
        $db = (new Database())->connect();
        $db->beginTransaction(); // Start a transaction

        // Insert into messages table
        $stmt = $db->prepare("INSERT INTO messages (user_id, title, description, priority, status, body) 
                              VALUES (:user_id, :title, :description, :priority, :status, :body)");
        $stmt->execute([
            ':user_id' => $user_id, // Use actual user_id from session/cookies
            ':title' => $title,
            ':description' => $description,
            ':priority' => $priority,
            ':status' => $status,
            ':body' => $body
        ]);

        // Get the message_id of the inserted message
        $message_id = $db->lastInsertId();

        // Check if a file was uploaded
        if ($file && $file['name']) {
            $target_dir = "../../uploads/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true); // Create directory if it doesn't exist
            }

            $file_name = basename($file["name"]);
            $target_file = $target_dir . $file_name;

            // Move uploaded file to target directory
            if (move_uploaded_file($file["tmp_name"], $target_file)) {
                // Insert file metadata into files table
                $stmt = $db->prepare("INSERT INTO files (user_id, file_name, file_path, file_type, uploaded_at) 
                                      VALUES (:user_id, :file_name, :file_path, :file_type, NOW())");
                $stmt->execute([
                    ':user_id' => $user_id, // Use actual user_id from session/cookies
                    ':file_name' => $file_name,
                    ':file_path' => $target_file,
                    ':file_type' => $file["type"]
                ]);

                // Get the file_id of the inserted file
                $file_id = $db->lastInsertId();

                // Link the file to the message in the message_files table
                $stmt = $db->prepare("INSERT INTO message_files (message_id, file_id) VALUES (:message_id, :file_id)");
                $stmt->execute([
                    ':message_id' => $message_id,
                    ':file_id' => $file_id
                ]);
            } else {
                throw new Exception('File upload failed');
            }
        }

        // Commit the transaction
        $db->commit();

        // Return success response
        echo json_encode(['success' => true, 'message' => 'Data inserted successfully']);
    } catch (Exception $e) {
        // Rollback the transaction if something goes wrong
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
