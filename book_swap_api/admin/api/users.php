<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, DELETE, PUT");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../../config/database.php';
include_once '../../models/UserManagement.php';
include_once '../../models/User.php';
include_once '../../middleware/auth.php';

$database = new Database();
$db = $database->getConnection();

$userManagement = new UserManagement($db);
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
        // Check if user has permission to view users
        // Super admin, admin, and viewer should all be able to view users
        if($auth_user['role'] === 'super_admin' || $auth_user['role'] === 'admin' || $auth_user['role'] === 'viewer') {
            // Get all users from the database
            $users = $userManagement->readAll();
            
            // Return the users as JSON
            echo json_encode($users);
        } else {
            http_response_code(403);
            echo json_encode(array("message" => "Access denied"));
        }
        break;
        
    case 'DELETE':
        // Check permissions for delete
        if($auth_user['role'] === 'super_admin') {
            // Super admin can delete anyone except themselves
            $data = json_decode(file_get_contents("php://input"));
            if(!empty($data->id)) {
                // Don't allow deleting yourself
                if($data->id == $auth_user['id']) {
                    http_response_code(400);
                    echo json_encode(array("message" => "Cannot delete your own account"));
                    exit();
                }
                
                // Get user info before deleting
                $check_query = "SELECT role FROM users WHERE id = :id";
                $check_stmt = $db->prepare($check_query);
                $check_stmt->bindParam(":id", $data->id);
                $check_stmt->execute();
                $user_to_delete = $check_stmt->fetch(PDO::FETCH_ASSOC);
                
                if($user_to_delete) {
                    if($userManagement->delete($data->id)) {
                        http_response_code(200);
                        echo json_encode(array("message" => "User deleted successfully"));
                    } else {
                        http_response_code(503);
                        echo json_encode(array("message" => "Unable to delete user"));
                    }
                } else {
                    http_response_code(404);
                    echo json_encode(array("message" => "User not found"));
                }
            } else {
                http_response_code(400);
                echo json_encode(array("message" => "User ID required"));
            }
        } 
        else if($auth_user['role'] === 'admin') {
            // Admin can delete only user and viewer roles
            $data = json_decode(file_get_contents("php://input"));
            if(!empty($data->id)) {
                // Don't allow deleting yourself
                if($data->id == $auth_user['id']) {
                    http_response_code(400);
                    echo json_encode(array("message" => "Cannot delete your own account"));
                    exit();
                }
                
                // Get user info before deleting
                $check_query = "SELECT role FROM users WHERE id = :id";
                $check_stmt = $db->prepare($check_query);
                $check_stmt->bindParam(":id", $data->id);
                $check_stmt->execute();
                $user_to_delete = $check_stmt->fetch(PDO::FETCH_ASSOC);
                
                if($user_to_delete) {
                    // Admin can only delete user or viewer
                    if($user_to_delete['role'] === 'user' || $user_to_delete['role'] === 'viewer') {
                        if($userManagement->delete($data->id)) {
                            http_response_code(200);
                            echo json_encode(array("message" => "User deleted successfully"));
                        } else {
                            http_response_code(503);
                            echo json_encode(array("message" => "Unable to delete user"));
                        }
                    } else {
                        http_response_code(403);
                        echo json_encode(array("message" => "Cannot delete admin or super admin"));
                    }
                } else {
                    http_response_code(404);
                    echo json_encode(array("message" => "User not found"));
                }
            } else {
                http_response_code(400);
                echo json_encode(array("message" => "User ID required"));
            }
        }
        else if($auth_user['role'] === 'viewer') {
            // Viewer can delete only user role
            $data = json_decode(file_get_contents("php://input"));
            if(!empty($data->id)) {
                // Don't allow deleting yourself
                if($data->id == $auth_user['id']) {
                    http_response_code(400);
                    echo json_encode(array("message" => "Cannot delete your own account"));
                    exit();
                }
                
                // Get user info before deleting
                $check_query = "SELECT role FROM users WHERE id = :id";
                $check_stmt = $db->prepare($check_query);
                $check_stmt->bindParam(":id", $data->id);
                $check_stmt->execute();
                $user_to_delete = $check_stmt->fetch(PDO::FETCH_ASSOC);
                
                if($user_to_delete) {
                    // Viewer can only delete user
                    if($user_to_delete['role'] === 'user') {
                        if($userManagement->delete($data->id)) {
                            http_response_code(200);
                            echo json_encode(array("message" => "User deleted successfully"));
                        } else {
                            http_response_code(503);
                            echo json_encode(array("message" => "Unable to delete user"));
                        }
                    } else {
                        http_response_code(403);
                        echo json_encode(array("message" => "You can only delete users with role 'user'"));
                    }
                } else {
                    http_response_code(404);
                    echo json_encode(array("message" => "User not found"));
                }
            } else {
                http_response_code(400);
                echo json_encode(array("message" => "User ID required"));
            }
        }
        else {
            http_response_code(403);
            echo json_encode(array("message" => "Access denied"));
        }
        break;
        
    case 'PUT':
        // Check permissions for update
        if($auth_user['role'] === 'super_admin') {
            // Super admin can update anyone
            $data = json_decode(file_get_contents("php://input"));
            if(!empty($data->id) && !empty($data->role)) {
                // Don't allow changing your own role to something lower
                if($data->id == $auth_user['id'] && $data->role !== 'super_admin') {
                    http_response_code(400);
                    echo json_encode(array("message" => "Cannot downgrade your own super admin role"));
                    exit();
                }
                
                if($userManagement->updateRole($data->id, $data->role)) {
                    http_response_code(200);
                    echo json_encode(array("message" => "User role updated successfully"));
                } else {
                    http_response_code(503);
                    echo json_encode(array("message" => "Unable to update user role"));
                }
            } else {
                http_response_code(400);
                echo json_encode(array("message" => "Data incomplete"));
            }
        }
        else if($auth_user['role'] === 'admin') {
            // Admin can update only user and viewer roles
            $data = json_decode(file_get_contents("php://input"));
            if(!empty($data->id) && !empty($data->role)) {
                // Don't allow updating your own role
                if($data->id == $auth_user['id']) {
                    http_response_code(400);
                    echo json_encode(array("message" => "Cannot change your own role"));
                    exit();
                }
                
                // Get current user role
                $check_query = "SELECT role FROM users WHERE id = :id";
                $check_stmt = $db->prepare($check_query);
                $check_stmt->bindParam(":id", $data->id);
                $check_stmt->execute();
                $user_to_update = $check_stmt->fetch(PDO::FETCH_ASSOC);
                
                if($user_to_update) {
                    // Can only update user or viewer roles
                    if($user_to_update['role'] === 'user' || $user_to_update['role'] === 'viewer') {
                        // Can only set to user or viewer
                        if($data->role === 'user' || $data->role === 'viewer') {
                            if($userManagement->updateRole($data->id, $data->role)) {
                                http_response_code(200);
                                echo json_encode(array("message" => "User role updated successfully"));
                            } else {
                                http_response_code(503);
                                echo json_encode(array("message" => "Unable to update user role"));
                            }
                        } else {
                            http_response_code(403);
                            echo json_encode(array("message" => "Admin can only set roles to user or viewer"));
                        }
                    } else {
                        http_response_code(403);
                        echo json_encode(array("message" => "Cannot update admin or super admin roles"));
                    }
                } else {
                    http_response_code(404);
                    echo json_encode(array("message" => "User not found"));
                }
            } else {
                http_response_code(400);
                echo json_encode(array("message" => "Data incomplete"));
            }
        }
        else if($auth_user['role'] === 'viewer') {
            // Viewer can update only user role
            $data = json_decode(file_get_contents("php://input"));
            if(!empty($data->id) && !empty($data->role)) {
                // Don't allow updating your own role
                if($data->id == $auth_user['id']) {
                    http_response_code(400);
                    echo json_encode(array("message" => "Cannot change your own role"));
                    exit();
                }
                
                // Get current user role
                $check_query = "SELECT role FROM users WHERE id = :id";
                $check_stmt = $db->prepare($check_query);
                $check_stmt->bindParam(":id", $data->id);
                $check_stmt->execute();
                $user_to_update = $check_stmt->fetch(PDO::FETCH_ASSOC);
                
                if($user_to_update) {
                    // Can only update user role
                    if($user_to_update['role'] === 'user') {
                        // Can only set to user
                        if($data->role === 'user') {
                            if($userManagement->updateRole($data->id, $data->role)) {
                                http_response_code(200);
                                echo json_encode(array("message" => "User role updated successfully"));
                            } else {
                                http_response_code(503);
                                echo json_encode(array("message" => "Unable to update user role"));
                            }
                        } else {
                            http_response_code(403);
                            echo json_encode(array("message" => "Viewer can only set roles to user"));
                        }
                    } else {
                        http_response_code(403);
                        echo json_encode(array("message" => "You can only update users with role 'user'"));
                    }
                } else {
                    http_response_code(404);
                    echo json_encode(array("message" => "User not found"));
                }
            } else {
                http_response_code(400);
                echo json_encode(array("message" => "Data incomplete"));
            }
        }
        else {
            http_response_code(403);
            echo json_encode(array("message" => "Access denied"));
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(array("message" => "Method not allowed"));
        break;
}
?>