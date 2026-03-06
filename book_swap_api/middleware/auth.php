<?php
class Auth {
    private $conn;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function verifyToken($token) {
        if($token) {
            error_log("Verifying token: " . $token);
            
            // Your token format from login.php is: bin2hex(random_bytes(16)) . '_' . $user_id
            // So it looks like: "a1b2c3..._123"
            
            // Extract user_id from token
            $parts = explode('_', $token);
            if(count($parts) == 2) {
                $user_id = $parts[1];
                
                if(is_numeric($user_id)) {
                    $query = "SELECT id, email, role FROM users WHERE id = ?";
                    $stmt = $this->conn->prepare($query);
                    $stmt->execute([$user_id]);
                    
                    if($stmt->rowCount() > 0) {
                        $user = $stmt->fetch(PDO::FETCH_ASSOC);
                        error_log("Token verified for user: " . $user['email']);
                        return $user;
                    }
                }
            }
            
            // Fallback to old method if token is just numeric
            if(is_numeric($token)) {
                $query = "SELECT id, email, role FROM users WHERE id = ?";
                $stmt = $this->conn->prepare($query);
                $stmt->execute([$token]);
                
                if($stmt->rowCount() > 0) {
                    return $stmt->fetch(PDO::FETCH_ASSOC);
                }
            }
        }
        
        error_log("Token verification failed");
        return false;
    }
    
    public function generateToken($user_id) {
        // Generate token: random_hex_userid
        return bin2hex(random_bytes(16)) . '_' . $user_id;
    }
}
?>