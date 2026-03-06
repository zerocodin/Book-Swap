<?php
error_reporting(0);
ini_set('display_errors', 0);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include '../connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $input = file_get_contents("php://input");
    $data = json_decode($input);
    
    if ($data === null) {
        echo json_encode(array("success" => false, "message" => "Invalid JSON data"));
        exit();
    }
    
    // Only check for essential fields
    if (empty($data->user_id) || empty($data->current_password) || empty($data->new_password)) {
        echo json_encode(array("success" => false, "message" => "Missing required fields"));
        exit();
    }
    
    $user_id = $data->user_id;
    $current_password = $data->current_password;
    $new_password = $data->new_password;
    
    try {
        if (!isset($connectNow) || $connectNow->connect_error) {
            throw new Exception("Database connection failed");
        }
        
        // Get user's current password hash
        $query = "SELECT password_hash FROM users WHERE id = ?";
        $stmt = $connectNow->prepare($query);
        
        if (!$stmt) {
            throw new Exception("Prepare failed");
        }
        
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(array("success" => false, "message" => "User not found"));
            exit();
        }
        
        $user = $result->fetch_assoc();
        $current_password_hash = $user['password_hash'];
        
        // FIX: Use MD5 verification to match your existing system
        if (md5($current_password) !== $current_password_hash) {
            echo json_encode(array("success" => false, "message" => "Current password is incorrect"));
            exit();
        }
        
        // FIX: Hash the new password using MD5 (to match your existing system)
        $new_password_hash = md5($new_password);
        
        $update_query = "UPDATE users SET password_hash = ? WHERE id = ?";
        $update_stmt = $connectNow->prepare($update_query);
        
        if (!$update_stmt) {
            throw new Exception("Prepare failed");
        }
        
        $update_stmt->bind_param("si", $new_password_hash, $user_id);
        
        if ($update_stmt->execute() && $update_stmt->affected_rows > 0) {
            echo json_encode(array("success" => true, "message" => "Password updated successfully"));
        } else {
            echo json_encode(array("success" => false, "message" => "Failed to update password"));
        }
        
        $update_stmt->close();
        $stmt->close();
        
    } catch (Exception $e) {
        echo json_encode(array("success" => false, "message" => "Server error: " . $e->getMessage()));
    }
} else {
    echo json_encode(array("success" => false, "message" => "Method not allowed"));
}

if (isset($connectNow)) {
    $connectNow->close();
}
?>