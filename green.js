// Search Functionality with Input Validation
function searchBikes() {
    const searchInput = document.getElementById('search-input').value.trim().toLowerCase();
    const reparationCards = document.querySelectorAll('.reparation-card');

    // Input validation
    if (searchInput === '') {
        alert('Veuillez entrer un ID de vélo ou un type de vélo.');
        return;
    }

    // Basic validation to prevent malicious input
    const validInputPattern = /^[a-zA-Z0-9\s#]+$/;
    if (!validInputPattern.test(searchInput)) {
        alert('Caractères non valides. Utilisez uniquement des lettres, chiffres, espaces ou #.');
        return;
    }

    // Search logic
    reparationCards.forEach(card => {
        const bikeTitle = card.querySelector('h3').textContent.toLowerCase();
        const bikeProblem = card.querySelector('p').textContent.toLowerCase();

        if (bikeTitle.includes(searchInput) || bikeProblem.includes(searchInput)) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

// Filter Buttons Functionality
function setupFilterButtons() {
    const filterButtons = document.querySelectorAll('.filter-btn');
    const reparationCards = document.querySelectorAll('.reparation-card');

    filterButtons.forEach(button => {
        button.addEventListener('click', () => {
            const filter = button.getAttribute('data-filter');

            // Remove active class from all buttons
            filterButtons.forEach(btn => btn.classList.remove('active'));
            // Add active class to clicked button
            button.classList.add('active');

            // Filter cards
            reparationCards.forEach(card => {
                const status = card.getAttribute('data-status');
                if (filter === 'all' || status === filter) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });
}

// Detail Buttons Functionality
function setupDetailButtons() {
    const detailButtons = document.querySelectorAll('.detail');

    detailButtons.forEach(button => {
        button.addEventListener('click', () => {
            const card = button.closest('.reparation-card');
            const bikeTitle = card.querySelector('h3').textContent;
            const bikeProblem = card.querySelector('p:nth-of-type(1)').textContent;
            const bikeStatus = card.querySelector('.status').textContent;
            const bikeDate = card.querySelector('p:nth-of-type(2)').textContent;

            // Show details in an alert
            alert(`
                Détails du vélo:
                - ${bikeTitle}
                - ${bikeProblem}
                - ${bikeStatus}
                - ${bikeDate}
            `);
        });
    });
}

// Initialize functionalities when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    setupFilterButtons();
    setupDetailButtons();

    // Allow Enter key to trigger search
    document.getElementById('search-input').addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            searchBikes();
        }
    });
});