<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

include '../connection.php';

$data = json_decode(file_get_contents("php://input"), true);
$userId = isset($data['user_id']) ? $data['user_id'] : (isset($_POST['user_id']) ? $_POST['user_id'] : '');

if (empty($userId)) {
    echo json_encode(array("success" => false, "message" => "User ID is required"));
    exit;
}

try {
    $stmt = $connectNow->prepare("
        SELECT u.id, u.name, u.email, 
               up.student_id, up.department, up.current_location, 
               up.permanent_location, up.bio, up.profile_image
        FROM users u
        LEFT JOIN user_profiles up ON u.id = up.user_id
        WHERE u.id = ?
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $userData = $result->fetch_assoc();
        
        // Convert null values to empty strings for consistency
        $userData['student_id'] = $userData['student_id'] ?? '';
        $userData['department'] = $userData['department'] ?? '';
        $userData['current_location'] = $userData['current_location'] ?? '';
        $userData['permanent_location'] = $userData['permanent_location'] ?? '';
        $userData['bio'] = $userData['bio'] ?? '';
        $userData['profile_image'] = $userData['profile_image'] ?? '';
        
        echo json_encode(array(
            "success" => true,
            "userData" => $userData
        ));
    } else {
        echo json_encode(array("success" => false, "message" => "User not found"));
    }

    $stmt->close();
} catch (Exception $e) {
    echo json_encode(array(
        "success" => false,
        "message" => "Error: " . $e->getMessage()
    ));
}

$connectNow->close();
?>