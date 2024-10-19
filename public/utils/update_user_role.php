<?php
require_once '../../config/db.php';

try {
    $db = (new Database())->connect();

    // Get user_id and new role from request
    $user_id = $_POST['user_id'];
    $new_role = $_POST['role'];

    $query = "UPDATE users SET role = :role WHERE user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':role', $new_role);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();

    echo json_encode(['success' => true, 'message' => 'Role updated successfully']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
