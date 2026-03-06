<?php
include '../connection.php';

header("Content-Type: application/json");
$response = array('success' => false, 'message' => '');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['post_id']) && isset($_FILES['image'])) {
        $post_id = $connectNow->real_escape_string($_POST['post_id']);
        
        // Check if post exists
        $postCheck = $connectNow->query("SELECT id FROM posts WHERE id = '$post_id'");
        
        if ($postCheck->num_rows > 0) {
            $uploadDir = '../post_images/';
            
            // Create directory if it doesn't exist
            if (!file_exists($uploadDir)) {
                if (!mkdir($uploadDir, 0777, true)) {
                    $response['message'] = 'Failed to create upload directory';
                    echo json_encode($response);
                    exit;
                }
            }
            
            $image = $_FILES['image'];
            $fileExtension = strtolower(pathinfo($image['name'], PATHINFO_EXTENSION));
            $fileName = 'post_' . $post_id . '_' . time() . '.' . $fileExtension;
            $filePath = $uploadDir . $fileName;
            
            // Validate image using file extension and MIME type
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            
            // Get the actual MIME type
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $actualMimeType = finfo_file($finfo, $image['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($fileExtension, $allowedExtensions)) {
                $response['message'] = 'Invalid image type. Only JPG, JPEG, PNG, GIF, and WebP are allowed.';
            } elseif (!in_array($actualMimeType, $allowedMimeTypes)) {
                $response['message'] = 'Invalid image MIME type: ' . $actualMimeType;
            } elseif ($image['size'] > 5 * 1024 * 1024) {
                $response['message'] = 'Image too large (max 5MB)';
            } elseif (move_uploaded_file($image['tmp_name'], $filePath)) {
                // Store image path in database
                $imagePath = 'post_images/' . $fileName;
                
                // First, delete any existing primary image for this post
                $connectNow->query("DELETE FROM post_images WHERE post_id = '$post_id' AND is_primary = 1");
                
                // Then insert the new image
                $sql = "INSERT INTO post_images (post_id, image_path, is_primary) VALUES (?, ?, 1)";
                
                $stmt = $connectNow->prepare($sql);
                $stmt->bind_param("is", $post_id, $imagePath);
                
                if ($stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = 'Image uploaded successfully';
                    $response['image_path'] = $imagePath;
                } else {
                    $response['message'] = 'Failed to save image info: ' . $stmt->error;
                    // Clean up the uploaded file if database insert failed
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                }
                $stmt->close();
            } else {
                $response['message'] = 'Failed to upload image. Check directory permissions. Error: ' . $image['error'];
            }
        } else {
            $response['message'] = 'Post not found';
        }
    } else {
        $response['message'] = 'Post ID and image required';
    }
} else {
    $response['message'] = 'Invalid request method';
}

echo json_encode($response);
$connectNow->close();
?>