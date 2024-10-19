<?php
require_once '../../config/db.php';

if (isset($_GET['id'])) {
    $messageId = $_GET['id'];
    
    $db = (new Database())->connect();

    // Prepare the SQL query to delete the message
    $query = "DELETE FROM messages WHERE message_id = :message_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':message_id', $messageId);

    // Execute the query and return the result as JSON
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Message deleted successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to delete message'
        ]);
    }
}
?>
