<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../../config/database.php';
include_once '../../models/Post.php';
include_once '../../models/User.php';
include_once '../../middleware/auth.php';

$database = new Database();
$db = $database->getConnection();

$post = new Post($db);
$user = new User($db);

// Verify token and get user info
$headers = getallheaders();
$token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : '';

$auth = new Auth($db);
$auth_user = $auth->verifyToken($token);

if(!$auth_user) {
    http_response_code(401);
    echo json_encode(array("message" => "Unauthorized"));
    exit();
}

// Check permission
$user->id = $auth_user['id'];
$user->email = $auth_user['email'];
$user->emailExists();
$permissions = $user->getPermissions();

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        // Super admin, admin, and viewer can all view posts
        if($auth_user['role'] === 'super_admin' || $auth_user['role'] === 'admin' || $auth_user['role'] === 'viewer') {
            $stmt = $post->readAll();
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($posts);
        } else {
            http_response_code(403);
            echo json_encode(array("message" => "Access denied"));
        }
        break;
        
    case 'DELETE':
        // Check permissions for delete
        if($auth_user['role'] === 'super_admin') {
            // Super admin can delete any post
            $data = json_decode(file_get_contents("php://input"));
            if(!empty($data->id)) {
                if($post->delete($data->id)) {
                    http_response_code(200);
                    echo json_encode(array("message" => "Post deleted successfully"));
                } else {
                    http_response_code(503);
                    echo json_encode(array("message" => "Unable to delete post"));
                }
            } else {
                http_response_code(400);
                echo json_encode(array("message" => "Post ID required"));
            }
        }
        else if($auth_user['role'] === 'admin') {
            // Admin can delete any post
            $data = json_decode(file_get_contents("php://input"));
            if(!empty($data->id)) {
                if($post->delete($data->id)) {
                    http_response_code(200);
                    echo json_encode(array("message" => "Post deleted successfully"));
                } else {
                    http_response_code(503);
                    echo json_encode(array("message" => "Unable to delete post"));
                }
            } else {
                http_response_code(400);
                echo json_encode(array("message" => "Post ID required"));
            }
        }
        else if($auth_user['role'] === 'viewer') {
            // Viewer can delete posts (as per requirements)
            $data = json_decode(file_get_contents("php://input"));
            if(!empty($data->id)) {
                if($post->delete($data->id)) {
                    http_response_code(200);
                    echo json_encode(array("message" => "Post deleted successfully"));
                } else {
                    http_response_code(503);
                    echo json_encode(array("message" => "Unable to delete post"));
                }
            } else {
                http_response_code(400);
                echo json_encode(array("message" => "Post ID required"));
            }
        }
        else {
            http_response_code(403);
            echo json_encode(array("message" => "Access denied"));
        }
        break;
}
?>