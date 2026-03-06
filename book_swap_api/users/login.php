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
$userEmail = isset($data['email']) ? $data['email'] : (isset($_POST['email']) ? $_POST['email'] : '');
$userPassword = isset($data['password_hash']) ? md5($data['password_hash']) : (isset($_POST['password_hash']) ? md5($_POST['password_hash']) : '');

if (empty($userEmail) || empty($userPassword)) {
    echo json_encode(array("success"=> false, "message"=> "Email and password are required"));
    exit;
}

// Use prepared statements to prevent SQL injection
$stmt = $connectNow->prepare("SELECT * FROM users WHERE email = ? AND password_hash = ? AND is_verified = true");
$stmt->bind_param("ss", $userEmail, $userPassword);
$stmt->execute();
$rslt = $stmt->get_result();

if($rslt->num_rows > 0) {
    $userRecord = $rslt->fetch_assoc();
    
    echo json_encode(
        array(
            'success' => true,
            'userData' => $userRecord,
        )
    );
} else {
    echo json_encode(array("success"=> false, "message"=> "Account not found or not verified"));
}

$stmt->close();
?>