<?php
include '../connection.php';

header("Content-Type: application/json");
$response = array('success' => false, 'message' => '', 'posts' => array());

try {
    // Get user_id from request
    $user_id = isset($_POST['user_id']) ? $_POST['user_id'] : (isset($_GET['user_id']) ? $_GET['user_id'] : null);
    
    if (!$user_id) {
        $response['message'] = 'User ID is required';
        echo json_encode($response);
        exit;
    }

    // Build the SQL query to get user's posts with images
    $sql = "SELECT p.*, 
                   u.name as user_name,
                   up.profile_image as user_profile_image,
                   pi.image_path as post_image
            FROM posts p 
            LEFT JOIN users u ON p.user_id = u.id 
            LEFT JOIN user_profiles up ON u.id = up.user_id
            LEFT JOIN post_images pi ON p.id = pi.post_id AND pi.is_primary = 1
            WHERE p.user_id = ?
            ORDER BY p.created_at DESC";

    $stmt = $connectNow->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result) {
        $posts = array();
        while ($row = $result->fetch_assoc()) {
            // For anonymous posts, hide user info (though these are the user's own posts)
            if (isset($row['is_anonymous']) && $row['is_anonymous'] == 1) {
                $row['user_name'] = 'Anonymous';
                $row['user_profile_image'] = null;
            }
            
            // Ensure all required fields exist - ADDED is_donated here
            $post = array(
                'id' => $row['id'] ?? null,
                'title' => $row['title'] ?? 'No Title',
                'book_name' => $row['book_name'] ?? null,
                'author' => $row['author'] ?? null,
                'type' => $row['type'] ?? 'UNKNOWN',
                'description' => $row['description'] ?? 'No description',
                'is_anonymous' => $row['is_anonymous'] ?? 0,
                'is_donated' => $row['is_donated'] ?? 0,  // ← ADD THIS LINE
                'user_id' => $row['user_id'] ?? null,
                'batch_year' => $row['batch_year'] ?? null,
                'department' => $row['department'] ?? null,
                'location' => $row['location'] ?? null,
                'contact_number' => $row['contact_number'] ?? null,
                'price' => isset($row['price']) ? floatval($row['price']) : null,
                'created_at' => $row['created_at'] ?? null,
                'user_name' => $row['user_name'] ?? 'Unknown User',
                'user_profile_image' => $row['user_profile_image'] ?? null,
                'post_image' => $row['post_image'] ?? null
            );
            
            $posts[] = $post;
        }
        
        $response['success'] = true;
        $response['posts'] = $posts;
        $response['message'] = 'Posts fetched successfully';
    } else {
        $response['message'] = 'Failed to fetch posts: ' . $connectNow->error;
    }
    
    $stmt->close();
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

echo json_encode($response);
$connectNow->close();
?>