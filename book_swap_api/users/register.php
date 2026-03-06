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
$userName = isset($data['name']) ? $data['name'] : (isset($_POST['name']) ? $_POST['name'] : '');
$userEmail = isset($data['email']) ? $data['email'] : (isset($_POST['email']) ? $_POST['email'] : '');
$userPassword = isset($data['password_hash']) ? $data['password_hash'] : (isset($_POST['password_hash']) ? $_POST['password_hash'] : '');

if (empty($userName) || empty($userEmail) || empty($userPassword)) {
    echo json_encode(array("success"=> false, "message"=> "All fields are required"));
    exit;
}

// Generate 6-digit OTP and set expiry (10 minutes)
$otp = rand(100000, 999999);
$otpExpiry = date('Y-m-d H:i:s', strtotime('+1 minutes'));
$hashedPassword = md5($userPassword);

// Check if email already exists using prepared statement
$checkEmailQuery = $connectNow->prepare("SELECT * FROM users WHERE email = ?");
$checkEmailQuery->bind_param("s", $userEmail);
$checkEmailQuery->execute();
$checkResult = $checkEmailQuery->get_result();

if($checkResult->num_rows > 0) {
    echo json_encode(array("success"=> false, "message"=> "Email already exists"));
    exit;
}

// Insert user with OTP (not verified yet) using prepared statement
$sqlQuery = $connectNow->prepare("INSERT INTO users (name, email, password_hash, otp_code, otp_expiry, is_verified) VALUES (?, ?, ?, ?, ?, false)");
$sqlQuery->bind_param("sssss", $userName, $userEmail, $hashedPassword, $otp, $otpExpiry);

if($sqlQuery->execute()) {
    // Send OTP email using PHPMailer
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'bookswapcommunity@gmail.com';
        //Enter your 16 digit mail password to send email
        $mail->Password   = 'tqya kzyt lqnt uuxg';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('bookswapcommunity@gmail.com', 'Book Swap Community');
        $mail->addAddress($userEmail, $userName);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Your OTP Code for Book Swaps';
        $mail->Body    = "
            <h2>Welcome to Book Swaps, $userName!</h2>
            <p>Your OTP verification code is: <strong>$otp</strong></p>
            <p>This code will expire in 1 minutes.</p>
            <p>Thank you for joining our community!</p>
        ";
        
        $mail->AltBody = "Welcome to Book Swaps! Your OTP code is: $otp. This code expires in 1 minutes.";

        $mail->send();
        
        echo json_encode(array(
            "success"=> true, 
            "message"=> "OTP sent to your email",
            "requires_otp" => true,
            "email" => $userEmail
        ));
        
    } catch (Exception $e) {
        echo json_encode(array(
            "success"=> true, 
            "message"=> "Registered but failed to send OTP: " . $e->getMessage(),
            "requires_otp" => true,
            "email" => $userEmail
        ));
    }
} else {
    echo json_encode(array("success"=> false, "message"=> "Error: " . $connectNow->error));
}

$checkEmailQuery->close();
$sqlQuery->close();
?>