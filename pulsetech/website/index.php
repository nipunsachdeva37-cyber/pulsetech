<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Direct database connection with new user
$conn = new mysqli('localhost', 'pulsetech_user', 'pulsetech123', 'pulsetech_news');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get filter parameters
$category = isset($_GET['category']) ? $_GET['category'] : null;
$search = isset($_GET['search']) ? $_GET['search'] : null;

// Build query
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

$query .= " ORDER BY created_at DESC LIMIT 50";

// Execute query
if (!empty($params)) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($query);
}

$articles = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $articles[] = $row;
    }
}

// Get tags
$tagsQuery = "SELECT * FROM tags ORDER BY name";
$tagsResult = $conn->query($tagsQuery);

$tags = [];
if ($tagsResult) {
    while ($row = $tagsResult->fetch_assoc()) {
        $tags[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PulseTech - Fresh Tech News</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header class="header">
            <div class="logo">
                <div class="logo-icon">PT</div>
                <div class="logo-text">
                    <h1>PulseTech</h1>
                    <p>Fresh tech news</p>
                </div>
            </div>
            
            <div class="header-actions">
                <div class="search-bar">
                    <input type="text" id="searchInput" placeholder="Search headlines, companies" 
                           value="<?php echo htmlspecialchars($search ?? ''); ?>">
                </div>
                <select id="categoryFilter" class="category-select">
                    <option value="All">All</option>
                    <?php foreach ($tags as $tag): ?>
                        <option value="<?php echo htmlspecialchars($tag['name']); ?>" 
                                <?php echo ($category == $tag['name']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($tag['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button class="clear-btn" onclick="clearFilters()">Clear</button>
                <a href="admin.php" class="btn-primary">Admin</a>
            </div>
        </header>

        <!-- Sidebar and Content -->
        <div class="main-content">
            <!-- Sidebar -->
            <aside class="sidebar">
                <div class="sidebar-section">
                    <h3>Top Tags</h3>
                    <div class="tag-list">
                        <?php foreach ($tags as $tag): ?>
                            <a href="?category=<?php echo urlencode($tag['name']); ?>" 
                               class="tag <?php echo ($category == $tag['name']) ? 'active' : ''; ?>">
                                <?php echo htmlspecialchars($tag['name']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="sidebar-section">
                    <h3>Latest</h3>
                    <div class="latest-articles">
                        <?php 
                        $latestArticles = array_slice($articles, 0, 4);
                        foreach ($latestArticles as $article): 
                        ?>
                            <a href="article.php?id=<?php echo $article['id']; ?>" class="latest-item">
                                <?php if ($article['image_url']): ?>
                                    <img src="<?php echo htmlspecialchars($article['image_url']); ?>" 
                                         alt="<?php echo htmlspecialchars($article['title']); ?>"
                                         onerror="this.style.display='none'">
                                <?php endif; ?>
                                <div class="latest-info">
                                    <h4><?php echo htmlspecialchars(substr($article['title'], 0, 60)) . '...'; ?></h4>
                                    <span class="date"><?php echo date('Y-m-d', strtotime($article['publish_date'])); ?></span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </aside>

            <!-- Articles Grid -->
            <main class="articles-grid">
                <div class="results-header">
                    <span class="results-count"><?php echo count($articles); ?> results</span>
                </div>
                
                <?php if (empty($articles)): ?>
                    <div class="no-results">
                        <p>No articles found. Try scraping some tech news!</p>
                        <a href="admin.php" class="btn-primary">Go to Admin Panel</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($articles as $article): ?>
                        <article class="article-card">
                            <a href="article.php?id=<?php echo $article['id']; ?>">
                                <?php if ($article['image_url']): ?>
                                    <div class="article-image">
                                        <img src="<?php echo htmlspecialchars($article['image_url']); ?>" 
                                             alt="<?php echo htmlspecialchars($article['title']); ?>"
                                             onerror="this.style.display='none'">
                                    </div>
                                <?php endif; ?>
                                
                                <div class="article-content">
                                    <div class="article-meta">
                                        <span class="category"><?php echo htmlspecialchars($article['category']); ?></span>
                                        <span class="date"><?php echo date('Y-m-d', strtotime($article['publish_date'])); ?></span>
                                    </div>
                                    
                                    <h2 class="article-title"><?php echo htmlspecialchars($article['title']); ?></h2>
                                    
                                    <p class="article-excerpt">
                                        <?php echo htmlspecialchars($article['excerpt']); ?>
                                    </p>
                                </div>
                            </a>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="js/main.js"></script>
</body>
</html>