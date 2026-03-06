<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

include '../connection.php';

$userId = $_POST['user_id'] ?? '';
$uploadDir = '../profile_images/';

// Create directory if it doesn't exist
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

if (empty($userId)) {
    echo json_encode(array("success" => false, "message" => "User ID is required"));
    exit;
}

if (!isset($_FILES['profile_image']) || $_FILES['profile_image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(array("success" => false, "message" => "No image uploaded or upload error"));
    exit;
}

$imageFile = $_FILES['profile_image'];
$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
$maxSize = 5 * 1024 * 1024; // 5MB

// Get actual file extension and MIME type
$fileExtension = strtolower(pathinfo($imageFile['name'], PATHINFO_EXTENSION));
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $imageFile['tmp_name']);
finfo_close($finfo);

// Validate file type using both extension and MIME type
$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
if (!in_array($fileExtension, $allowedExtensions) || !in_array($mimeType, $allowedTypes)) {
    echo json_encode(array(
        "success" => false, 
        "message" => "Only JPG, PNG, GIF, and WEBP images are allowed. Detected: $mimeType",
        "detected_type" => $mimeType
    ));
    exit;
}

// Validate file size
if ($imageFile['size'] > $maxSize) {
    echo json_encode(array("success" => false, "message" => "Image size must be less than 5MB"));
    exit;
}

// Generate unique filename
$fileName = 'user_' . $userId . '_' . time() . '.' . $fileExtension;
$filePath = $uploadDir . $fileName;

// Move uploaded file
if (move_uploaded_file($imageFile['tmp_name'], $filePath)) {
    // Update database with image path - ensure user_profiles record exists
    $checkStmt = $connectNow->prepare("SELECT id FROM user_profiles WHERE user_id = ?");
    $checkStmt->bind_param("i", $userId);
    $checkStmt->execute();
    $profileExists = $checkStmt->get_result()->num_rows > 0;
    $checkStmt->close();
    
    $profileImagePath = 'profile_images/' . $fileName;
    
    if ($profileExists) {
        $stmt = $connectNow->prepare("UPDATE user_profiles SET profile_image = ? WHERE user_id = ?");
    } else {
        $stmt = $connectNow->prepare("INSERT INTO user_profiles (profile_image, user_id) VALUES (?, ?)");
    }
    
    $stmt->bind_param("si", $profileImagePath, $userId);
    
    if ($stmt->execute()) {
        echo json_encode(array(
            "success" => true,
            "message" => "Profile image updated successfully",
            "image_path" => $profileImagePath
        ));
    } else {
        unlink($filePath); // Remove uploaded file if DB update fails
        echo json_encode(array("success" => false, "message" => "Failed to update database: " . $stmt->error));
    }
    $stmt->close();
} else {
    echo json_encode(array("success" => false, "message" => "Failed to upload image"));
}

$connectNow->close();
?>