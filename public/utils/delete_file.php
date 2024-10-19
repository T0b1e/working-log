<?php
require_once '../../config/db.php';

$data = json_decode(file_get_contents("php://input"), true);
$file_id = $data['file_id'];

$db = (new Database())->connect();

// Get file info from the database
$stmt = $db->prepare("SELECT * FROM files WHERE file_id = :file_id");
$stmt->execute([':file_id' => $file_id]);
$file = $stmt->fetch();

if ($file) {
    $file_path = $file['file_path'];

    // Delete the file from the file system
    if (unlink($file_path)) {
        // Remove from the database
        $stmt = $db->prepare("DELETE FROM files WHERE file_id = :file_id");
        $stmt->execute([':file_id' => $file_id]);

        // Update cache
        $json_file = '../../uploads/files_cache.json';
        $cache_data = json_decode(file_get_contents($json_file), true);
        $cache_data = array_filter($cache_data, function($f) use ($file_id) {
            return $f['file_id'] != $file_id;
        });
        file_put_contents($json_file, json_encode($cache_data));

        echo json_encode(['success' => true, 'files' => $cache_data]);
    } else {
        echo json_encode(['success' => false, 'message' => 'File delete failed']);
    }
}
?>
