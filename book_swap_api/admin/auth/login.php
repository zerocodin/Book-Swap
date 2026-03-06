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
include_once '../../middleware/auth.php';

$database = new Database();
$db = $database->getConnection();

$user = new User($db);
$adminMetadata = new AdminMetadata($db);
$auth = new Auth($db);

$data = json_decode(file_get_contents("php://input"));

if(!empty($data->email) && !empty($data->password)) {
    $user->email = $data->email;
    
    if($user->emailExists()) {
        // Check if user has admin privileges
        if($user->role == 'user') {
            http_response_code(403);
            echo json_encode(array("message" => "Access denied. Admin privileges required."));
            exit();
        }
        
        // Use MD5 for password verification
        if(md5($data->password) == $user->password_hash) {
            if(!$user->is_verified) {
                http_response_code(401);
                echo json_encode(array("message" => "Account not verified. Please verify your email."));
                exit();
            }
            
            // Get admin metadata
            $adminMetadata->user_id = $user->id;
            $adminMetadata->read();
            
            // Get user permissions
            $permissions = $user->getPermissions();
            
            // Generate token (using user_id as simple token)
            $token = $auth->generateToken($user->id);
            
            http_response_code(200);
            echo json_encode(array(
                "message" => "Login successful",
                "token" => $token,
                "user" => array(
                    "id" => $user->id,
                    "name" => $user->name,
                    "email" => $user->email,
                    "role" => $user->role,
                    "profile_image" => $user->profile_image,
                    "permissions" => $permissions,
                    "admin_since" => $adminMetadata->admin_since,
                    "access_level" => $adminMetadata->access_level
                )
            ));
        } else {
            http_response_code(401);
            echo json_encode(array("message" => "Invalid password."));
        }
    } else {
        http_response_code(404);
        echo json_encode(array("message" => "User not found."));
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "Unable to login. Data incomplete."));
}
?>