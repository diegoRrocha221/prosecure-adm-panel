<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');

try {
    require_once 'config/database.php';
    require_once 'classes/Database.php';
    require_once 'classes/Blog.php';
    require_once 'includes/session.php';
    
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
        exit;
    }
    
    $postId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($postId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid post ID']);
        exit;
    }
    
    $db = new Database();
    $blogManager = new Blog($db);
    
    $post = $blogManager->getPostById($postId);
    
    if (!$post) {
        echo json_encode(['success' => false, 'message' => 'Post not found']);
        exit;
    }
    
    $post['media_url'] = $blogManager->getMediaUrl($post['media']);
    
    echo json_encode([
        'success' => true,
        'post' => $post
    ]);
    
} catch (Exception $e) {
    error_log("Exception in get_post.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}