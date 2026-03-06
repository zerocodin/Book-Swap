<?php
class User {
    private $conn;
    private $table_name = "users";
    
    public $id;
    public $name;
    public $email;
    public $role;
    public $profile_image;
    public $password_hash;
    public $is_verified;
    public $created_at;
    public $updated_at;
    public $otp_code;
    public $otp_expiry;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                (name, email, role, password_hash, is_verified, otp_code, otp_expiry)
                VALUES (:name, :email, :role, :password_hash, :is_verified, :otp_code, :otp_expiry)";
        
        $stmt = $this->conn->prepare($query);
        
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->email = htmlspecialchars(strip_tags($this->email));
        
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":role", $this->role);
        $stmt->bindParam(":password_hash", $this->password_hash);
        $stmt->bindParam(":is_verified", $this->is_verified, PDO::PARAM_BOOL);
        $stmt->bindParam(":otp_code", $this->otp_code);
        $stmt->bindParam(":otp_expiry", $this->otp_expiry);
        
        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }
    
    public function emailExists() {
        $query = "SELECT id, name, email, role, password_hash, is_verified, otp_code, otp_expiry
                FROM " . $this->table_name . " WHERE email = ? LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->email);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->name = $row['name'];
            $this->email = $row['email'];
            $this->role = $row['role'];
            $this->password_hash = $row['password_hash'];
            $this->is_verified = $row['is_verified'];
            $this->otp_code = $row['otp_code'];
            $this->otp_expiry = $row['otp_expiry'];
            return true;
        }
        return false;
    }
    
    public function updateProfileImage() {
        $query = "UPDATE " . $this->table_name . " SET profile_image = :profile_image WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":profile_image", $this->profile_image);
        $stmt->bindParam(":id", $this->id);
        return $stmt->execute();
    }
    
    public function verify() {
        $query = "UPDATE " . $this->table_name . "
                SET is_verified = 1, otp_code = NULL, otp_expiry = NULL
                WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        
        return $stmt->execute();
    }
    
    public function updateOTP() {
        $query = "UPDATE " . $this->table_name . "
                SET otp_code = :otp_code, otp_expiry = :otp_expiry
                WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":otp_code", $this->otp_code);
        $stmt->bindParam(":otp_expiry", $this->otp_expiry);
        $stmt->bindParam(":id", $this->id);
        
        return $stmt->execute();
    }
    
    public function updatePassword() {
        $query = "UPDATE " . $this->table_name . "
                SET password_hash = :password_hash, otp_code = NULL, otp_expiry = NULL
                WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":password_hash", $this->password_hash);
        $stmt->bindParam(":id", $this->id);
        
        return $stmt->execute();
    }
    
    public function getPermissions() {
        $query = "SELECT p.permission_key, arp.granted
                FROM admin_role_permissions arp
                JOIN admin_permissions p ON arp.permission_id = p.id
                WHERE arp.role = :role";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":role", $this->role);
        $stmt->execute();
        
        $permissions = [];
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $permissions[$row['permission_key']] = $row['granted'];
        }
        
        return $permissions;
    }
}
?>