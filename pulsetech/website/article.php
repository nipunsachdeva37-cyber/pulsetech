<?php
// This line includes the database connection variable '$conn'
require_once 'config.php'; 

// Check if an 'id' was passed in the URL
$articleId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// If no valid ID, redirect back to the homepage
if ($articleId <= 0) {
    header('Location: index.php');
    exit;
}

// --- THIS IS THE FIX ---
// Instead of calling getArticleById(), we use $conn to fetch the article
$stmt = $conn->prepare("SELECT * FROM articles WHERE id = ?");
$stmt->bind_param("i", $articleId);
$stmt->execute();
$result = $stmt->get_result();
$article = $result->fetch_assoc();
// --- END OF FIX ---

// If no article was found with that ID, redirect to homepage
if (!$article) {
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($article['title']); ?> - PulseTech</title>
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
                        <p>Fresh tech news</p>
                    </div>
                </a>
            </div>
            
            <div class="header-actions">
                <a href="index.php" class="btn-secondary">← Back to Home</a>
                <a href="admin.php" class="btn-primary">Admin Panel</a>
            </div>
        </header>

        <div class="article-page">
            <article class="article-full">
                <div class="article-header">
                    <div class="article-meta-full">
                        <span class="category-badge"><?php echo htmlspecialchars($article['category']); ?></span>
                        <span class="date"><?php echo date('M j, Y', strtotime($article['publish_date'])); ?></span>
                    </div>
                    
                    <h1 class="article-title-full"><?php echo htmlspecialchars($article['title']); ?></h1>
                    
                    <?php if ($article['source_url']): ?>
                        <a href="<?php echo htmlspecialchars($article['source_url']); ?>" 
                           target="_blank" class="source-link">
                           View Original Source →
                        </a>
                    <?php endif; ?>
                </div>

                <?php if ($article['image_url']): ?>
                    <div class="article-image-full">
                        <img src="<?php echo htmlspecialchars($article['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($article['title']); ?>">
                    </div>
                <?php endif; ?>

                <div class="article-body">
                   <?php echo $article['content']; ?>
                </div>

                <div class="article-footer">
                    <p class="scraped-info">
                        Article scraped on <?php echo date('F j, Y', strtotime($article['created_at'])); ?>
                    </p>
                </div>
            </article>

            <aside class="related-sidebar">
                <h3>Related Articles</h3>
                <div class="related-articles">
                    <?php
                    // --- THIS IS THE FIX FOR THE SIDEBAR ---
                    // Fetch related articles using $conn
                    $category = $article['category'];
                    $relatedStmt = $conn->prepare("SELECT * FROM articles WHERE category = ? AND id != ? ORDER BY publish_date DESC LIMIT 4");
                    $relatedStmt->bind_param("si", $category, $articleId);
                    $relatedStmt->execute();
                    $relatedArticles = $relatedStmt->get_result();
                    // --- END OF FIX ---

                    foreach ($relatedArticles as $related) {
                    ?>
                        <a href="article.php?id=<?php echo $related['id']; ?>" class="related-item">
                            <?php if ($related['image_url']): ?>
                                <img src="<?php echo htmlspecialchars($related['image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($related['title']); ?>">
                            <?php endif; ?>
                            <div class="related-info">
                                <h4><?php echo htmlspecialchars($related['title']); ?></h4>
                                <span class="date"><?php echo date('M j, Y', strtotime($related['publish_date'])); ?></span>
                            </div>
                        </a>
                    <?php 
                    } 
                    $relatedStmt->close();
                    $conn->close(); // Close the connection at the end
                    ?>
                </div>
            </aside>
        </div>
    </div>

    <script src="js/main.js"></script>
</body>
</html>