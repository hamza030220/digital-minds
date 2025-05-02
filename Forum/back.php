<?php
// Start the session
session_start();

// Set the current page for the sidebar
$currentPage = 'forum';

// Base path for the project
$basePath = '/old/Forum/'; // Adjust this path to match your project structure

// Function to check if user is admin
function isAdminLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
}

// Check if user is logged in and is an admin
if (!isAdminLoggedIn()) {
    // Not logged in or not an admin, redirect to login page
    header("Location: signin.php?error=unauthorized");
    exit();
}

// Handle logout if requested
if (isset($_GET['logout']) && $_GET['logout'] == '1') {
    // Clear all session variables
    $_SESSION = array();
    
    // If a session cookie is used, clear it
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Delete remember_me token if exists
    if (isset($_COOKIE['remember_me_token'])) {
        // Remove from database
        require_once 'db_connect.php';
        $token = $_COOKIE['remember_me_token'];
        try {
            $stmt = $conn->prepare("DELETE FROM remember_tokens WHERE token = :token");
            $stmt->bindParam(':token', $token);
            $stmt->execute();
        } catch(PDOException $e) {
            // Fail silently
        }
        
        // Delete cookie
        setcookie('remember_me_token', '', time() - 3600, "/");
    }
    
    // Destroy the session
    session_destroy();
    
    // Redirect to login page
    header("Location: signin.php?logout=1");
    exit();
}

// Get user information for display
$username = htmlspecialchars($_SESSION['username']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Forum Green.tn</title>
    <link rel="stylesheet" href="forum.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Admin specific styles */
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #ffffff; /* Changed to white */
            margin: 0;
            padding: 0;
        }

        .admin-container {
            margin-left: var(--sidebar-width); /* Adjust content to account for sidebar width */
            padding: 40px;
            max-width: calc(100% - var(--sidebar-width));
            background: linear-gradient(135deg, #F9F5E8 0%, #ffffff 100%);
            border-radius: 15px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(46, 125, 50, 0.2);
        }
        
        .admin-user-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
            border: 1px solid rgba(46, 125, 50, 0.1);
        }
        
        .admin-user-info {
            display: flex;
            align-items: center;
            font-size: 16px;
            color: #2c3e50;
        }
        
        .admin-actions {
            display: flex;
            gap: 15px;
        }
        
        .logout-btn {
            color: #fff;
            background: linear-gradient(to right, #2e7d32, #219150);
            border: 1px solid #2e7d32;
            padding: 8px 16px;
            border-radius: 8px;
            font-family: "Arial", sans-serif;
            font-size: 16px;
            font-weight: bold;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
            letter-spacing: 0.5px;
            display: inline-block;
            min-width: 65px;
            text-align: center;
            margin: 0 5px;
            white-space: nowrap;
        }
        
        .logout-btn:hover {
            background-color: #219150;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.2);
        }
        
        .question-admin {
            background: #f8f9fa;
            padding: 25px;
            margin-bottom: 25px;
            border-radius: 12px;
            border-left: 5px solid #2e7d32;
            transition: all 0.3s ease;
            position: relative;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        
        .question-admin:hover {
            transform: translateX(5px);
            background: #ffffff;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .comment-admin {
            background: #f0f0f0;
            padding: 15px;
            margin: 10px 0 10px 20px;
            border-radius: 8px;
            border-left: 3px solid #f57c00;
            position: relative;
        }
        
        .comments-section {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px dashed #ccc;
        }
        
        .timestamp {
            font-size: 0.8em;
            color: #666;
            margin-left: 10px;
        }
        
        .post-header, .comment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .no-comments {
            font-style: italic;
            color: #666;
            margin-left: 15px;
        }
        
        .error {
            color: #c62828;
            font-weight: bold;
            padding: 10px;
            background-color: #ffebee;
            border-radius: 5px;
            margin: 10px 0;
        }
        
        .admin-actions button {
    background: #4CAF50; /* Green background */
    color: white; /* White text */
    border: none; /* Remove border */
    border-radius: 5px; /* Rounded corners */
    padding: 8px 12px; /* Padding for the button */
    font-size: 14px; /* Font size */
    font-weight: bold; /* Bold text */
    cursor: pointer; /* Pointer cursor on hover */
    transition: background-color 0.3s ease, transform 0.2s ease; /* Smooth transitions */
    display: inline-flex; /* Align icon and text */
    align-items: center; /* Center align icon and text */
    gap: 5px; /* Space between icon and text */
}

.admin-actions button:hover {
    background: #45A049; /* Darker green on hover */
    transform: translateY(-2px); /* Slight lift on hover */
}

.admin-actions .delete {
    background: #E53935; /* Red background for delete */
}

.admin-actions .delete:hover {
    background: #D32F2F; /* Darker red on hover */
}

.admin-actions .report {
    background: #FF9800; /* Orange background for report */
}

.admin-actions .report:hover {
    background: #FB8C00; /* Darker orange on hover */
}

.comments-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.comments-header h4 {
    margin: 0;
    font-size: 1.2em;
    color: #333;
}

.view-comments-btn {
    /*background-color: #4CAF50; */
    color:  #4CAF50; /* White text */
    border: none; /* Remove border */
    border-radius: 5px; /* Rounded corners */
    padding: 8px 12px; /* Padding for the button */
    font-size: 14px; /* Font size */
    font-weight: bold; /* Bold text */
    cursor: pointer; /* Pointer cursor on hover */
    transition: background-color 0.3s ease, transform 0.2s ease; /* Smooth transitions */
    display: inline-flex; /* Align icon and text */
    align-items: center; /* Center align icon and text */
    gap: 5px; /* Space between icon and text */
}

.view-comments-btn:hover {
    background-color: #45A049; /* Darker green on hover */
    transform: translateY(-2px); /* Slight lift on hover */
}
        
        /*.admin-actions button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            filter: brightness(110%);
        }*/
        
        h2 {
            font-size: 28px;
            color: #2e7d32;
            margin-bottom: 30px;
            text-align: center;
            font-family: "Bauhaus 93", Arial, sans-serif;
            position: relative;
            padding-bottom: 15px;
        }
        
        h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 3px;
            background: linear-gradient(to right, #2e7d32, #219150);
            border-radius: 2px;
        }
        
        .user-info-modal {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            z-index: 1000;
        }
        
        .user-info-modal h3 {
            margin-top: 0;
        }
        
        .user-info-modal .btn-close {
            background: #e53935;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
        }
        
        .user-info-modal .btn-close:hover {
            background: #c62828;
        }
        /* Pagination Styles */
.pagination {
    display: flex;
    justify-content: center;
    margin-top: 30px;
    padding: 20px 0;
    flex-wrap: wrap;
}

.pagination-link {
    padding: 8px 16px;
    margin: 0 4px;
    border: 1px solid #ddd;
    text-decoration: none;
    color: #2e7d32;
    border-radius: 4px;
    transition: all 0.3s ease;
}

.pagination-link:hover {
    background-color: #e9e9e9;
}

.pagination-link.active {
    background-color: #2e7d32;
    color: white;
    border-color: #2e7d32;
}

.pagination-link.disabled {
    color: #ccc;
    pointer-events: none;
}
    </style>
</head>
<body>
    <!-- Include Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Admin Main Content -->
    <main class="admin-container">
        <div class="admin-user-section">
            <div>
                <h2>Panneau d'Administration</h2>
                <p>Bienvenue dans l'interface d'administration du forum Green.tn</p>
            </div>
            <div class="admin-actions">
                <a href="<?php echo $basePath; ?>back.php" class="logout-btn">Retour au Dashboard</a>
            </div>
        </div>
        <h2>Forum Content</h2>

        <?php
        // Include database connection
        require_once 'db_connect.php';
        // Pagination configuration - THIS IS THE CRITICAL FIX
$postsPerPage =4; // Define this FIRST before any calculations
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($currentPage < 1) $currentPage = 1;
$offset = ($currentPage - 1) * $postsPerPage;

try {
    // Count total posts
    $countQuery = "SELECT COUNT(*) as total FROM post WHERE is_deleted = 0";
    $countStmt = $conn->query($countQuery);
    $totalPosts = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($totalPosts / $postsPerPage);

    // Get paginated posts - YOUR EXACT QUERY
    $postQuery = "SELECT p.*, u.username, 
                 (SELECT COUNT(*) FROM commentaire c WHERE c.post_id = p.post_id AND c.is_deleted = 0) as comment_count 
                 FROM post p 
                 JOIN users u ON p.user_id = u.id 
                 WHERE p.is_deleted = 0 
                 ORDER BY p.created_at DESC
                 LIMIT :limit OFFSET :offset";
    
    $postStmt = $conn->prepare($postQuery);
    $postStmt->bindValue(':limit', $postsPerPage, PDO::PARAM_INT);
    $postStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $postStmt->execute();


if ($postStmt->rowCount() > 0) {
    while ($post = $postStmt->fetch(PDO::FETCH_ASSOC)) {
        error_log("Post Data: " . print_r($post, true));
        error_log("Post ID: " . $post['post_id'] . " | Is Anonymous: " . $post['is_anonymous'] . " | Username: " . $post['username']);

        $postId = $post['post_id'];
        $stmt = $conn->prepare("SELECT u.id, u.username, u.email, p.is_anonymous 
                                FROM users u 
                                JOIN post p ON u.id = p.user_id 
                                WHERE p.post_id = :post_id");
        $stmt->bindParam(':post_id', $postId, PDO::PARAM_INT);
        $stmt->execute();
        ?>
        <div class="question-admin">
            <div class="post-header">
                <p>
                    <strong>Utilisateur:</strong> 
                    <?php 
                    if ((int)$post['is_anonymous'] === 1) {
                        echo "Anonyme";
                    } else {
                        echo htmlspecialchars($post['username']);
                    }
                    ?>
                    <button class="btn-info" data-user-id="<?php echo $post['post_id']; ?>">Infos</button>
                </p>
                <span class="timestamp">Posté le: <?php echo date('d/m/Y H/i', strtotime($post['created_at'])); ?></span>
            </div>
            <h3><?php echo htmlspecialchars($post['title']); ?></h3>
            <div class="post-content" id="post-content-<?php echo $post['post_id']; ?>">
                <p><?php echo htmlspecialchars($post['content']); ?></p>
            </div>
            <div class="admin-actions">
                <button class="delete" data-id="<?php echo $post['post_id']; ?>" data-type="post">
                    <i class="fas fa-trash-alt"></i> Supprimer
                </button>
                <button class="report" data-id="<?php echo $post['post_id']; ?>" data-type="post" 
                        data-reported="<?php echo $post['is_reported'] ? '1' : '0'; ?>">
                    <i class="fas fa-flag"></i> <?php echo $post['is_reported'] ? 'Déjà signalé' : 'Signaler'; ?>
                </button>
                <button class="btn translate-btn" data-id="<?php echo $post['post_id']; ?>" data-type="post">Traduire</button>
            </div>

            <!-- Comments for this post -->
            <div class="comments-section" id="comments-<?php echo $post['post_id']; ?>">
                <div class="comments-header">
                    <h4>Commentaires (<?php echo $post['comment_count']; ?>)</h4>
                    <button class="btn-icon view-comments-btn" 
                            data-post-id="<?php echo $post['post_id']; ?>" 
                            title="Voir les commentaires" 
                            data-state="closed"
                            data-comment-count="<?php echo $post['comment_count']; ?>">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                
                <?php if ($post['comment_count'] > 0): ?>
                    <div class="comments-list" style="display: none;">
                        <?php
                        // Fetch comments for this post
                        $commentQuery = "SELECT c.*, u.username 
                                       FROM commentaire c 
                                       JOIN users u ON c.user_id = u.id 
                                       WHERE c.post_id = :post_id AND c.is_deleted = 0 
                                       ORDER BY c.created_at ASC";
                        $commentStmt = $conn->prepare($commentQuery);
                        $commentStmt->bindParam(':post_id', $post['post_id']);
                        $commentStmt->execute();
                        
                        while ($comment = $commentStmt->fetch(PDO::FETCH_ASSOC)): ?>
                            <div class="comment-admin">
                                <div class="comment-header">
                                    <p><strong><?php echo htmlspecialchars($comment['username']); ?></strong> 
                                    <span class="timestamp">le <?php echo date('d/m/Y H/i', strtotime($comment['created_at'])); ?></span></p>
                                </div>
                                <div class="comment-content" id="comment-content-<?php echo $comment['comment_id']; ?>">
                                    <p><?php echo htmlspecialchars($comment['content']); ?></p>
                                </div>
                                <div class="admin-actions">
                                    <button class="delete" data-id="<?php echo $comment['comment_id']; ?>" data-type="comment">
                                        <i class="fas fa-trash-alt"></i> Supprimer
                                    </button>
                                    <button class="report" data-id="<?php echo $comment['comment_id']; ?>" data-type="comment" 
                                            data-reported="<?php echo $comment['is_reported'] ? '1' : '0'; ?>">
                                        <i class="fas fa-flag"></i> <?php echo $comment['is_reported'] ? 'Déjà signalé' : 'Signaler'; ?>
                                    </button>
                                    <button class="btn translate-btn" data-id="<?php echo $comment['comment_id']; ?>" data-type="comment">Traduire</button>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="no-comments">Aucun commentaire pour le moment.</div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    } // End of while loop
} else {
    echo '<div class="no-posts">Aucun post trouvé.</div>';
}
} catch(PDOException $e) {
    echo '<p class="error">Erreur de base de données: ' . $e->getMessage() . '</p>';
}
?>
<!-- Pagination -->
<div class="pagination">
            <?php if ($totalPages > 1): ?>
                <?php if ($currentPage > 1): ?>
                    <a href="?page=1" class="pagination-link">&laquo; Première</a>
                    <a href="?page=<?php echo $currentPage - 1; ?>" class="pagination-link">&lsaquo; Précédente</a>
                <?php endif; ?>

                <?php 
                $start = max(1, $currentPage - 2);
                $end = min($totalPages, $currentPage + 2);
                
                for ($i = $start; $i <= $end; $i++): ?>
                    <a href="?page=<?php echo $i; ?>" class="pagination-link <?php echo $i == $currentPage ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>

                <?php if ($currentPage < $totalPages): ?>
                    <a href="?page=<?php echo $currentPage + 1; ?>" class="pagination-link">Suivante &rsaquo;</a>
                    <a href="?page=<?php echo $totalPages; ?>" class="pagination-link">Dernière &raquo;</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
<script src="../Forum/sidebar.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Handle "Infos" button click
    document.body.addEventListener('click', function (event) {
        if (event.target.classList.contains('btn-info')) {
            const userId = event.target.getAttribute('data-user-id');
            showUserInfo(userId);
        }
    });
});


        function showUserInfo(userId) {
            if (!userId) return;

            // Fetch user information from the server
            fetch(`get_user_info.php?user_id=${userId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    console.log(data); // Check the response in the console
                    if (data.success) {
                        // Display user information
                        const userInfoHtml = `
                            <div class="user-info-modal">
                                <h3>Informations sur l'utilisateur</h3>
                                <p><strong>ID :</strong> ${data.user.id}</p>
                                <p><strong>Nom d'utilisateur :</strong> ${data.user.username}</p>
                                <p><strong>Email :</strong> ${data.user.email}</p>
                                <p><strong>Post Anonyme :</strong> ${data.user.is_anonymous ? 'Oui' : 'Non'}</p>
                                <button class="btn-close" onclick="closeUserInfo()">Fermer</button>
                            </div>
                        `;
                        document.body.insertAdjacentHTML('beforeend', userInfoHtml);
                    } else {
                        alert(data.message || 'Impossible de récupérer les informations de l\'utilisateur.');
                    }
                })
                .catch(error => {
                    console.error('Error fetching user info:', error);
                    alert('Une erreur est survenue lors de la récupération des informations.');
                });
        }

        function closeUserInfo() {
            const modal = document.querySelector('.user-info-modal');
            if (modal) {
                modal.remove();
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Handle delete buttons
            document.querySelectorAll('.delete').forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const type = this.getAttribute('data-type');
                    if (confirm(`Êtes-vous sûr de vouloir supprimer cet élément ?`)) {
                        // Send delete request to server
                        fetch(`admin_actions.php?action=delete&type=${type}&id=${id}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Remove element from DOM or refresh page
                                window.location.reload();
                            } else {
                                alert('Erreur: ' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Une erreur est survenue lors de la suppression.');
                        });
                    }
                });
            });
            
            // Handle report buttons
            document.querySelectorAll('.report').forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const type = this.getAttribute('data-type');
                    const isReported = this.getAttribute('data-reported') === '1';
                    
                    if (isReported) {
                        alert('Cet élément a déjà été signalé.');
                        return;
                    }
                    
                    if (confirm(`Voulez-vous signaler cet élément ?`)) {
                        // Send report request to server
                        fetch(`admin_actions.php?action=report&type=${type}&id=${id}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                this.textContent = 'Déjà signalé';
                                this.setAttribute('data-reported', '1');
                            } else {
                                alert('Erreur: ' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Une erreur est survenue lors du signalement.');
                        });
                    }
                });
            });
            // Handle view comments buttons
document.querySelectorAll('.view-comments-btn').forEach(button => {
    button.addEventListener('click', function() {
        const postId = this.getAttribute('data-post-id');
        const commentsList = this.closest('.comments-section').querySelector('.comments-list');
        const state = this.getAttribute('data-state');
        
        if (state === 'closed') {
            commentsList.style.display = 'block';
            this.setAttribute('data-state', 'open');
            this.innerHTML = '<i class="fas fa-eye-slash"></i>';
        } else {
            commentsList.style.display = 'none';
            this.setAttribute('data-state', 'closed');
            this.innerHTML = '<i class="fas fa-eye"></i>';
        }
    });
});
            // Handle translate buttons
            document.body.addEventListener('click', function (event) {
                if (event.target.classList.contains('translate-btn')) {
                    const id = event.target.getAttribute('data-id');
                    const type = event.target.getAttribute('data-type');
                    const contentElement = document.querySelector(`#${type}-content-${id}`);

                    if (!contentElement) {
                        alert('Contenu introuvable pour la traduction.');
                        return;
                    }

                    // Fetch the content to translate
                    const originalText = contentElement.textContent;

                    // Send the translation request
                    fetch('translate.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            q: originalText,
                            source: 'en', // Source language
                            target: 'fr', // Target language
                        }),
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Update the content with the translated text
                            contentElement.textContent = data.translatedText;
                        } else {
                            alert('Erreur: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Une erreur est survenue lors de la traduction.');
                    });
                }
            });
        });
        </script>
        <!-- Add more questions here -->
         <!-- Pagination -->
<div class="pagination">
    <?php if ($totalPages > 1): ?>
        <?php if ($currentPage > 1): ?>
            <a href="?page=1" class="pagination-link">&laquo; Première</a>
            <a href="?page=<?php echo $currentPage - 1; ?>" class="pagination-link">&lsaquo; Précédente</a>
        <?php endif; ?>

        <?php 
        // Afficher les numéros de page
        $start = max(1, $currentPage - 2);
        $end = min($totalPages, $currentPage + 2);
        
        for ($i = $start; $i <= $end; $i++): ?>
            <a href="?page=<?php echo $i; ?>" class="pagination-link <?php echo $i == $currentPage ? 'active' : ''; ?>">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>

        <?php if ($currentPage < $totalPages): ?>
            <a href="?page=<?php echo $currentPage + 1; ?>" class="pagination-link">Suivante &rsaquo;</a>
            <a href="?page=<?php echo $totalPages; ?>" class="pagination-link">Dernière &raquo;</a>
        <?php endif; ?>
    <?php endif; ?>
</div>
    </main>
</body>
</html>

