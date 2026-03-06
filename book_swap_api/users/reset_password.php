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
$userEmail = isset($data['email']) ? $data['email'] : (isset($_POST['email']) ? $_POST['email'] : '');
$otp = isset($data['otp']) ? $data['otp'] : (isset($_POST['otp']) ? $_POST['otp'] : '');
// CHANGE THIS LINE - receive the raw password, not password_hash
$newPassword = isset($data['password']) ? $data['password'] : (isset($_POST['password']) ? $_POST['password'] : '');

if (empty($userEmail) || empty($otp) || empty($newPassword)) {
    echo json_encode(array("success"=> false, "message"=> "All fields are required"));
    exit;
}

$currentTime = date('Y-m-d H:i:s');
// Hash the received password
$hashedPassword = md5($newPassword);

// Verify OTP and check expiry
$verifyQuery = $connectNow->prepare("SELECT * FROM users WHERE email = ? AND otp_code = ? AND otp_expiry > ?");
$verifyQuery->bind_param("sss", $userEmail, $otp, $currentTime);
$verifyQuery->execute();
$verifyResult = $verifyQuery->get_result();

if($verifyResult->num_rows > 0) {
    // OTP is valid and not expired - update password and clear OTP fields
    $updateQuery = $connectNow->prepare("UPDATE users SET password_hash = ?, otp_code = NULL, otp_expiry = NULL WHERE email = ?");
    $updateQuery->bind_param("ss", $hashedPassword, $userEmail);
    
    if($updateQuery->execute()) {
        echo json_encode(array("success"=> true, "message"=> "Password reset successfully"));
    } else {
        echo json_encode(array("success"=> false, "message"=> "Error updating password"));
    }
    
    $updateQuery->close();
} else {
    // Check if OTP exists but expired
    $checkExpiredQuery = $connectNow->prepare("SELECT * FROM users WHERE email = ? AND otp_code = ?");
    $checkExpiredQuery->bind_param("ss", $userEmail, $otp);
    $checkExpiredQuery->execute();
    $expiredResult = $checkExpiredQuery->get_result();
    
    if($expiredResult->num_rows > 0) {
        echo json_encode(array("success"=> false, "message"=> "OTP has expired. Please request a new one."));
    } else {
        echo json_encode(array("success"=> false, "message"=> "Invalid OTP"));
    }
    
    $checkExpiredQuery->close();
}

$verifyQuery->close();
?>