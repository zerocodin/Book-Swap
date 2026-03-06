<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

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

// Check if user has permission to manage permissions
$user = new User($db);
$user->id = $auth_user['id'];
$user->email = $auth_user['email'];
$user->emailExists();
$permissions = $user->getPermissions();

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        // Get all permissions
        if(isset($_GET['role'])) {
            // Get permissions for specific role
            $query = "SELECT p.*, arp.granted 
                     FROM admin_permissions p
                     LEFT JOIN admin_role_permissions arp ON p.id = arp.permission_id AND arp.role = :role
                     ORDER BY p.id";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(":role", $_GET['role']);
            $stmt->execute();
            
            $permissions_list = [];
            while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $permissions_list[] = [
                    'id' => $row['id'],
                    'permission_key' => $row['permission_key'],
                    'description' => $row['description'],
                    'granted' => isset($row['granted']) ? (bool)$row['granted'] : false
                ];
            }
            
            echo json_encode($permissions_list);
        } else {
            // Get all permissions
            $query = "SELECT * FROM admin_permissions ORDER BY id";
            $stmt = $db->prepare($query);
            $stmt->execute();
            
            $permissions_list = [];
            while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $permissions_list[] = [
                    'id' => $row['id'],
                    'permission_key' => $row['permission_key'],
                    'description' => $row['description']
                ];
            }
            
            echo json_encode($permissions_list);
        }
        break;
        
    case 'PUT':
        // Update permissions for a role (Super Admin only)
        if($auth_user['role'] !== 'super_admin') {
            http_response_code(403);
            echo json_encode(array("message" => "Access denied"));
            exit();
        }
        
        $data = json_decode(file_get_contents("php://input"));
        
        if(!empty($data->role) && isset($data->permissions)) {
            // Begin transaction
            $db->beginTransaction();
            
            try {
                // Delete existing permissions for this role
                $delete_query = "DELETE FROM admin_role_permissions WHERE role = :role";
                $delete_stmt = $db->prepare($delete_query);
                $delete_stmt->bindParam(":role", $data->role);
                $delete_stmt->execute();
                
                // Insert new permissions
                $insert_query = "INSERT INTO admin_role_permissions (role, permission_id, granted) VALUES (:role, :permission_id, 1)";
                $insert_stmt = $db->prepare($insert_query);
                
                foreach($data->permissions as $perm_id) {
                    $insert_stmt->bindParam(":role", $data->role);
                    $insert_stmt->bindParam(":permission_id", $perm_id);
                    $insert_stmt->execute();
                }
                
                $db->commit();
                
                http_response_code(200);
                echo json_encode(array("message" => "Permissions updated successfully"));
            } catch(Exception $e) {
                $db->rollBack();
                http_response_code(503);
                echo json_encode(array("message" => "Unable to update permissions"));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Data incomplete"));
        }
        break;
}
?>