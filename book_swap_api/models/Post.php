<?php
class Post {
    private $conn;
    private $table_name = "posts";
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function readAll() {
        $query = "SELECT p.*, 
                        u.name as author_name, 
                        u.email as author_email,
                        (SELECT image_path FROM post_images WHERE post_id = p.id AND is_primary = 1 LIMIT 1) as image
                FROM " . $this->table_name . " p
                JOIN users u ON p.user_id = u.id
                ORDER BY p.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }
    
    public function delete($id) {
        // First, delete associated images from filesystem
        $query = "SELECT image_path FROM post_images WHERE post_id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $file_path = "C:/xampp/htdocs/book_swap_api/post_images/" . $row['image_path'];
            if(file_exists($file_path)) {
                unlink($file_path);
            }
        }
        
        // Delete from database (images will be deleted by CASCADE)
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        return $stmt->execute();
    }
    
    public function getImages($post_id) {
        $query = "SELECT * FROM post_images WHERE post_id = :post_id ORDER BY is_primary DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":post_id", $post_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>