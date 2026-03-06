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

$database = new Database();
$db = $database->getConnection();

$user = new User($db);

$data = json_decode(file_get_contents("php://input"));

if(!empty($data->email) && !empty($data->otp)) {
    $user->email = $data->email;
    
    if($user->emailExists()) {
        if($user->otp_code == $data->otp && strtotime($user->otp_expiry) > time()) {
            $user->is_verified = true;
            $user->otp_code = null;
            $user->otp_expiry = null;
            
            if($user->verify()) {
                http_response_code(200);
                echo json_encode(array("message" => "Email verified successfully."));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "Unable to verify email."));
            }
        } else {
            http_response_code(401);
            echo json_encode(array("message" => "Invalid or expired OTP."));
        }
    } else {
        http_response_code(404);
        echo json_encode(array("message" => "User not found."));
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "Data incomplete."));
}
?>