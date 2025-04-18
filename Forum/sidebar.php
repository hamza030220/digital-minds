<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a class="sidebar-brand" href="<?php echo $basePath; ?>dashboard.php">
            <img src="<?php echo $basePath; ?>../image/backend_logo.png" alt="Green Admin">
        </a>
    </div>
    <div class="sidebar-content">
        <ul class="sidebar-nav">
            <!-- Dashboard Link -->
            <li class="sidebar-nav-item">
                <a class="sidebar-nav-link <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>" href="<?php echo $basePath; ?>dashboard.php">
                    <span class="sidebar-nav-icon"><i class="bi bi-speedometer2"></i></span>
                    <span class="sidebar-nav-text">Dashboard</span>
                </a>
            </li>
            <!-- Forum Link -->
            <li class="sidebar-nav-item">
                <a class="sidebar-nav-link <?php echo $currentPage === 'forum' ? 'active' : ''; ?>" href="<?php echo $basePath; ?>back.php">
                    <span class="sidebar-nav-icon"><i class="bi bi-chat-dots"></i></span>
                    <span class="sidebar-nav-text">Forum</span>
                </a>
            </li>
        </ul>
    </div>
    <div class="sidebar-footer">
        <div class="mb-2">
            <span class="text-white">Bienvenue, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
        </div>
        <a href="<?php echo $basePath; ?>logout.php" class="btn">
            <i class="bi bi-box-arrow-right"></i> Déconnexion
        </a>
    </div>
</div>

<!-- Sidebar Toggle Button -->
<button class="sidebar-toggler" type="button" id="sidebarToggle">
    <i class="bi bi-list"></i>
</button>
<style>
:root {
    --sidebar-width: 250px;
    --sidebar-collapsed-width: 70px;
    --sidebar-color: #60BA97;
    --sidebar-text: #ffffff;
    --sidebar-active-color: #3498db;
    --sidebar-hover-bg: rgba(255, 255, 255, 0.1);
    --sidebar-transition: all 0.3s ease;
}

/* Sidebar Base Styles */
.sidebar {
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    width: var(--sidebar-width);
    background-color: var(--sidebar-color);
    color: var(--sidebar-text);
    transition: var(--sidebar-transition);
    z-index: 1000;
    overflow-y: auto;
    box-shadow: 2px 0 10px rgba(0, 0, 0, 0.15);
    display: flex;
    flex-direction: column;
}

/* Collapsed State */
.sidebar-collapsed {
    width: var(--sidebar-collapsed-width);
    overflow: hidden;
}

.sidebar-collapsed .sidebar-brand img,
.sidebar-collapsed .sidebar-nav-text,
.sidebar-collapsed .sidebar-footer span {
    opacity: 0;
    width: 0;
    height: 0;
    margin: 0;
    transition: var(--sidebar-transition);
}

/* Header Section */
.sidebar-header {
    padding: 20px 15px;
    text-align: center;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    flex-shrink: 0;
}

.sidebar-brand img {
    height: 40px;
    transition: var(--sidebar-transition);
    max-width: 100%;
}

/* Navigation Content */
.sidebar-content {
    flex: 1;
    padding: 15px 0;
    overflow-y: auto;
}

.sidebar-nav {
    list-style: none;
    padding: 0;
    margin: 0;
}

.sidebar-nav-item {
    position: relative;
}

.sidebar-nav-link {
    display: flex;
    align-items: center;
    padding: 12px 20px;
    color: rgba(255, 255, 255, 0.85);
    text-decoration: none;
    transition: var(--sidebar-transition);
    white-space: nowrap;
}

.sidebar-nav-link:hover {
    color: white;
    background-color: var(--sidebar-hover-bg);
}

.sidebar-nav-link.active {
    color: white;
    background-color: var(--sidebar-hover-bg);
    border-left: 3px solid var(--sidebar-active-color);
}

.sidebar-nav-icon {
    min-width: 24px;
    margin-right: 12px;
    font-size: 1.1rem;
    text-align: center;
    transition: var(--sidebar-transition);
}

.sidebar-nav-text {
    transition: var(--sidebar-transition);
}

/* Footer Section */
.sidebar-footer {
    padding: 15px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    flex-shrink: 0;
}

.sidebar-footer .btn {
    background-color: transparent; /* Transparent background */
    color: white; /* White text */
    border: 1px solid white; /* White border */
    padding: 8px 16px; /* Add padding */
    font-size: 14px; /* Adjust font size */
    font-weight: bold; /* Make text bold */
    border-radius: 4px; /* Slightly rounded corners */
    text-align: center;
    display: block;
    width: 100%; /* Full width */
    transition: all 0.3s ease; /* Smooth hover effect */
}

.sidebar-footer .btn:hover {
    background-color: white; /* White background on hover */
    color: var(--sidebar-color); /* Sidebar color for text */
    border-color: white; /* Keep the border white */
    transform: scale(1.05); /* Slight zoom effect */
}

/* Toggle Button */
.sidebar-toggler {
    position: fixed;
    top: 15px;
    left: 15px;
    z-index: 1010;
    display: none;
    cursor: pointer;
    padding: 8px 12px;
    background-color: var(--sidebar-color);
    color: white;
    border: none;
    border-radius: 4px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
    transition: var(--sidebar-transition);
}

.sidebar-toggler:hover {
    background-color: #34495e;
    transform: scale(1.05);
}

/* Main Content Adjustment */
.main-content {
    transition: margin-left 0.3s ease;
    margin-left: var(--sidebar-width);
}

.sidebar-collapsed + .main-content {
    margin-left: var(--sidebar-collapsed-width);
}

/* Responsive Behavior */
@media (max-width: 992px) {
    .sidebar {
        transform: translateX(-100%);
    }
    
    .sidebar.show {
        transform: translateX(0);
        box-shadow: 5px 0 15px rgba(0, 0, 0, 0.2);
    }
    
    .main-content {
        margin-left: 0 !important;
    }
    
    .sidebar-toggler {
        display: block;
    }
    
    .sidebar.show + .sidebar-toggler {
        left: calc(var(--sidebar-width) + 10px);
    }
}

/* Animation for smooth collapse/expand */
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.sidebar-nav-link, .sidebar-brand img {
    animation: fadeIn 0.3s ease-out;
}
</style>

<?php
echo '<pre>';
echo 'Current Page: ' . $currentPage . PHP_EOL;
echo 'Base Path: ' . $basePath . PHP_EOL;
echo '</pre>';
?>