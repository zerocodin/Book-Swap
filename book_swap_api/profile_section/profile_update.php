<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

include '../connection.php';

// Better way to detect JSON content
$contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';

if (strpos($contentType, 'application/json') !== false) {
    // Receive JSON data
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
} else {
    // Receive form data
    $data = $_POST;
}

// Debug logging (remove in production)
error_log("Received data: " . print_r($data, true));

$userId = isset($data['user_id']) ? $data['user_id'] : '';
$userName = isset($data['name']) ? $data['name'] : '';
$userBio = isset($data['bio']) ? $data['bio'] : '';
$userStudentID = isset($data['student_id']) ? $data['student_id'] : '';
$userDepartment = isset($data['department']) ? $data['department'] : '';
$currentLocation = isset($data['current_location']) ? $data['current_location'] : '';
$permanentLocation = isset($data['permanent_location']) ? $data['permanent_location'] : '';

// More detailed error response
if (empty($userId)) {
    error_log("Missing user_id. Received data: " . print_r($data, true));
    echo json_encode(array(
        "success" => false, 
        "message" => "User ID is required",
        "received_data" => $data // For debugging
    ));
    exit;
}

// Rest of your PHP code remains the same...
try {
    // Start transaction
    $connectNow->begin_transaction();

    // Update users table
    $stmt1 = $connectNow->prepare("UPDATE users SET name = ? WHERE id = ?");
    $stmt1->bind_param("si", $userName, $userId);
    $stmt1->execute();

    // Check if user profile exists, if not create it
    $checkProfile = $connectNow->prepare("SELECT id FROM user_profiles WHERE user_id = ?");
    $checkProfile->bind_param("i", $userId);
    $checkProfile->execute();
    $profileExists = $checkProfile->get_result()->num_rows > 0;
    $checkProfile->close();

    if ($profileExists) {
        // Update existing profile
        $stmt2 = $connectNow->prepare("UPDATE user_profiles SET student_id = ?, department = ?, current_location = ?, permanent_location = ?, bio = ? WHERE user_id = ?");
        $stmt2->bind_param("sssssi", $userStudentID, $userDepartment, $currentLocation, $permanentLocation, $userBio, $userId);
        $stmt2->execute();
    } else {
        // Create new profile
        $stmt2 = $connectNow->prepare("INSERT INTO user_profiles (student_id, department, current_location, permanent_location, bio, user_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt2->bind_param("sssssi", $userStudentID, $userDepartment, $currentLocation, $permanentLocation, $userBio, $userId);
        $stmt2->execute();
    }

    // Commit transaction
    $connectNow->commit();

    echo json_encode(array(
        "success" => true,
        "message" => "Profile updated successfully"
    ));

} catch (Exception $e) {
    // Rollback transaction on error
    $connectNow->rollback();
    echo json_encode(array(
        "success" => false,
        "message" => "Error updating profile: " . $e->getMessage()
    ));
}

$connectNow->close();
?>