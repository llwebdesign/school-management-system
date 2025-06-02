<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

require_once 'db.php';

$error = '';
$success = '';

// Handle news post creation (for admin users)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create') {
        $title = trim($_POST['title']);
        $content = trim($_POST['content']);
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO news (
                    title, 
                    content, 
                    author_id, 
                    created_at
                ) VALUES (?, ?, ?, NOW())
            ");
            
            $stmt->execute([$title, $content, $_SESSION['user_id']]);
            $success = "News article published successfully!";
            
        } catch (PDOException $e) {
            error_log("News creation error: " . $e->getMessage());
            $error = "Failed to publish news article.";
        }
    }
}

include 'header.php';
?>

<div class="news-container">
    <!-- News Creation Form (for admin users) -->
    <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
    <div class="news-form-section">
        <h2>Create News Article</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <form method="POST" class="news-form">
            <input type="hidden" name="action" value="create">
            
            <div class="form-group">
                <label for="title">Title</label>
                <input type="text" id="title" name="title" required>
            </div>

            <div class="form-group">
                <label for="content">Content</label>
                <textarea id="content" name="content" rows="6" required></textarea>
            </div>

            <button type="submit" class="btn">Publish News</button>
        </form>
    </div>
    <?php endif; ?>

    <!-- News Articles List -->
    <div class="news-list">
        <h2>School News</h2>
        
        <!-- News Filters -->
        <div class="news-filters">
            <button class="filter-btn active" onclick="filterNews('all')">All News</button>
            <button class="filter-btn" onclick="filterNews('recent')">Recent</button>
            <button class="filter-btn" onclick="filterNews('important')">Important</button>
        </div>

        <?php
        try {
            $stmt = $pdo->prepare("
                SELECT n.*, u.username as author_name 
                FROM news n
                JOIN users u ON n.author_id = u.id
                ORDER BY n.created_at DESC
            ");
            $stmt->execute();
            $newsArticles = $stmt->fetchAll();

            if ($newsArticles) {
                foreach ($newsArticles as $article) {
                    echo "<article class='news-article'>";
                    echo "<header class='article-header'>";
                    echo "<h3>" . htmlspecialchars($article['title']) . "</h3>";
                    echo "<div class='article-meta'>";
                    echo "<span>By " . htmlspecialchars($article['author_name']) . "</span>";
                    echo "<span>Posted on " . date('F j, Y', strtotime($article['created_at'])) . "</span>";
                    echo "</div>";
                    echo "</header>";
                    
                    // Show preview of content
                    $preview = substr(strip_tags($article['content']), 0, 200);
                    echo "<div class='article-preview'>" . htmlspecialchars($preview) . "...</div>";
                    
                    echo "<div class='article-actions'>";
                    echo "<button class='btn btn-small' onclick='showFullArticle(" . $article['id'] . ")'>Read More</button>";
                    if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
                        echo "<button class='btn btn-small btn-danger' onclick='deleteArticle(" . $article['id'] . ")'>Delete</button>";
                    }
                    echo "</div>";
                    
                    // Hidden full content
                    echo "<div id='article-" . $article['id'] . "' class='article-full-content' style='display: none;'>";
                    echo "<div class='article-body'>" . nl2br(htmlspecialchars($article['content'])) . "</div>";
                    echo "<button class='btn btn-small' onclick='hideFullArticle(" . $article['id'] . ")'>Show Less</button>";
                    echo "</div>";
                    echo "</article>";
                }
            } else {
                echo "<p class='no-news'>No news articles available</p>";
            }
        } catch (PDOException $e) {
            error_log("News list error: " . $e->getMessage());
            echo "<p class='error'>Unable to load news articles</p>";
        }
        ?>
    </div>
</div>

<script>
function showFullArticle(id) {
    document.querySelector(`#article-${id} .article-preview`).style.display = 'none';
    document.querySelector(`#article-${id} .article-full-content`).style.display = 'block';
}

function hideFullArticle(id) {
    document.querySelector(`#article-${id} .article-preview`).style.display = 'block';
    document.querySelector(`#article-${id} .article-full-content`).style.display = 'none';
}

function filterNews(type) {
    // Update active filter button
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    event.target.classList.add('active');

    // AJAX request to get filtered news
    fetch(`news_filter.php?type=${type}`)
        .then(response => response.text())
        .then(html => {
            document.querySelector('.news-list').innerHTML = html;
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

function deleteArticle(id) {
    if (confirm('Are you sure you want to delete this article?')) {
        fetch('news_delete.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${id}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.error || 'Failed to delete article');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to delete article');
        });
    }
}
</script>

<?php include 'footer.php'; ?>
