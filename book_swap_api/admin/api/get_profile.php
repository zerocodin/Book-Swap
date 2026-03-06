<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once '../../config/database.php';
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

// Get user ID from request
$data = json_decode(file_get_contents("php://input"), true);
$userId = isset($data['user_id']) ? $data['user_id'] : (isset($_GET['user_id']) ? $_GET['user_id'] : '');

if (empty($userId)) {
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "User ID is required"));
    exit;
}

try {
    // Get user details with profile information
    $query = "SELECT 
                u.id, 
                u.name, 
                u.email, 
                u.role,
                u.profile_image as user_profile_image,
                u.is_verified,
                u.created_at,
                u.updated_at,
                up.student_id, 
                up.department, 
                up.current_location, 
                up.permanent_location, 
                up.bio, 
                up.profile_image as profile_profile_image
              FROM users u
              LEFT JOIN user_profiles up ON u.id = up.user_id
              WHERE u.id = :user_id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":user_id", $userId);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Determine which profile image to use
        if ($userData['role'] === 'user') {
            $userData['profile_image'] = $userData['profile_profile_image'] ?? 'default.png';
        } else {
            $userData['profile_image'] = $userData['user_profile_image'] ?? 'default.png';
        }
        
        // Remove duplicate fields
        unset($userData['user_profile_image']);
        unset($userData['profile_profile_image']);
        
        // Convert null values to empty strings
        $userData['student_id'] = $userData['student_id'] ?? '';
        $userData['department'] = $userData['department'] ?? '';
        $userData['current_location'] = $userData['current_location'] ?? '';
        $userData['permanent_location'] = $userData['permanent_location'] ?? '';
        $userData['bio'] = $userData['bio'] ?? '';
        
        http_response_code(200);
        echo json_encode(array(
            "success" => true,
            "user" => $userData
        ));
    } else {
        http_response_code(404);
        echo json_encode(array("success" => false, "message" => "User not found"));
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        "success" => false,
        "message" => "Error: " . $e->getMessage()
    ));
}
?>