<?php
require_once '../../config/db.php';

$message_id = $_GET['id'];

try {
    $db = (new Database())->connect();

    $query = "
        SELECT m.*, f.file_name 
        FROM messages m
        LEFT JOIN message_files mf ON m.message_id = mf.message_id
        LEFT JOIN files f ON mf.file_id = f.file_id
        WHERE m.message_id = :message_id
    ";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':message_id', $message_id);
    $stmt->execute();

    $message = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($message) {
        echo json_encode(['success' => true, 'message' => $message]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Message not found']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
