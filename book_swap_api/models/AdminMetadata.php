<?php
class AdminMetadata {
    private $conn;
    private $table_name = "admin_metadata";
    
    public $id;
    public $user_id;
    public $admin_since;
    public $access_level;
    public $notes;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function read() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE user_id = ? LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->user_id);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->admin_since = $row['admin_since'];
            $this->access_level = $row['access_level'];
            $this->notes = $row['notes'];
            return true;
        }
        return false;
    }
}
?>