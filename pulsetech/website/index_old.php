<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // Change this to your MySQL password
define('DB_NAME', 'pulsetech_news');

// Flask API configuration
define('API_URL', 'http://localhost:5000/api');

// Connect to database
function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    return $conn;
}

// Get all articles
function getArticles($category = null, $search = null, $limit = 50) {
    $conn = getDBConnection();
    
    $query = "SELECT * FROM articles WHERE 1=1";
    $params = [];
    $types = "";
    
    if ($category && $category != 'All') {
        $query .= " AND category = ?";
        $params[] = $category;
        $types .= "s";
    }
    
    if ($search) {
        $query .= " AND (title LIKE ? OR content LIKE ?)";
        $searchTerm = "%" . $search . "%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= "ss";
    }
    
    $query .= " ORDER BY created_at DESC LIMIT ?";
    $params[] = (int)$limit;
    $types .= "i";
    
    $stmt = $conn->prepare($query);
    if ($types) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $articles = [];
    while ($row = $result->fetch_assoc()) {
        $articles[] = $row;
    }
    
    $conn->close();
    return $articles;
}

// Get single article by ID
function getArticleById($id) {
    $conn = getDBConnection();
    
    $stmt = $conn->prepare("SELECT * FROM articles WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $article = $result->fetch_assoc();
    $conn->close();
    
    return $article;
}

// Get all tags
function getTags() {
    $conn = getDBConnection();
    
    $result = $conn->query("SELECT * FROM tags ORDER BY name");
    $tags = [];
    
    while ($row = $result->fetch_assoc()) {
        $tags[] = $row;
    }
    
    $conn->close();
    return $tags;
}

// Get article count by category
function getArticleCountByCategory($category) {
    $conn = getDBConnection();
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM articles WHERE category = ?");
    $stmt->bind_param("s", $category);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $count = $result->fetch_assoc()['count'];
    $conn->close();
    
    return $count;
}

// Format date
function formatDate($date) {
    return date('Y-m-d', strtotime($date));
}
?>