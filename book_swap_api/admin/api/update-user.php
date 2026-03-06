<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, PUT, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

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

// Check if user has permission to edit users
$user = new User($db);
$user->id = $auth_user['id'];
$user->email = $auth_user['email'];
$user->emailExists();
$permissions = $user->getPermissions();

// Get data from request
$data = json_decode(file_get_contents("php://input"));

if(empty($data->user_id)) {
    http_response_code(400);
    echo json_encode(array("message" => "User ID is required"));
    exit();
}

$user_id = $data->user_id;
$name = isset($data->name) ? htmlspecialchars(strip_tags($data->name)) : null;
$email = isset($data->email) ? htmlspecialchars(strip_tags($data->email)) : null;
$role = isset($data->role) ? htmlspecialchars(strip_tags($data->role)) : null;
$student_id = isset($data->student_id) ? htmlspecialchars(strip_tags($data->student_id)) : null;
$department = isset($data->department) ? htmlspecialchars(strip_tags($data->department)) : null;
$current_location = isset($data->current_location) ? htmlspecialchars(strip_tags($data->current_location)) : null;
$permanent_location = isset($data->permanent_location) ? htmlspecialchars(strip_tags($data->permanent_location)) : null;
$bio = isset($data->bio) ? htmlspecialchars(strip_tags($data->bio)) : null;

try {
    // Get target user's current role
    $target_query = "SELECT role FROM users WHERE id = :user_id";
    $target_stmt = $db->prepare($target_query);
    $target_stmt->bindParam(":user_id", $user_id);
    $target_stmt->execute();
    $target_user = $target_stmt->fetch(PDO::FETCH_ASSOC);
    
    if(!$target_user) {
        http_response_code(404);
        echo json_encode(array("message" => "User not found"));
        exit();
    }
    
    $target_role = $target_user['role'];
    
    // Permission checks
    $can_edit = false;
    
    if($auth_user['role'] === 'super_admin') {
        $can_edit = true;
    } else if($auth_user['role'] === 'admin') {
        // Admin can edit user and viewer
        if($target_role === 'user' || $target_role === 'viewer') {
            $can_edit = true;
        }
    } else if($auth_user['role'] === 'viewer') {
        // Viewer can only edit user
        if($target_role === 'user') {
            $can_edit = true;
        }
    }
    
    if(!$can_edit) {
        http_response_code(403);
        echo json_encode(array("message" => "You don't have permission to edit this user"));
        exit();
    }
    
    // If role is being changed, validate
    if($role !== null) {
        $allowed_roles = [];
        if($auth_user['role'] === 'super_admin') {
            $allowed_roles = ['user', 'viewer', 'admin', 'super_admin'];
        } else if($auth_user['role'] === 'admin') {
            $allowed_roles = ['user', 'viewer'];
        } else if($auth_user['role'] === 'viewer') {
            $allowed_roles = ['user'];
        }
        
        if(!in_array($role, $allowed_roles)) {
            http_response_code(403);
            echo json_encode(array("message" => "You don't have permission to set role: " . $role));
            exit();
        }
    }
    
    $db->beginTransaction();
    
    // Update users table
    $update_fields = [];
    $params = [':user_id' => $user_id];
    
    if ($name !== null) {
        $update_fields[] = "name = :name";
        $params[':name'] = $name;
    }
    
    if ($email !== null) {
        // Check if email already exists for another user
        $check_query = "SELECT id FROM users WHERE email = :email AND id != :user_id";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(":email", $email);
        $check_stmt->bindParam(":user_id", $user_id);
        $check_stmt->execute();
        
        if($check_stmt->rowCount() > 0) {
            $db->rollBack();
            http_response_code(400);
            echo json_encode(array("message" => "Email already exists for another user"));
            exit();
        }
        
        $update_fields[] = "email = :email";
        $params[':email'] = $email;
    }
    
    if ($role !== null) {
        $update_fields[] = "role = :role";
        $params[':role'] = $role;
    }
    
    if (!empty($update_fields)) {
        $update_query = "UPDATE users SET " . implode(", ", $update_fields) . ", updated_at = NOW() WHERE id = :user_id";
        $update_stmt = $db->prepare($update_query);
        $update_stmt->execute($params);
    }
    
    // Update user_profiles table
    $profile_fields = [];
    $profile_params = [':user_id' => $user_id];
    
    if ($student_id !== null) {
        $profile_fields[] = "student_id = :student_id";
        $profile_params[':student_id'] = $student_id;
    }
    
    if ($department !== null) {
        $profile_fields[] = "department = :department";
        $profile_params[':department'] = $department;
    }
    
    if ($current_location !== null) {
        $profile_fields[] = "current_location = :current_location";
        $profile_params[':current_location'] = $current_location;
    }
    
    if ($permanent_location !== null) {
        $profile_fields[] = "permanent_location = :permanent_location";
        $profile_params[':permanent_location'] = $permanent_location;
    }
    
    if ($bio !== null) {
        $profile_fields[] = "bio = :bio";
        $profile_params[':bio'] = $bio;
    }
    
    if (!empty($profile_fields)) {
        // Check if profile exists
        $check_profile = "SELECT id FROM user_profiles WHERE user_id = :user_id";
        $check_profile_stmt = $db->prepare($check_profile);
        $check_profile_stmt->bindParam(":user_id", $user_id);
        $check_profile_stmt->execute();
        
        if ($check_profile_stmt->rowCount() > 0) {
            $profile_fields[] = "updated_at = NOW()";
            $profile_query = "UPDATE user_profiles SET " . implode(", ", $profile_fields) . " WHERE user_id = :user_id";
        } else {
            $profile_query = "INSERT INTO user_profiles (user_id, " . 
                implode(", ", array_map(function($field) { 
                    return str_replace(':', '', $field); 
                }, array_keys($profile_params))) . 
                ") VALUES (:user_id, " . implode(", ", array_keys($profile_params)) . ")";
        }
        
        $profile_stmt = $db->prepare($profile_query);
        $profile_stmt->execute($profile_params);
    }
    
    $db->commit();
    
    // Fetch updated user data
    $fetch_query = "SELECT 
                    u.id, u.name, u.email, u.role, u.profile_image, u.is_verified, u.created_at,
                    up.student_id, up.department, up.current_location, up.permanent_location, up.bio
                  FROM users u
                  LEFT JOIN user_profiles up ON u.id = up.user_id
                  WHERE u.id = :user_id";
    
    $fetch_stmt = $db->prepare($fetch_query);
    $fetch_stmt->bindParam(":user_id", $user_id);
    $fetch_stmt->execute();
    $updated_user = $fetch_stmt->fetch(PDO::FETCH_ASSOC);
    
    http_response_code(200);
    echo json_encode(array(
        "success" => true,
        "message" => "User updated successfully",
        "user" => $updated_user
    ));
    
} catch(Exception $e) {
    $db->rollBack();
    http_response_code(503);
    echo json_encode(array(
        "success" => false,
        "message" => "Unable to update user: " . $e->getMessage()
    ));
}
?>