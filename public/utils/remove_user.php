<?php
require_once '../../config/db.php';

try {
    $db = (new Database())->connect();

    // Get user_id from request
    $user_id = $_POST['user_id'];

    $query = "DELETE FROM users WHERE user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();

    echo json_encode(['success' => true, 'message' => 'User removed successfully']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
