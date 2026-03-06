<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Fix the path - adjust based on where PHPMailer is installed
require_once 'C:/xampp/htdocs/book_swap_api/PHPMailer/src/Exception.php';
require_once 'C:/xampp/htdocs/book_swap_api/PHPMailer/src/PHPMailer.php';
require_once 'C:/xampp/htdocs/book_swap_api/PHPMailer/src/SMTP.php';

class Mailer {
    private $mail;
    
    public function __construct() {
        $this->mail = new PHPMailer(true);
        $this->mail->isSMTP();
        $this->mail->Host = 'smtp.gmail.com';
        $this->mail->SMTPAuth = true;
        $this->mail->Username = 'bookswapcommunity@gmail.com';
        //Enter your 16 digit mail password to send email
        $this->mail->Password = 'your mail password'; 
        $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mail->Port = 587;
        $this->mail->setFrom('bookswapcommunity@gmail.com', 'Book Swap Community');
    }
    
    public function sendOTP($email, $name, $otp) {
        try {
            $this->mail->clearAddresses(); // Clear previous addresses
            $this->mail->addAddress($email, $name);
            $this->mail->isHTML(true);
            $this->mail->Subject = 'Book Swap - OTP Verification';
            $this->mail->Body = "
                <html>
                <head>
                    <style>
                        .container { font-family: Arial, sans-serif; padding: 20px; }
                        .otp { font-size: 24px; font-weight: bold; color: #667eea; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <h2>Email Verification</h2>
                        <p>Hello <strong>$name</strong>,</p>
                        <p>Your OTP for verification is: <span class='otp'>$otp</span></p>
                        <p>This OTP will expire in 10 minutes.</p>
                        <p>If you didn't request this, please ignore this email.</p>
                    </div>
                </body>
                </html>
            ";
            
            $this->mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Mailer Error: " . $this->mail->ErrorInfo);
            return false;
        }
    }
}

?>
