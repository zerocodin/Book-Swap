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
    
    if (empty($data->user_id) || empty($data->new_email)) {
        echo json_encode(array("success" => false, "message" => "Missing required fields"));
        exit();
    }
    
    $user_id = $data->user_id;
    $new_email = $data->new_email;
    
    try {
        if (!isset($connectNow) || $connectNow->connect_error) {
            throw new Exception("Database connection failed");
        }
        
        // Check if new email already exists
        $check_email_query = "SELECT id FROM users WHERE email = ? AND id != ?";
        $check_stmt = $connectNow->prepare($check_email_query);
        
        if (!$check_stmt) {
            throw new Exception("Prepare failed");
        }
        
        $check_stmt->bind_param("si", $new_email, $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            echo json_encode(array("success" => false, "message" => "Email already exists"));
            $check_stmt->close();
            exit();
        }
        $check_stmt->close();
        
        // Get current user data
        $user_query = "SELECT name FROM users WHERE id = ?";
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
        $current_name = $user_data['name'];
        $user_stmt->close();
        
        // Generate OTP (1 minute expiry)
        $otp = rand(100000, 999999);
        $otp_expiry = date('Y-m-d H:i:s', strtotime('+1 minutes'));
        
        // Store OTP in users table
        $otp_query = "UPDATE users SET otp_code = ?, otp_expiry = ? WHERE id = ?";
        $otp_stmt = $connectNow->prepare($otp_query);
        
        if (!$otp_stmt) {
            throw new Exception("Prepare failed");
        }
        
        $otp_stmt->bind_param("ssi", $otp, $otp_expiry, $user_id);
        
        if ($otp_stmt->execute()) {
            // Store the pending email change
            $pending_email_query = "CREATE TABLE IF NOT EXISTS pending_email_changes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                new_email VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )";
            
            $connectNow->query($pending_email_query);
            
            // Delete any existing pending email change
            $delete_pending_query = "DELETE FROM pending_email_changes WHERE user_id = ?";
            $delete_stmt = $connectNow->prepare($delete_pending_query);
            $delete_stmt->bind_param("i", $user_id);
            $delete_stmt->execute();
            $delete_stmt->close();
            
            // Insert new pending email change
            $insert_pending_query = "INSERT INTO pending_email_changes (user_id, new_email) VALUES (?, ?)";
            $insert_stmt = $connectNow->prepare($insert_pending_query);
            $insert_stmt->bind_param("is", $user_id, $new_email);
            $insert_stmt->execute();
            $insert_stmt->close();
            
            // Send OTP email
            $mail = new PHPMailer(true);
            
            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'bookswapcommunity@gmail.com';
                //Enter your 16 digit mail password to send email
                $mail->Password   = 'your mail password';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;
                
                $mail->setFrom('bookswapcommunity@gmail.com', 'Book Swap Community');
                $mail->addAddress($new_email, $current_name);
                
                $mail->isHTML(true);
                $mail->Subject = 'Verify Your New Email Address';
                $mail->Body    = "
                    <h2>Email Change Verification</h2>
                    <p>Hello $current_name,</p>
                    <p>You requested to change your email address to this one.</p>
                    <p>Your OTP verification code is: <strong>$otp</strong></p>
                    <p>This code will expire in 1 minute.</p>
                    <p>If you didn't request this change, please ignore this email.</p>
                ";
                
                $mail->AltBody = "Your OTP for email change is: $otp. Expires in 1 minute.";
                
                $mail->send();
                
                echo json_encode(array(
                    "success" => true, 
                    "message" => "OTP sent to your new email address",
                    "email" => $new_email
                ));
                
            } catch (Exception $e) {
                echo json_encode(array("success" => false, "message" => "Error sending email: " . $e->getMessage()));
            }
        } else {
            echo json_encode(array("success" => false, "message" => "Failed to generate OTP"));
        }
        
        $otp_stmt->close();
        
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
