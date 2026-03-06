<?php
include '../connection.php';

header("Content-Type: application/json");
$response = array('success' => false, 'message' => '');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get post_id and status from request
    $post_id = isset($_POST['post_id']) ? $_POST['post_id'] : (isset($_GET['post_id']) ? $_GET['post_id'] : null);
    $is_donated = isset($_POST['is_donated']) ? (int)$_POST['is_donated'] : 0;
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
            $response['message'] = 'Post not found or you do not have permission to update it';
            echo json_encode($response);
            $checkStmt->close();
            exit;
        }
        $checkStmt->close();
    }

    // Check if is_donated column exists, if not add it
    $checkColumn = $connectNow->query("SHOW COLUMNS FROM posts LIKE 'is_donated'");
    if ($checkColumn->num_rows == 0) {
        $connectNow->query("ALTER TABLE posts ADD COLUMN is_donated TINYINT(1) DEFAULT 0 AFTER is_anonymous");
    }

    // Update post status
    $stmt = $connectNow->prepare("UPDATE posts SET is_donated = ? WHERE id = ?");
    $stmt->bind_param("ii", $is_donated, $post_id);
    
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = $is_donated ? 'Post marked as donated' : 'Post marked as available';
    } else {
        $response['message'] = 'Failed to update post: ' . $stmt->error;
    }
    
    $stmt->close();
} else {
    $response['message'] = 'Invalid request method';
}

echo json_encode($response);
$connectNow->close();
?>