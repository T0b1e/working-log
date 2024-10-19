<?php
require_once '../../config/db.php';

$data = json_decode(file_get_contents("php://input"), true);
$file_id = $data['file_id'];
$new_name = $data['new_name'];

$db = (new Database())->connect();

// Get current file info from the database
$stmt = $db->prepare("SELECT * FROM files WHERE file_id = :file_id");
$stmt->execute([':file_id' => $file_id]);
$file = $stmt->fetch();

if ($file) {
    $old_path = $file['file_path'];
    $new_path = "../uploads/" . $new_name;

    // Rename the file on the file system
    if (rename($old_path, $new_path)) {
        // Update the file path in the database
        $stmt = $db->prepare("UPDATE files SET file_name = :file_name, file_path = :file_path WHERE file_id = :file_id");
        $stmt->execute([
            ':file_name' => $new_name,
            ':file_path' => $new_path,
            ':file_id' => $file_id
        ]);

        // Update cache
        $json_file = '../../uploads/files_cache.json';
        $cache_data = json_decode(file_get_contents($json_file), true);
        foreach ($cache_data as &$cache_file) {
            if ($cache_file['file_id'] == $file_id) {
                $cache_file['file_name'] = $new_name;
                $cache_file['file_path'] = $new_path;
            }
        }
        file_put_contents($json_file, json_encode($cache_data));

        echo json_encode(['success' => true, 'files' => $cache_data]);
    } else {
        echo json_encode(['success' => false, 'message' => 'File rename failed']);
    }
}
?>
