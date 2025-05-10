// Sidebar toggle functionality
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const main = document.getElementById('main');
    const sidebarToggle = document.getElementById('sidebarToggle');
    
    // Toggle sidebar on button click
    sidebarToggle.addEventListener('click', function() {
        sidebar.classList.toggle('show');
    });
    
    // Handle responsive behavior
    function handleResize() {
        if (window.innerWidth <= 992) {
            sidebar.classList.remove('show');
            main.classList.remove('main-content-expanded');
        }
    }
    
    // Initial call and resize listener
    handleResize();
    window.addEventListener('resize', handleResize);
});