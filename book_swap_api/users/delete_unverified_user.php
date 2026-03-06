<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

include '../connection.php';

// Get JSON input
$data = json_decode(file_get_contents("php://input"), true);
$email = isset($data['email']) ? $data['email'] : (isset($_POST['email']) ? $_POST['email'] : '');

if (empty($email)) {
    echo json_encode(array("success"=> false, "message"=> "Email is required"));
    exit;
}

// Delete unverified user using prepared statement
$stmt = $connectNow->prepare("DELETE FROM users WHERE email = ? AND is_verified = false");
$stmt->bind_param("s", $email);
$stmt->execute();

if($stmt->affected_rows > 0) {
    echo json_encode(array("success"=> true, "message"=> "Unverified account deleted"));
} else {
    echo json_encode(array("success"=> false, "message"=> "No unverified account found or already verified"));
}

$stmt->close();
?>