<?php
error_reporting(0);
ini_set('display_errors', 0);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include '../connection.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $input = file_get_contents("php://input");
    $data = json_decode($input);
    
    if ($data === null) {
        echo json_encode(array("success" => false, "message" => "Invalid JSON data"));
        exit();
    }
    
    // Accept either user_id alone or both user_id and email
    if (empty($data->user_id)) {
        echo json_encode(array("success" => false, "message" => "Missing user ID"));
        exit();
    }
    
    $user_id = $data->user_id;
    $provided_email = $data->email ?? ''; // Get email if provided
    
    try {
        if (!isset($connectNow) || $connectNow->connect_error) {
            throw new Exception("Database connection failed");
        }
        
        // Check if there's a pending email change and user exists
        $user_query = "SELECT u.name, u.email, p.new_email, u.otp_expiry
                      FROM users u 
                      LEFT JOIN pending_email_changes p ON u.id = p.user_id 
                      WHERE u.id = ? 
                      ORDER BY p.created_at DESC LIMIT 1";
        $user_stmt = $connectNow->prepare($user_query);
        $user_stmt->bind_param("i", $user_id);
        $user_stmt->execute();
        $user_result = $user_stmt->get_result();
        
        if ($user_result->num_rows === 0) {
            echo json_encode(array("success" => false, "message" => "User not found"));
            $user_stmt->close();
            exit();
        }
        
        $user_data = $user_result->fetch_assoc();
        $name = $user_data['name'];
        $current_email = $user_data['email'];
        $new_email = $user_data['new_email'];
        $otp_expiry = $user_data['otp_expiry'];
        $user_stmt->close();
        
        // If email was provided in request, use it for verification
        $email_to_use = empty($provided_email) ? $new_email : $provided_email;
        
        if (empty($email_to_use)) {
            echo json_encode(array("success" => false, "message" => "No email address found for OTP resend"));
            exit();
        }
        
        // Verify that the provided email matches the pending email (if provided)
        if (!empty($provided_email) && $provided_email !== $new_email) {
            echo json_encode(array("success" => false, "message" => "Email does not match pending email change"));
            exit();
        }
        
        // Check if email is already verified
        $check_verified_query = "SELECT email FROM users WHERE id = ? AND email = ?";
        $check_verified_stmt = $connectNow->prepare($check_verified_query);
        $check_verified_stmt->bind_param("is", $user_id, $email_to_use);
        $check_verified_stmt->execute();
        $verified_result = $check_verified_stmt->get_result();
        
        if ($verified_result->num_rows > 0) {
            echo json_encode(array("success" => false, "message" => "Email is already verified"));
            $check_verified_stmt->close();
            exit();
        }
        $check_verified_stmt->close();
        
        // Generate new OTP
        $otp = rand(100000, 999999);
        $otp_expiry = date('Y-m-d H:i:s', strtotime('+1 minutes'));
        
        // Update OTP in users table
        $update_otp_query = "UPDATE users SET otp_code = ?, otp_expiry = ? WHERE id = ?";
        $update_stmt = $connectNow->prepare($update_otp_query);
        
        if (!$update_stmt) {
            throw new Exception("Prepare failed: " . $connectNow->error);
        }
        
        $update_stmt->bind_param("ssi", $otp, $otp_expiry, $user_id);
        
        if ($update_stmt->execute()) {
            // Send OTP email
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
                $mail->addAddress($email_to_use, $name);
                
                $mail->isHTML(true);
                $mail->Subject = 'New OTP for Email Verification';
                $mail->Body    = "
                    <h2>New OTP Code</h2>
                    <p>Hello $name,</p>
                    <p>Your new OTP verification code is: <strong>$otp</strong></p>
                    <p>This code will expire in 1 minute.</p>
                    <p>If you didn't request this, please ignore this email.</p>
                ";
                
                $mail->AltBody = "Your new OTP code is: $otp. Expires in 1 minute.";
                
                $mail->send();
                
                echo json_encode(array(
                    "success" => true, 
                    "message" => "New OTP sent to your email",
                    "email" => $email_to_use
                ));
                
            } catch (Exception $e) {
                echo json_encode(array("success" => false, "message" => "Error sending email: " . $e->getMessage()));
            }
        } else {
            echo json_encode(array("success" => false, "message" => "Failed to generate OTP: " . $update_stmt->error));
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