<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
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

$user = new User($db);
$user->id = $auth_user['id'];
$user->email = $auth_user['email'];
$user->emailExists();

// Get form data
$name = isset($_POST['name']) ? $_POST['name'] : '';
$current_password = isset($_POST['current_password']) ? $_POST['current_password'] : '';
$new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';

// Validate current password if trying to change password
if(!empty($new_password)) {
    if(empty($current_password)) {
        http_response_code(400);
        echo json_encode(array("message" => "Current password is required to change password"));
        exit();
    }
    
    if(md5($current_password) != $user->password_hash) {
        http_response_code(401);
        echo json_encode(array("message" => "Current password is incorrect"));
        exit();
    }
}

// Handle profile image upload
$profile_image = $user->profile_image;
if(isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $filename = $_FILES['profile_image']['name'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    if(in_array($ext, $allowed)) {
        // Generate unique filename
        $new_filename = 'profile_' . $user->id . '_' . time() . '.' . $ext;
        $upload_path = '../../profile_images/' . $new_filename;
        
        if(move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
            // Delete old profile image if exists and not default
            if($profile_image && $profile_image != 'default.png' && file_exists('../../profile_images/' . $profile_image)) {
                unlink('../../profile_images/' . $profile_image);
            }
            $profile_image = $new_filename;
        }
    }
}

// Update user in database
$query = "UPDATE users SET name = :name, profile_image = :profile_image";
$params = [':name' => $name, ':profile_image' => $profile_image];

if(!empty($new_password)) {
    $query .= ", password_hash = :password_hash";
    $params[':password_hash'] = md5($new_password);
}

$query .= " WHERE id = :id";
$params[':id'] = $user->id;

$stmt = $db->prepare($query);

if($stmt->execute($params)) {
    // Fetch the updated user data
    $query = "SELECT id, name, email, role, profile_image, is_verified, created_at FROM users WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $user->id);
    $stmt->execute();
    $updatedUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    http_response_code(200);
    echo json_encode(array(
        "message" => "Profile updated successfully",
        "user" => array(
            "id" => $updatedUser['id'],
            "name" => $updatedUser['name'],
            "email" => $updatedUser['email'],
            "role" => $updatedUser['role'],
            "profile_image" => $updatedUser['profile_image'],
            "is_verified" => $updatedUser['is_verified'],
            "created_at" => $updatedUser['created_at']
        )
    ));
} else {
    http_response_code(503);
    echo json_encode(array("message" => "Unable to update profile"));
}
?>