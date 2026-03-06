<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

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

// Get posted data
$data = json_decode(file_get_contents("php://input"));

if(!empty($data->post_id) && isset($data->is_available)) {
    
    // Using is_donated field (0 = available, 1 = donated/unavailable)
    $query = "UPDATE posts SET is_donated = :is_donated WHERE id = :post_id";
    $stmt = $db->prepare($query);
    
    // Convert is_available to is_donated (1 = unavailable/donated, 0 = available)
    $is_donated = ($data->is_available == 1) ? 0 : 1;
    
    $stmt->bindParam(":is_donated", $is_donated);
    $stmt->bindParam(":post_id", $data->post_id);
    
    if($stmt->execute()) {
        http_response_code(200);
        echo json_encode(array(
            "success" => true,
            "message" => "Post availability updated successfully"
        ));
    } else {
        http_response_code(503);
        echo json_encode(array(
            "success" => false,
            "message" => "Unable to update post availability"
        ));
    }
} else {
    http_response_code(400);
    echo json_encode(array(
        "success" => false,
        "message" => "Incomplete data"
    ));
}
?>