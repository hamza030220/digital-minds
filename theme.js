// theme.js
window.addEventListener("DOMContentLoaded", () => {
    // Vérifier si un thème est stocké dans le localStorage
    const theme = localStorage.getItem("theme");
    if (theme === "dark") {
        document.body.classList.add("dark-mode");
    }

    // Ajout du bouton pour changer de thème
    const themeToggleButton = document.querySelector(".theme-toggle");
    if (themeToggleButton) {
        themeToggleButton.addEventListener("click", toggleTheme);
    }
});

// Fonction pour basculer le thème sombre
function toggleTheme() {
    const isDark = document.body.classList.toggle("dark-mode");
    localStorage.setItem("theme", isDark ? "dark" : "light");
}

document.getElementById('toggleModeBtn').addEventListener('click', function () {
    document.body.classList.toggle('dark-mode');
    document.querySelector('.sidebar').classList.toggle('dark-mode');
    document.querySelector('.main-content').classList.toggle('dark-mode');
    document.querySelector('.user-table').classList.toggle('dark-mode');
    document.querySelector('.stats-container').classList.toggle('dark-mode');
    let mode = document.body.classList.contains('dark-mode') ? 'dark' : 'light';
    localStorage.setItem('theme', mode); // Sauvegarde le choix du mode dans le stockage local
});

// Charger le thème sauvegardé
window.addEventListener('load', function () {
    let theme = localStorage.getItem('theme');
    if (theme === 'dark') {
        document.body.classList.add('dark-mode');
        document.querySelector('.sidebar').classList.add('dark-mode');
        document.querySelector('.main-content').classList.add('dark-mode');
        document.querySelector('.user-table').classList.add('dark-mode');
        document.querySelector('.stats-container').classList.add('dark-mode');
    }
});
