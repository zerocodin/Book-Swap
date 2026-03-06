<?php
class UserManagement {
    private $conn;
    private $table_name = "users";
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function readAll() {
        $query = "SELECT u.*, up.student_id, up.department, up.current_location, up.profile_image as user_profile_image
                FROM " . $this->table_name . " u
                LEFT JOIN user_profiles up ON u.id = up.user_id
                ORDER BY u.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $users = [];
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Initialize profile_image if not set in users table
            if (!isset($row['profile_image']) || empty($row['profile_image'])) {
                $row['profile_image'] = 'default.png';
            }
            
            // For user role, prioritize user_profiles image
            if($row['role'] === 'user' && !empty($row['user_profile_image'])) {
                $row['profile_image'] = $row['user_profile_image'];
            }
            
            // Ensure is_verified is properly set
            if (!isset($row['is_verified'])) {
                $row['is_verified'] = 0;
            }
            
            // Format created_at if needed
            if (isset($row['created_at'])) {
                $row['created_at'] = date('Y-m-d H:i:s', strtotime($row['created_at']));
            }
            
            // Remove the duplicate field
            unset($row['user_profile_image']);
            
            $users[] = $row;
        }
        
        return $users;
    }
    
    // Rest of your code remains the same...
    public function delete($id) {
        // First get the profile image to delete the file
        $query = "SELECT profile_image FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($user && !empty($user['profile_image']) && $user['profile_image'] !== 'default.png') {
            $file_path = '../../profile_images/' . $user['profile_image'];
            if(file_exists($file_path)) {
                unlink($file_path);
            }
        }
        
        // Also check user_profiles for user role images
        $query = "SELECT profile_image FROM user_profiles WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $id);
        $stmt->execute();
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($profile && !empty($profile['profile_image']) && $profile['profile_image'] !== 'default.png') {
            $file_path = '../../profile_images/' . $profile['profile_image'];
            if(file_exists($file_path)) {
                unlink($file_path);
            }
        }
        
        // Delete the user (cascade will delete related records)
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        return $stmt->execute();
    }
    
    public function updateRole($id, $role) {
        $query = "UPDATE " . $this->table_name . " SET role = :role WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":role", $role);
        $stmt->bindParam(":id", $id);
        
        if($stmt->execute()) {
            // If role changed to admin/super_admin, ensure admin_metadata exists
            if($role === 'admin' || $role === 'super_admin') {
                $check_query = "SELECT id FROM admin_metadata WHERE user_id = :user_id";
                $check_stmt = $this->conn->prepare($check_query);
                $check_stmt->bindParam(":user_id", $id);
                $check_stmt->execute();
                
                if($check_stmt->rowCount() == 0) {
                    $insert_query = "INSERT INTO admin_metadata (user_id, admin_since) VALUES (:user_id, NOW())";
                    $insert_stmt = $this->conn->prepare($insert_query);
                    $insert_stmt->bindParam(":user_id", $id);
                    $insert_stmt->execute();
                }
            }
            
            // If role changed to user, ensure user_profiles exists
            if($role === 'user') {
                $check_query = "SELECT id FROM user_profiles WHERE user_id = :user_id";
                $check_stmt = $this->conn->prepare($check_query);
                $check_stmt->bindParam(":user_id", $id);
                $check_stmt->execute();
                
                if($check_stmt->rowCount() == 0) {
                    $insert_query = "INSERT INTO user_profiles (user_id) VALUES (:user_id)";
                    $insert_stmt = $this->conn->prepare($insert_query);
                    $insert_stmt->bindParam(":user_id", $id);
                    $insert_stmt->execute();
                }
            }
            
            return true;
        }
        
        return false;
    }
}
?>