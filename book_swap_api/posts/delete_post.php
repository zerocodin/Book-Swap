<?php
include '../connection.php';

header("Content-Type: application/json");
$response = array('success' => false, 'message' => '');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get post_id from request
    $post_id = isset($_POST['post_id']) ? $_POST['post_id'] : (isset($_GET['post_id']) ? $_GET['post_id'] : null);
    $user_id = isset($_POST['user_id']) ? $_POST['user_id'] : (isset($_GET['user_id']) ? $_GET['user_id'] : null);
    
    if (!$post_id) {
        $response['message'] = 'Post ID is required';
        echo json_encode($response);
        exit;
    }

    // Check if post exists and belongs to user (if user_id provided)
    if ($user_id) {
        $checkStmt = $connectNow->prepare("SELECT id FROM posts WHERE id = ? AND user_id = ?");
        $checkStmt->bind_param("ii", $post_id, $user_id);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows == 0) {
            $response['message'] = 'Post not found or you do not have permission to delete it';
            echo json_encode($response);
            $checkStmt->close();
            exit;
        }
        $checkStmt->close();
    }

    // First, delete associated images from post_images table
    $imgStmt = $connectNow->prepare("SELECT image_path FROM post_images WHERE post_id = ?");
    $imgStmt->bind_param("i", $post_id);
    $imgStmt->execute();
    $imgResult = $imgStmt->get_result();
    
    // Delete physical image files
    while ($row = $imgResult->fetch_assoc()) {
        $imagePath = '../' . $row['image_path'];
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
    }
    $imgStmt->close();

    // Delete post (cascade will delete related records)
    $stmt = $connectNow->prepare("DELETE FROM posts WHERE id = ?");
    $stmt->bind_param("i", $post_id);
    
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Post deleted successfully';
    } else {
        $response['message'] = 'Failed to delete post: ' . $stmt->error;
    }
    
    $stmt->close();
} else {
    $response['message'] = 'Invalid request method';
}

echo json_encode($response);
$connectNow->close();
?>