// test_blog_upload.php - teste isolado
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(120);

require_once 'config/database.php';
require_once 'classes/Database.php';
require_once 'classes/Blog.php';
require_once 'classes/BlogFilter.php';

echo "<h1>Test Blog Post Creation</h1>";

$db = new Database();
$blogManager = new Blog($db);
$filterManager = new BlogFilter($db);

$activeFilters = $filterManager->getActiveFilters();

echo "<h2>Active Filters:</h2>";
echo "<pre>";
print_r($activeFilters);
echo "</pre>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h2>Processing POST...</h2>";
    
    $data = [
        'title' => $_POST['title'] ?? '',
        'subtitle' => $_POST['subtitle'] ?? '',
        'introduction' => $_POST['introduction'] ?? '',
        'body' => $_POST['body'] ?? '',
        'summary' => $_POST['summary'] ?? '',
        'filter' => $_POST['filter']
    ];
    
    echo "<h3>Data:</h3>";
    echo "<pre>";
    print_r($data);
    echo "</pre>";
    
    echo "<h3>Files:</h3>";
    echo "<pre>";
    print_r($_FILES);
    echo "</pre>";
    
    $mediaFile = isset($_FILES['media']) && $_FILES['media']['error'] !== UPLOAD_ERR_NO_FILE ? $_FILES['media'] : null;
    
    try {
        $result = $blogManager->createPost($data, $mediaFile);
        
        echo "<h3>Result:</h3>";
        echo "<pre>";
        print_r($result);
        echo "</pre>";
        
        if ($result['success']) {
            echo "<p style='color: green; font-weight: bold;'>SUCCESS!</p>";
        } else {
            echo "<p style='color: red; font-weight: bold;'>FAILED: " . htmlspecialchars($result['message']) . "</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red; font-weight: bold;'>EXCEPTION: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    }
}
?>

<hr>
<h2>Test Form</h2>
<form method="POST" enctype="multipart/form-data">
    <p>
        <label>Title:</label><br>
        <input type="text" name="title" value="Test Post">
    </p>
    
    <p>
        <label>Subtitle:</label><br>
        <input type="text" name="subtitle" value="Test Subtitle">
    </p>
    
    <p>
        <label>Introduction:</label><br>
        <textarea name="introduction" rows="3">Test introduction</textarea>
    </p>
    
    <p>
        <label>Body:</label><br>
        <textarea name="body" rows="5">Test body content</textarea>
    </p>
    
    <p>
        <label>Summary:</label><br>
        <textarea name="summary" rows="3">Test summary</textarea>
    </p>
    
    <p>
        <label>Filter:</label><br>
        <select name="filter" required>
            <option value="">Select...</option>
            <?php foreach ($activeFilters as $filter): ?>
                <option value="<?php echo htmlspecialchars($filter['uuid']); ?>">
                    <?php echo htmlspecialchars($filter['filter']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </p>
    
    <p>
        <label>Media (optional):</label><br>
        <input type="file" name="media" accept="image/*,video/*">
    </p>
    
    <p>
        <button type="submit">Create Test Post</button>
    </p>
</form>