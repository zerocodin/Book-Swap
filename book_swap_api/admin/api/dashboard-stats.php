<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../../config/database.php';
include_once '../../models/User.php';
include_once '../../models/Post.php';
include_once '../../middleware/auth.php';

$database = new Database();
$db = $database->getConnection();

// Add this debug code temporarily
error_log("Dashboard stats accessed");
error_log("Headers: " . print_r(getallheaders(), true));

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

// Get statistics
$stats = array();

// Total users
$query = "SELECT COUNT(*) as total FROM users";
$stmt = $db->prepare($query);
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$stats['total_users'] = $row['total'];

// Total posts
$query = "SELECT COUNT(*) as total FROM posts";
$stmt = $db->prepare($query);
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$stats['total_posts'] = $row['total'];

// Today's posts
$query = "SELECT COUNT(*) as total FROM posts WHERE DATE(created_at) = CURDATE()";
$stmt = $db->prepare($query);
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$stats['today_posts'] = $row['total'];

// Total admins (including super_admin and admin)
$query = "SELECT COUNT(*) as total FROM users WHERE role IN ('admin', 'super_admin')";
$stmt = $db->prepare($query);
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$stats['total_admins'] = $row['total'];

// Recent activity (you might want to create an activity log table for this)
// For now, return recent posts and user registrations
$stats['recent_activity'] = array();

// Recent posts
$query = "SELECT p.*, u.name as user_name, 'post_created' as type 
          FROM posts p 
          JOIN users u ON p.user_id = u.id 
          ORDER BY p.created_at DESC 
          LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $row['description'] = "New post created by " . $row['user_name'] . ": " . $row['title'];
    $stats['recent_activity'][] = $row;
}

// Recent users
$query = "SELECT id, name, created_at, 'user_created' as type 
          FROM users 
          ORDER BY created_at DESC 
          LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $row['description'] = "New user registered: " . $row['name'];
    $stats['recent_activity'][] = $row;
}

// Sort recent activity by date
usort($stats['recent_activity'], function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

// Limit to 10 items
$stats['recent_activity'] = array_slice($stats['recent_activity'], 0, 10);

http_response_code(200);
echo json_encode($stats);
?>