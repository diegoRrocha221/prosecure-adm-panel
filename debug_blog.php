// debug_blog.php
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';
require_once 'classes/Database.php';
require_once 'classes/Blog.php';
require_once 'classes/BlogFilter.php';

echo "<h1>Blog Debug</h1>";

echo "<h2>1. Check Classes</h2>";
try {
    $db = new Database();
    echo "<p style='color: green;'>✓ Database connected</p>";
    
    $blogManager = new Blog($db);
    echo "<p style='color: green;'>✓ Blog class instantiated</p>";
    
    $filterManager = new BlogFilter($db);
    echo "<p style='color: green;'>✓ BlogFilter class instantiated</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<h2>2. Check Tables</h2>";
try {
    $posts = $db->fetchAll("SELECT COUNT(*) as count FROM posts");
    echo "<p style='color: green;'>✓ Posts table exists - " . $posts[0]['count'] . " posts</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Posts table error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

try {
    $filters = $db->fetchAll("SELECT COUNT(*) as count FROM blog_filter");
    echo "<p style='color: green;'>✓ Blog_filter table exists - " . $filters[0]['count'] . " filters</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Blog_filter table error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<h2>3. Check Active Filters</h2>";
try {
    $activeFilters = $filterManager->getActiveFilters();
    if (empty($activeFilters)) {
        echo "<p style='color: orange;'>⚠ No active filters found (is_show = 1)</p>";
    } else {
        echo "<p style='color: green;'>✓ Found " . count($activeFilters) . " active filters:</p>";
        echo "<pre>";
        print_r($activeFilters);
        echo "</pre>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<h2>4. Test Post Creation (Dry Run)</h2>";
$testData = [
    'title' => 'Test Title',
    'subtitle' => 'Test Subtitle',
    'introduction' => '<p>Test Introduction</p>',
    'body' => '<p>Test Body</p>',
    'summary' => '<p>Test Summary</p>',
    'filter' => !empty($activeFilters) ? $activeFilters[0]['uuid'] : 'test-uuid'
];

echo "<p>Test data:</p>";
echo "<pre>";
print_r($testData);
echo "</pre>";

echo "<h2>5. PHP Upload Settings</h2>";
echo "<p>upload_max_filesize: " . ini_get('upload_max_filesize') . "</p>";
echo "<p>post_max_size: " . ini_get('post_max_size') . "</p>";
echo "<p>max_execution_time: " . ini_get('max_execution_time') . "</p>";
echo "<p>memory_limit: " . ini_get('memory_limit') . "</p>";

echo "<h2>6. Test Get Post</h2>";
try {
    $allPosts = $db->fetchAll("SELECT id FROM posts LIMIT 1");
    if (!empty($allPosts)) {
        $testPost = $blogManager->getPostById($allPosts[0]['id']);
        echo "<p style='color: green;'>✓ Successfully retrieved post ID " . $allPosts[0]['id'] . "</p>";
        echo "<pre>";
        print_r($testPost);
        echo "</pre>";
    } else {
        echo "<p style='color: orange;'>⚠ No posts in database to test with</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>