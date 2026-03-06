<?php
error_reporting(0); 
ini_set('display_errors', 0);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include '../connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get JSON input
    $input = file_get_contents("php://input");
    $data = json_decode($input);
    
    if ($data === null) {
        echo json_encode(array("success" => false, "message" => "Invalid JSON data"));
        exit();
    }
    
    if (empty($data->user_id) || empty($data->otp)) {
        echo json_encode(array("success" => false, "message" => "Missing required fields"));
        exit();
    }
    
    $user_id = $data->user_id;
    $otp = $data->otp;
    $currentTime = date('Y-m-d H:i:s');
    
    try {
        if (!isset($connectNow) || $connectNow->connect_error) {
            throw new Exception("Database connection failed");
        }
        
        // First, check if OTP exists and is not expired
        $verify_query = "SELECT * FROM users WHERE id = ? AND otp_code = ? AND otp_expiry > ?";
        $verify_stmt = $connectNow->prepare($verify_query);
        
        if (!$verify_stmt) {
            throw new Exception("Prepare failed: " . $connectNow->error);
        }
        
        $verify_stmt->bind_param("iss", $user_id, $otp, $currentTime);
        $verify_stmt->execute();
        $verify_result = $verify_stmt->get_result();
        
        if ($verify_result->num_rows === 0) {
            // Check if OTP exists but expired
            $check_expired_query = "SELECT otp_expiry FROM users WHERE id = ? AND otp_code = ?";
            $check_expired_stmt = $connectNow->prepare($check_expired_query);
            $check_expired_stmt->bind_param("is", $user_id, $otp);
            $check_expired_stmt->execute();
            $expired_result = $check_expired_stmt->get_result();
            
            if ($expired_result->num_rows > 0) {
                $row = $expired_result->fetch_assoc();
                echo json_encode(array(
                    "success" => false, 
                    "message" => "OTP has expired. Please request a new one.",
                    "expired_at" => $row['otp_expiry']
                ));
            } else {
                echo json_encode(array("success" => false, "message" => "Invalid OTP code"));
            }
            
            $check_expired_stmt->close();
            $verify_stmt->close();
            exit();
        }
        
        $verify_stmt->close();
        
        // Get the pending email change
        $get_pending_query = "SELECT new_email FROM pending_email_changes WHERE user_id = ? ORDER BY created_at DESC LIMIT 1";
        $get_pending_stmt = $connectNow->prepare($get_pending_query);
        $get_pending_stmt->bind_param("i", $user_id);
        $get_pending_stmt->execute();
        $pending_result = $get_pending_stmt->get_result();
        
        if ($pending_result->num_rows === 0) {
            echo json_encode(array("success" => false, "message" => "No pending email change found"));
            $get_pending_stmt->close();
            exit();
        }
        
        $pending_data = $pending_result->fetch_assoc();
        $new_email = $pending_data['new_email'];
        $get_pending_stmt->close();
        
        // Update user's email
        $update_query = "UPDATE users SET email = ?, otp_code = NULL, otp_expiry = NULL, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $update_stmt = $connectNow->prepare($update_query);
        
        if (!$update_stmt) {
            throw new Exception("Prepare failed: " . $connectNow->error);
        }
        
        $update_stmt->bind_param("si", $new_email, $user_id);
        
        if ($update_stmt->execute()) {
            // Delete pending email change
            $delete_pending_query = "DELETE FROM pending_email_changes WHERE user_id = ?";
            $delete_stmt = $connectNow->prepare($delete_pending_query);
            $delete_stmt->bind_param("i", $user_id);
            $delete_stmt->execute();
            $delete_stmt->close();
            
            echo json_encode(array("success" => true, "message" => "Email updated successfully"));
        } else {
            echo json_encode(array("success" => false, "message" => "Failed to update email: " . $update_stmt->error));
        }
        
        $update_stmt->close();
        
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