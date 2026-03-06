<?php
// Add these headers at the top of EVERY PHP API file
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once '../../config/database.php';
include_once '../../models/User.php';
include_once '../../models/AdminMetadata.php';
include_once '../../middleware/mailer.php';

$database = new Database();
$db = $database->getConnection();

$user = new User($db);
$mailer = new Mailer();

$data = json_decode(file_get_contents("php://input"));

if(!empty($data->email)) {
    $user->email = $data->email;
    
    if($user->emailExists()) {
        // Generate OTP
        $otp = sprintf("%06d", mt_rand(1, 999999));
        $otp_expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        
        $user->otp_code = $otp;
        $user->otp_expiry = $otp_expiry;
        
        if($user->updateOTP()) {
            if($mailer->sendOTP($data->email, $user->name, $otp)) {
                http_response_code(200);
                echo json_encode(array(
                    "message" => "OTP sent to your email.",
                    "email" => $data->email
                ));
            } else {
                http_response_code(500);
                echo json_encode(array("message" => "Failed to send OTP."));
            }
        } else {
            http_response_code(503);
            echo json_encode(array("message" => "Unable to process request."));
        }
    } else {
        http_response_code(404);
        echo json_encode(array("message" => "Email not found."));
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "Email is required."));
}
?>