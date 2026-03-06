<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

include '../connection.php';

// Get JSON input
$data = json_decode(file_get_contents("php://input"), true);
$email = isset($data['email']) ? $data['email'] : (isset($_POST['email']) ? $_POST['email'] : '');
$otp = isset($data['otp']) ? $data['otp'] : (isset($_POST['otp']) ? $_POST['otp'] : '');
$currentTime = date('Y-m-d H:i:s');

if (empty($email) || empty($otp)) {
    echo json_encode(array("success"=> false, "message"=> "Email and OTP are required"));
    exit;
}

// Use prepared statement to check OTP
$stmt = $connectNow->prepare("SELECT * FROM users WHERE email = ? AND otp_code = ? AND otp_expiry > ?");
$stmt->bind_param("sss", $email, $otp, $currentTime);
$stmt->execute();
$rslt = $stmt->get_result();

if($rslt->num_rows > 0) {
    // Update user as verified and clear OTP fields
    $updateStmt = $connectNow->prepare("UPDATE users SET is_verified = true, otp_code = NULL, otp_expiry = NULL WHERE email = ?");
    $updateStmt->bind_param("s", $email);
    
    if($updateStmt->execute()) {
        echo json_encode(array("success"=> true, "message"=> "Email verified successfully"));
    } else {
        echo json_encode(array("success"=> false, "message"=> "Error updating verification status"));
    }
    
    $updateStmt->close();
} else {
    // Check if OTP exists but expired
    $checkExpiredStmt = $connectNow->prepare("SELECT * FROM users WHERE email = ? AND otp_code = ?");
    $checkExpiredStmt->bind_param("ss", $email, $otp);
    $checkExpiredStmt->execute();
    $expiredResult = $checkExpiredStmt->get_result();
    
    if($expiredResult->num_rows > 0) {
        echo json_encode(array("success"=> false, "message"=> "OTP has expired. Please request a new one."));
    } else {
        echo json_encode(array("success"=> false, "message"=> "Invalid OTP"));
    }
    
    $checkExpiredStmt->close();
}

$stmt->close();
?>