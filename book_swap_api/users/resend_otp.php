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
$email = isset($data['email']) ? $data['email'] : (isset($_POST['email']) ? $_POST['email'] : '');

if (empty($email)) {
    echo json_encode(array("success"=> false, "message"=> "Email is required"));
    exit;
}

// Generate new OTP and expiry (10 minutes)
$newOtp = rand(100000, 999999);
$otpExpiry = date('Y-m-d H:i:s', strtotime('+1 minutes'));

// Update the user with new OTP using prepared statement
// $updateQuery = $connectNow->prepare("UPDATE users SET otp_code = ?, otp_expiry = ? WHERE email = ? AND is_verified = false");
$updateQuery = $connectNow->prepare("UPDATE users SET otp_code = ?, otp_expiry = ? WHERE email = ?");
$updateQuery->bind_param("sss", $newOtp, $otpExpiry, $email);
$updateQuery->execute();

if($updateQuery->affected_rows > 0) {
    // Get user name
    $getUserQuery = $connectNow->prepare("SELECT name FROM users WHERE email = ?");
    $getUserQuery->bind_param("s", $email);
    $getUserQuery->execute();
    $userResult = $getUserQuery->get_result();
    $user = $userResult->fetch_assoc();
    $name = $user['name'];
    
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'bookswapcommunity@gmail.com';
        //Enter your 16 digit mail password to send email
        $mail->Password   = 'tqya kzyt lqnt uuxg';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('bookswapcommunity@gmail.com', 'Book Swap Community');
        $mail->addAddress($email, $name);
        
        $mail->isHTML(true);
        $mail->Subject = 'Your New OTP Code';
        $mail->Body    = "
            <h2>New OTP Code</h2>
            <p>Your new OTP verification code is: <strong>$newOtp</strong></p>
            <p>This code will expire in 1 minutes.</p>
        ";
        
        $mail->AltBody = "Your new OTP code is: $newOtp. Expires in 10 minutes.";

        $mail->send();
        
        echo json_encode(array("success"=> true, "message"=> "New OTP sent to your email"));
        
    } catch (Exception $e) {
        echo json_encode(array("success"=> false, "message"=> "Error sending email: " . $e->getMessage()));
    }
    
    $getUserQuery->close();
} else {
    echo json_encode(array("success"=> false, "message"=> "Error resending OTP or user already verified"));
}

$updateQuery->close();
?>