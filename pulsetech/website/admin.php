<?php
require_once 'config.php'; // Provides the $conn variable

// --- MODIFIED PART 1: Replaced getArticles() ---
$sql = "SELECT * FROM articles ORDER BY publish_date DESC LIMIT 100";
$result = $conn->query($sql);
$articles = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $articles[] = $row;
    }
} else {
    // Handle error if query fails
    echo "Error fetching articles: " . $conn->error;
}
// --- End of Modification ---
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - PulseTech</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <div class="logo">
                <a href="index.php">
                    <div class="logo-icon">PT</div>
                    <div class="logo-text">
                        <h1>PulseTech</h1>
                        <p>Admin Panel</p>
                    </div>
                </a>
            </div>
            
            <div class="header-actions">
                <a href="index.php" class="btn-secondary">← Back to Portal</a>
            </div>
        </header>

        <div class="admin-content">
            <section class="admin-section">
                <h2>Article Scraper</h2>
                <div class="scraper-form">
                    <div class="form-group">
                        <label for="articleUrl">Enter Article URL</label>
                        <input type="url" id="articleUrl" placeholder="https://example.com/tech-article" required>
                        <small>Enter the URL of any tech news article to scrape</small>
                    </div>
                    
                    <button id="scrapeBtn" class="btn-primary">
                        <span class="btn-text">Scrape Article</span>
                        <span class="btn-loader" style="display: none;">Scraping...</span>
                    </button>
                    
                    <div id="scrapeResult" class="result-message"></div>
                </div>

                
            </section>

            <section class="admin-section">
                <h2>Portal Statistics</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3><?php echo count($articles); ?></h3>
                        <p>Total Articles</p>
                    </div>
                    <?php
                    $categories = ['AI', 'Mobile', 'Security', 'Hardware', 'Startups'];
                    
                    // --- MODIFIED PART 2: Replaced getArticleCountByCategory() ---
                    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM articles WHERE category = ?");
                    // --- End of Modification ---

                    foreach ($categories as $cat) {
                        // --- MODIFIED PART 3: Execute query for each category ---
                        $stmt->bind_param("s", $cat);
                        $stmt->execute();
                        $countResult = $stmt->get_result()->fetch_assoc();
                        $count = $countResult['count'];
                        // --- End of Modification ---
                        
                        if ($count > 0) {
                    ?>
                        <div class="stat-card">
                            <h3><?php echo $count; ?></h3>
                            <p><?php echo $cat; ?></p>
                        </div>
                    <?php 
                        }
                    }
                    $stmt->close(); // Close the prepared statement
                    ?>
                </div>
            </section>

            <section class="admin-section">
                <h2>Manage Articles</h2>
                <div class="articles-table">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($articles, 0, 20) as $article): ?>
                                <tr data-article-id="<?php echo $article['id']; ?>">
                                    <td><?php echo $article['id']; ?></td>
                                    <td class="article-title-cell">
                                        <a href="article.php?id=<?php echo $article['id']; ?>" target="_blank">
                                            <?php echo htmlspecialchars($article['title']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="category-badge-small">
                                            <?php echo htmlspecialchars($article['category']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($article['publish_date'])); ?></td>
                                    <td>
                                        <button class="btn-danger btn-delete" 
                                                onclick="deleteArticle(<?php echo $article['id']; ?>)">
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>

    <script src="js/admin.js"></script>
</body>
</html>