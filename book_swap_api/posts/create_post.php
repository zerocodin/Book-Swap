<?php
// require_once 'connection.php';
include '../connection.php';

header("Content-Type: application/json");
$response = array('success' => false, 'message' => '');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (isset($input['user_id']) && isset($input['title']) && isset($input['type'])) {
        $user_id = $connectNow->real_escape_string($input['user_id']);
        $title = $connectNow->real_escape_string($input['title']);
        $book_name = isset($input['book_name']) ? $connectNow->real_escape_string($input['book_name']) : null;
        $author = isset($input['author']) ? $connectNow->real_escape_string($input['author']) : null;
        $type = $connectNow->real_escape_string($input['type']); // Can be 'SELL', 'GIVE', or 'REQUEST'
        $description = isset($input['description']) ? $connectNow->real_escape_string($input['description']) : '';
        $is_anonymous = isset($input['is_anonymous']) ? (int)$input['is_anonymous'] : 0;
        $batch_year = isset($input['batch_year']) ? $connectNow->real_escape_string($input['batch_year']) : null;
        $department = isset($input['department']) ? $connectNow->real_escape_string($input['department']) : null;
        $location = isset($input['location']) ? $connectNow->real_escape_string($input['location']) : null;
        $contact_number = isset($input['contact_number']) ? $connectNow->real_escape_string($input['contact_number']) : null;
        // Price is only for SELL type, null for GIVE and REQUEST
        $price = ($type == 'SELL' && isset($input['price'])) ? floatval($input['price']) : null;

        // Check if user exists and is verified
        $userCheck = $connectNow->query("SELECT id FROM users WHERE id = '$user_id' AND is_verified = 1");
        
        if ($userCheck->num_rows > 0) {
            // Insert post
            $sql = "INSERT INTO posts (title, book_name, author, type, description, is_anonymous, user_id, batch_year, department, location, contact_number, price) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $connectNow->prepare($sql);
            $stmt->bind_param("sssssisssssd", $title, $book_name, $author, $type, $description, $is_anonymous, $user_id, $batch_year, $department, $location, $contact_number, $price);
            
            if ($stmt->execute()) {
                $post_id = $stmt->insert_id;
                $response['success'] = true;
                $response['message'] = 'Post created successfully';
                $response['post_id'] = $post_id;
            } else {
                $response['message'] = 'Failed to create post: ' . $stmt->error;
            }
            $stmt->close();
        } else {
            $response['message'] = 'User not found or not verified';
        }
    } else {
        $response['message'] = 'Required fields missing';
    }
} else {
    $response['message'] = 'Invalid request method';
}

echo json_encode($response);
$connectNow->close();
?>