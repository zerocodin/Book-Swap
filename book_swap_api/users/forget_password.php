<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

include '../connection.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';

// Get JSON input
$data = json_decode(file_get_contents("php://input"), true);
$userEmail = isset($data['email']) ? $data['email'] : (isset($_POST['email']) ? $_POST['email'] : '');

if (empty($userEmail)) {
    echo json_encode(array("success"=> false, "message"=> "Email is required"));
    exit;
}

// Generate 6-digit OTP and set expiry (1 minute)
$otp = rand(100000, 999999);
$otpExpiry = date('Y-m-d H:i:s', strtotime('+1 minute'));

// Check if email exists using prepared statement
$checkEmailQuery = $connectNow->prepare("SELECT id, name FROM users WHERE email = ?");
$checkEmailQuery->bind_param("s", $userEmail);
$checkEmailQuery->execute();
$checkResult = $checkEmailQuery->get_result();

if($checkResult->num_rows <= 0) {
    echo json_encode(array("success"=> false, "message"=> "User not found"));
    exit;
}

$user = $checkResult->fetch_assoc();
$userId = $user['id'];
$userName = $user['name'];

// Store OTP in database for verification (without changing password yet)
$updateQuery = $connectNow->prepare("UPDATE users SET otp_code = ?, otp_expiry = ? WHERE email = ?");
$updateQuery->bind_param("sss", $otp, $otpExpiry, $userEmail);

if($updateQuery->execute()) {
    // Send OTP email using PHPMailer
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'bookswapcommunity@gmail.com';
        //Enter your 16 digit mail password to send email
        $mail->Password   = 'your mail password';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('bookswapcommunity@gmail.com', 'Book Swap Community');
        $mail->addAddress($userEmail, $userName);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset OTP - Book Swaps';
        $mail->Body    = "
            <h2>Password Reset Request</h2>
            <p>Hello $userName,</p>
            <p>Your OTP verification code for password reset is: <strong>$otp</strong></p>
            <p>This code will expire in 1 minutes.</p>
            <p>If you didn't request this reset, please ignore this email.</p>
        ";
        
        $mail->AltBody = "Password Reset OTP: $otp. This code expires in 1 minutes.";

        $mail->send();
        
        echo json_encode(array(
            "success"=> true, 
            "message"=> "OTP sent to your email",
            "requires_otp" => true,
            "email" => $userEmail
        ));
        
    } catch (Exception $e) {
        echo json_encode(array(
            "success"=> false, 
            "message"=> "Failed to send OTP: " . $e->getMessage()
        ));
    }
} else {
    echo json_encode(array("success"=> false, "message"=> "Error: " . $connectNow->error));
}

$checkEmailQuery->close();
$updateQuery->close();

?>
