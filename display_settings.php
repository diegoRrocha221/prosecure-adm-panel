<?php
require_once 'config/database.php';
require_once 'classes/Database.php';
require_once 'classes/Settings.php';
require_once 'classes/Blog.php';
require_once 'includes/session.php';
require_once 'includes/functions.php';

requireLogin();

$db = new Database();
$settingsManager = new Settings($db);
$blogManager = new Blog($db);

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $section = $_POST['section'] ?? '';
        $posts = [];
        
        for ($i = 1; $i <= 6; $i++) {
            $posts[] = $_POST['post' . $i] ?? '';
        }
        
        $result = $settingsManager->updateWebsiteConfig($section, $posts);
        $message = $result['message'];
        $messageType = $result['success'] ? 'success' : 'danger';
    }
}

$allPosts = $blogManager->getAllPosts();
$config = $settingsManager->getWebsiteConfig();

$homeConfig = $config['home'] ? json_decode($config['home'], true) : [];
$faqConfig = $config['faq'] ? json_decode($config['faq'], true) : [];
$contactConfig = $config['contact'] ? json_decode($config['contact'], true) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Display Settings - ProSecure Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body data-page="display_settings">
    <?php include 'views/navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'views/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="main-content">
                    <h2><i class="fas fa-desktop me-2"></i>Display Settings</h2>
                    
                    <?php if ($message): ?>
                        <?php echo alert($message, $messageType); ?>
                    <?php endif; ?>
                    
                    <?php if (empty($allPosts)): ?>
                        <div class="alert alert-warning">
                            No blog posts available. Please create blog posts first.
                        </div>
                    <?php else: ?>
                        
                        <!-- Home Page -->
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-home me-2"></i>Home Page Posts</h5>
                            </div>
                            <div class="card-body">
                                <p class="text-muted">Select 6 blog posts to display on the home page.</p>
                                
                                <form method="POST">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="section" value="home">
                                    
                                    <div class="row">
                                        <?php for ($i = 1; $i <= 6; $i++): ?>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Post <?php echo $i; ?></label>
                                                <select class="form-select" name="post<?php echo $i; ?>" required>
                                                    <option value="">Select a post...</option>
                                                    <?php foreach ($allPosts as $post): ?>
                                                        <option value="<?php echo htmlspecialchars($post['uuid']); ?>"
                                                                <?php echo (isset($homeConfig['post' . $i]) && $homeConfig['post' . $i] === $post['uuid']) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($post['title']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        <?php endfor; ?>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i>Save Home Settings
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <!-- FAQ Page -->
                        <div class="card mb-4">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0"><i class="fas fa-question-circle me-2"></i>FAQ Page Posts</h5>
                            </div>
                            <div class="card-body">
                                <p class="text-muted">Select 6 blog posts to display on the FAQ page.</p>
                                
                                <form method="POST">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="section" value="faq">
                                    
                                    <div class="row">
                                        <?php for ($i = 1; $i <= 6; $i++): ?>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Post <?php echo $i; ?></label>
                                                <select class="form-select" name="post<?php echo $i; ?>" required>
                                                    <option value="">Select a post...</option>
                                                    <?php foreach ($allPosts as $post): ?>
                                                        <option value="<?php echo htmlspecialchars($post['uuid']); ?>"
                                                                <?php echo (isset($faqConfig['post' . $i]) && $faqConfig['post' . $i] === $post['uuid']) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($post['title']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        <?php endfor; ?>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-save me-1"></i>Save FAQ Settings
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Contact Page -->
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0"><i class="fas fa-envelope me-2"></i>Contact Page Posts</h5>
                            </div>
                            <div class="card-body">
                                <p class="text-muted">Select 6 blog posts to display on the contact page.</p>
                                
                                <form method="POST">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="section" value="contact">
                                    
                                    <div class="row">
                                        <?php for ($i = 1; $i <= 6; $i++): ?>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Post <?php echo $i; ?></label>
                                                <select class="form-select" name="post<?php echo $i; ?>" required>
                                                    <option value="">Select a post...</option>
                                                    <?php foreach ($allPosts as $post): ?>
                                                        <option value="<?php echo htmlspecialchars($post['uuid']); ?>"
                                                                <?php echo (isset($contactConfig['post' . $i]) && $contactConfig['post' . $i] === $post['uuid']) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($post['title']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        <?php endfor; ?>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-info">
                                        <i class="fas fa-save me-1"></i>Save Contact Settings
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/navigation.js"></script>
    <script src="assets/js/script.js"></script>
</body>
</html>