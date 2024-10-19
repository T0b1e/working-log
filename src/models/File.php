<?php
require_once '../config/db.php';

class File {
    private $conn;
    private $table = 'files';

    public $file_id;
    public $user_id;
    public $file_name;
    public $file_path;
    public $file_type;
    public $uploaded_at;

    // Constructor to initialize the database connection
    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    // Upload a new file
    public function upload() {
        $query = "INSERT INTO $this->table (user_id, file_name, file_path, file_type) 
                  VALUES (:user_id, :file_name, :file_path, :file_type)";
        $stmt = $this->conn->prepare($query);

        // Sanitize and bind data
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':file_name', $this->file_name);
        $stmt->bindParam(':file_path', $this->file_path);
        $stmt->bindParam(':file_type', $this->file_type);

        return $stmt->execute();
    }

    // Read file by ID
    public function read($file_id) {
        $query = "SELECT * FROM $this->table WHERE file_id = :file_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':file_id', $file_id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Delete file
    public function delete($file_id) {
        $query = "DELETE FROM $this->table WHERE file_id = :file_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':file_id', $file_id);

        return $stmt->execute();
    }
}
?>
