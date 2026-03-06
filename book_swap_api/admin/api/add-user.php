<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../../config/database.php';
include_once '../../models/User.php';
include_once '../../middleware/auth.php';

$database = new Database();
$db = $database->getConnection();

// Verify token
$headers = getallheaders();
$token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : '';

$auth = new Auth($db);
$auth_user = $auth->verifyToken($token);

if(!$auth_user) {
    http_response_code(401);
    echo json_encode(array("message" => "Unauthorized"));
    exit();
}

// Check if user has permission to add users
// Super admin, admin, and viewer can add users (with role restrictions)
if($auth_user['role'] !== 'super_admin' && $auth_user['role'] !== 'admin' && $auth_user['role'] !== 'viewer') {
    http_response_code(403);
    echo json_encode(array("message" => "Access denied. Only privileged users can add users."));
    exit();
}

$data = json_decode(file_get_contents("php://input"));

if(
    !empty($data->name) &&
    !empty($data->email) &&
    !empty($data->password) &&
    !empty($data->role)
) {
    // Validate role based on current user's role
    $allowed_roles = [];
    if($auth_user['role'] === 'super_admin') {
        $allowed_roles = ['user', 'viewer', 'admin', 'super_admin'];
    } else if($auth_user['role'] === 'admin') {
        $allowed_roles = ['user', 'viewer'];
    } else if($auth_user['role'] === 'viewer') {
        $allowed_roles = ['user'];
    }
    
    if(!in_array($data->role, $allowed_roles)) {
        http_response_code(403);
        echo json_encode(array("message" => "You don't have permission to create users with role: " . $data->role));
        exit();
    }
    
    // Check if email already exists
    $check_query = "SELECT id FROM users WHERE email = :email";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(":email", $data->email);
    $check_stmt->execute();
    
    if($check_stmt->rowCount() > 0) {
        http_response_code(400);
        echo json_encode(array("message" => "Email already exists"));
        exit();
    }
    
    // Create new user
    $query = "INSERT INTO users (name, email, role, password_hash, is_verified, created_at) 
              VALUES (:name, :email, :role, :password_hash, 1, NOW())";
    
    $stmt = $db->prepare($query);
    
    $name = htmlspecialchars(strip_tags($data->name));
    $email = htmlspecialchars(strip_tags($data->email));
    $role = htmlspecialchars(strip_tags($data->role));
    $password_hash = md5($data->password); // Using MD5 as requested
    
    $stmt->bindParam(":name", $name);
    $stmt->bindParam(":email", $email);
    $stmt->bindParam(":role", $role);
    $stmt->bindParam(":password_hash", $password_hash);
    
    if($stmt->execute()) {
        $user_id = $db->lastInsertId();
        
        // If role is user, create user_profiles entry
        if($role === 'user') {
            $profile_query = "INSERT INTO user_profiles (user_id) VALUES (:user_id)";
            $profile_stmt = $db->prepare($profile_query);
            $profile_stmt->bindParam(":user_id", $user_id);
            $profile_stmt->execute();
        }
        
        // If role is admin or super_admin, create admin_metadata entry
        if($role === 'admin' || $role === 'super_admin') {
            $admin_query = "INSERT INTO admin_metadata (user_id, admin_since) VALUES (:user_id, NOW())";
            $admin_stmt = $db->prepare($admin_query);
            $admin_stmt->bindParam(":user_id", $user_id);
            $admin_stmt->execute();
        }
        
        http_response_code(201);
        echo json_encode(array(
            "message" => "User created successfully",
            "user_id" => $user_id
        ));
    } else {
        http_response_code(503);
        echo json_encode(array("message" => "Unable to create user"));
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "Unable to create user. Data incomplete."));
}
?>