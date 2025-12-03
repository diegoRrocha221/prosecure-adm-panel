<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
set_time_limit(120);

require_once 'config/database.php';
require_once 'classes/Database.php';
require_once 'classes/Blog.php';
require_once 'classes/BlogFilter.php';
require_once 'includes/session.php';
require_once 'includes/functions.php';

requireLogin();

$db = new Database();
$blogManager = new Blog($db);
$filterManager = new BlogFilter($db);

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                try {
                    $data = [
                        'title' => $_POST['title'] ?? '',
                        'subtitle' => $_POST['subtitle'] ?? '',
                        'introduction' => $_POST['introduction'] ?? '',
                        'body' => $_POST['body'] ?? '',
                        'summary' => $_POST['summary'] ?? '',
                        'filter' => $_POST['filter']
                    ];
                    
                    $mediaFile = isset($_FILES['media']) && $_FILES['media']['error'] !== UPLOAD_ERR_NO_FILE ? $_FILES['media'] : null;
                    
                    $result = $blogManager->createPost($data, $mediaFile);
                    $message = $result['message'];
                    $messageType = $result['success'] ? 'success' : 'danger';
                } catch (Exception $e) {
                    error_log("Exception in create: " . $e->getMessage());
                    $message = "Error: " . $e->getMessage();
                    $messageType = 'danger';
                }
                break;
                
            case 'update':
                try {
                    $data = [
                        'title' => $_POST['title'] ?? '',
                        'subtitle' => $_POST['subtitle'] ?? '',
                        'introduction' => $_POST['introduction'] ?? '',
                        'body' => $_POST['body'] ?? '',
                        'summary' => $_POST['summary'] ?? '',
                        'filter' => $_POST['filter']
                    ];
                    
                    $mediaFile = isset($_FILES['media']) && $_FILES['media']['error'] !== UPLOAD_ERR_NO_FILE ? $_FILES['media'] : null;
                    
                    $result = $blogManager->updatePost($_POST['post_id'], $data, $mediaFile);
                    $message = $result['message'];
                    $messageType = $result['success'] ? 'success' : 'danger';
                } catch (Exception $e) {
                    error_log("Exception in update: " . $e->getMessage());
                    $message = "Error: " . $e->getMessage();
                    $messageType = 'danger';
                }
                break;
                
            case 'delete':
                try {
                    $result = $blogManager->deletePost($_POST['post_id']);
                    $message = $result['message'];
                    $messageType = $result['success'] ? 'success' : 'danger';
                } catch (Exception $e) {
                    error_log("Exception in delete: " . $e->getMessage());
                    $message = "Error: " . $e->getMessage();
                    $messageType = 'danger';
                }
                break;
        }
    }
}

$posts = $blogManager->getAllPosts();
$activeFilters = $filterManager->getActiveFilters();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog - ProSecure Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body data-page="blog">
    <?php include 'views/navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'views/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="main-content">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2><i class="fas fa-blog me-2"></i>Blog Management</h2>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createPostModal">
                            <i class="fas fa-plus me-1"></i>Create New Post
                        </button>
                    </div>
                    
                    <?php if ($message): ?>
                        <?php echo alert($message, $messageType); ?>
                    <?php endif; ?>
                    
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Media</th>
                                            <th>Title</th>
                                            <th>Filter</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($posts)): ?>
                                            <tr>
                                                <td colspan="5" class="text-center py-4">
                                                    <div class="empty-state">
                                                        <i class="fas fa-inbox"></i>
                                                        <p class="mb-0">No blog posts found.</p>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($posts as $post): ?>
                                                <tr>
                                                    <td>
                                                        <?php if ($post['media']): ?>
                                                            <?php 
                                                            $mediaUrl = $blogManager->getMediaUrl($post['media']);
                                                            $isVideo = preg_match('/\.(mp4|mpeg|mov|webm)$/i', $post['media']);
                                                            ?>
                                                            <?php if ($isVideo): ?>
                                                                <video class="plan-image-thumb" style="object-fit: cover;">
                                                                    <source src="<?php echo htmlspecialchars($mediaUrl); ?>">
                                                                </video>
                                                            <?php else: ?>
                                                                <img src="<?php echo htmlspecialchars($mediaUrl); ?>" 
                                                                     alt="<?php echo htmlspecialchars($post['title']); ?>"
                                                                     class="plan-image-thumb">
                                                            <?php endif; ?>
                                                        <?php else: ?>
                                                            <div class="plan-image-thumb bg-light d-flex align-items-center justify-content-center">
                                                                <i class="fas fa-image text-muted"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($post['title']); ?></strong>
                                                        <?php if ($post['subtitle']): ?>
                                                            <br><small class="text-muted"><?php echo htmlspecialchars($post['subtitle']); ?></small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($post['filter_name']): ?>
                                                            <span class="badge bg-info"><?php echo htmlspecialchars($post['filter_name']); ?></span>
                                                        <?php else: ?>
                                                            <span class="text-muted">No filter</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo formatDateTime($post['created_at']); ?></td>
                                                    <td>
                                                        <button type="button" class="btn btn-sm btn-warning" 
                                                                onclick="editPost(<?php echo $post['id']; ?>)">
                                                            <i class="fas fa-edit"></i> Edit
                                                        </button>
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="action" value="delete">
                                                            <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-danger delete-confirm">
                                                                <i class="fas fa-trash"></i> Delete
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Create Post Modal -->
    <div class="modal fade" id="createPostModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="create">
                    <div class="modal-header">
                        <h5 class="modal-title">Create New Post</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="create_title" class="form-label">Title</label>
                                    <input type="text" class="form-control" id="create_title" name="title">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="create_subtitle" class="form-label">Subtitle</label>
                                    <input type="text" class="form-control" id="create_subtitle" name="subtitle">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="create_introduction" class="form-label">Introduction</label>
                                    <textarea class="form-control" id="create_introduction" name="introduction" rows="4"></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="create_body" class="form-label">Body</label>
                                    <textarea class="form-control" id="create_body" name="body" rows="8"></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="create_summary" class="form-label">Summary</label>
                                    <textarea class="form-control" id="create_summary" name="summary" rows="4"></textarea>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="create_filter" class="form-label required-field">Filter</label>
                                    <select class="form-select" id="create_filter" name="filter" required>
                                        <option value="">Select a filter...</option>
                                        <?php foreach ($activeFilters as $filter): ?>
                                            <option value="<?php echo htmlspecialchars($filter['uuid']); ?>">
                                                <?php echo htmlspecialchars($filter['filter']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="create_media" class="form-label">Media <span class="text-muted">(Image or Video)</span></label>
                                    <input type="file" class="form-control" id="create_media" name="media" 
                                           accept="image/jpeg,image/png,image/gif,image/webp,video/mp4,video/mpeg,video/quicktime,video/webm"
                                           onchange="previewMedia(this, 'create_media_preview')">
                                    <small class="text-muted">Max 50MB</small>
                                    <div id="create_media_preview" class="mt-2"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Post</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Post Modal -->
    <div class="modal fade" id="editPostModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="post_id" id="edit_post_id">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Post</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="edit_title" class="form-label">Title</label>
                                    <input type="text" class="form-control" id="edit_title" name="title">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="edit_subtitle" class="form-label">Subtitle</label>
                                    <input type="text" class="form-control" id="edit_subtitle" name="subtitle">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="edit_introduction" class="form-label">Introduction</label>
                                    <textarea class="form-control" id="edit_introduction" name="introduction" rows="4"></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="edit_body" class="form-label">Body</label>
                                    <textarea class="form-control" id="edit_body" name="body" rows="8"></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="edit_summary" class="form-label">Summary</label>
                                    <textarea class="form-control" id="edit_summary" name="summary" rows="4"></textarea>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="edit_filter" class="form-label required-field">Filter</label>
                                    <select class="form-select" id="edit_filter" name="filter" required>
                                        <option value="">Select a filter...</option>
                                        <?php foreach ($activeFilters as $filter): ?>
                                            <option value="<?php echo htmlspecialchars($filter['uuid']); ?>">
                                                <?php echo htmlspecialchars($filter['filter']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="edit_media" class="form-label">Media <span class="text-muted">(Leave empty to keep current)</span></label>
                                    <div id="edit_current_media" class="mb-2"></div>
                                    <input type="file" class="form-control" id="edit_media" name="media" 
                                           accept="image/jpeg,image/png,image/gif,image/webp,video/mp4,video/mpeg,video/quicktime,video/webm"
                                           onchange="previewMedia(this, 'edit_media_preview')">
                                    <small class="text-muted">Max 50MB</small>
                                    <div id="edit_media_preview" class="mt-2"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Post</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.tiny.cloud/1/bj0dky4sek8qz6w9l89vbogl0ss9uf7mrq1jgo7l0ssteap1/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <script src="assets/js/navigation.js"></script>
    <script src="assets/js/script.js"></script>
    <script>
        // Initialize TinyMCE on page load
        $(document).ready(function() {
            tinymce.init({
                selector: '#create_introduction, #create_body, #create_summary, #edit_introduction, #edit_body, #edit_summary',
                height: 250,
                menubar: false,
                plugins: [
                    'advlist', 'autolink', 'lists', 'link', 'image', 'charmap',
                    'anchor', 'searchreplace', 'visualblocks', 'code',
                    'insertdatetime', 'media', 'table', 'help', 'wordcount'
                ],
                toolbar: 'undo redo | formatselect | bold italic | alignleft aligncenter alignright | bullist numlist | link image | code',
                content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; font-size: 14px; }',
                setup: function(editor) {
                    editor.on('init', function() {
                        console.log('TinyMCE initialized:', editor.id);
                    });
                }
            });
        });
        
        function previewMedia(input, previewId) {
            const preview = document.getElementById(previewId);
            preview.innerHTML = '';
            
            if (input.files && input.files[0]) {
                const file = input.files[0];
                
                // Check file size
                if (file.size > 50 * 1024 * 1024) {
                    alert('File too large. Maximum 50MB allowed.');
                    input.value = '';
                    return;
                }
                
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    if (file.type.startsWith('image/')) {
                        preview.innerHTML = '<img src="' + e.target.result + '" class="image-preview show" alt="Preview">';
                    } else if (file.type.startsWith('video/')) {
                        preview.innerHTML = '<video class="image-preview show" controls><source src="' + e.target.result + '"></video>';
                    }
                };
                
                reader.readAsDataURL(file);
            }
        }
        
        function editPost(postId) {
            console.log('Fetching post:', postId);
            
            $.ajax({
                url: 'get_post.php',
                type: 'GET',
                data: { id: postId },
                dataType: 'json',
                beforeSend: function() {
                    console.log('Sending request...');
                },
                success: function(response) {
                    console.log('Response received:', response);
                    
                    if (response.success) {
                        const post = response.post;
                        
                        // Set basic fields
                        document.getElementById('edit_post_id').value = post.id;
                        document.getElementById('edit_title').value = post.title || '';
                        document.getElementById('edit_subtitle').value = post.subtitle || '';
                        document.getElementById('edit_filter').value = post.filter || '';
                        
                        // Set TinyMCE content - wait for editors to be ready
                        setTimeout(function() {
                            var introEditor = tinymce.get('edit_introduction');
                            var bodyEditor = tinymce.get('edit_body');
                            var summaryEditor = tinymce.get('edit_summary');
                            
                            if (introEditor) {
                                introEditor.setContent(post.introduction || '');
                            } else {
                                console.error('TinyMCE edit_introduction not found');
                                document.getElementById('edit_introduction').value = post.introduction || '';
                            }
                            
                            if (bodyEditor) {
                                bodyEditor.setContent(post.body || '');
                            } else {
                                console.error('TinyMCE edit_body not found');
                                document.getElementById('edit_body').value = post.body || '';
                            }
                            
                            if (summaryEditor) {
                                summaryEditor.setContent(post.summary || '');
                            } else {
                                console.error('TinyMCE edit_summary not found');
                                document.getElementById('edit_summary').value = post.summary || '';
                            }
                        }, 300);
                        
                        // Show current media
                        const currentMediaDiv = document.getElementById('edit_current_media');
                        if (post.media) {
                            const mediaUrl = post.media_url;
                            const isVideo = /\.(mp4|mpeg|mov|webm)$/i.test(post.media);
                            
                            if (isVideo) {
                                currentMediaDiv.innerHTML = '<div class="mb-2"><strong>Current Media:</strong><br><video class="plan-image-thumb" controls><source src="' + mediaUrl + '"></video></div>';
                            } else {
                                currentMediaDiv.innerHTML = '<div class="mb-2"><strong>Current Media:</strong><br><img src="' + mediaUrl + '" class="plan-image-thumb" alt="Current media"></div>';
                            }
                        } else {
                            currentMediaDiv.innerHTML = '<p class="text-muted small">No media set</p>';
                        }
                        
                        document.getElementById('edit_media_preview').innerHTML = '';
                        document.getElementById('edit_media').value = '';
                        
                        // Show modal
                        const modal = new bootstrap.Modal(document.getElementById('editPostModal'));
                        modal.show();
                    } else {
                        console.error('Error response:', response.message);
                        alert('Error loading post: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', status, error);
                    console.error('Status:', xhr.status);
                    console.error('Response:', xhr.responseText);
                    alert('Failed to load post data. Status: ' + xhr.status + '\nError: ' + error);
                }
            });
        }
        
        // Submit handlers
        $('#createPostModal form').on('submit', function(e) {
            // Sync TinyMCE content before submit
            tinymce.triggerSave();
        });
        
        $('#editPostModal form').on('submit', function(e) {
            // Sync TinyMCE content before submit
            tinymce.triggerSave();
        });
        
        // Reset on close
        $('#createPostModal').on('hidden.bs.modal', function() {
            this.querySelector('form').reset();
            if (tinymce.get('create_introduction')) tinymce.get('create_introduction').setContent('');
            if (tinymce.get('create_body')) tinymce.get('create_body').setContent('');
            if (tinymce.get('create_summary')) tinymce.get('create_summary').setContent('');
            document.getElementById('create_media_preview').innerHTML = '';
        });
        
        $('#editPostModal').on('hidden.bs.modal', function() {
            this.querySelector('form').reset();
            if (tinymce.get('edit_introduction')) tinymce.get('edit_introduction').setContent('');
            if (tinymce.get('edit_body')) tinymce.get('edit_body').setContent('');
            if (tinymce.get('edit_summary')) tinymce.get('edit_summary').setContent('');
            document.getElementById('edit_media_preview').innerHTML = '';
        });
    </script>
</body>
</html>
</body>
</html>