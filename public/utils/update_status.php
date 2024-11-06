<?php
require_once '../../config/db.php';

try {
    // Connect to the database
    $db = (new Database())->connect();

    // Get the current date to check overdue status
    $currentDate = date('Y-m-d');

    // Prepare an update query to set status to 'เลยกำหนด' for overdue 'กำลังดำเนินการ' tasks
    $updateStatusQuery = "
        UPDATE messages
        SET status = 'เลยกำหนด'
        WHERE status = 'กำลังดำเนินการ' AND end_date < :currentDate
    ";
    $updateStatusStmt = $db->prepare($updateStatusQuery);
    $updateStatusStmt->bindParam(':currentDate', $currentDate, PDO::PARAM_STR);

    // Execute the update query
    $updateStatusStmt->execute();

    // Log the number of records updated
    $updatedRecords = $updateStatusStmt->rowCount();
    echo json_encode([
        'success' => true,
        'message' => "Status updated for $updatedRecords records"
    ]);

} catch (PDOException $e) {
    // Handle database errors
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    // Handle general errors
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
