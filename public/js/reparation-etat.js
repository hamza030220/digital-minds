let allRepairs = [];
let selectedRating = 0;
let currentRepairId = null;

function escapeHTML(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function fetchRepairs() {
    fetch('/projetweb/api/get_repairs.php')
        .then(response => {
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            return response.json();
        })
        .then(data => {
            allRepairs = data;
            renderRepairs(allRepairs);
        })
        .catch(error => {
            console.error('Error fetching repairs:', error);
            document.getElementById('error-message').innerText = 'Erreur lors du chargement des réparations';
            document.getElementById('error-message').style.display = 'block';
        });
}

function renderRepairs(repairs) {
    const grid = document.getElementById('reparation-grid');
    grid.innerHTML = '';
    if (!repairs || repairs.length === 0) {
        grid.innerHTML = '<p>Aucune réparation trouvée.</p>';
        return;
    }

    const bikeImages = {
        'Vélo de Ville': '/projetweb/images/vv.jpg',
        'Vélo de Course': '/projetweb/images/co.jpg',
        'Vélo de Montagne': '/projetweb/images/mm.jpg'
    };

    repairs.forEach(repair => {
        const statusClass = repair.status.toLowerCase().replace(' ', '-') || 'en-cours';
        const bikeImage = bikeImages[repair.bike_type] || '/projetweb/images/vv.jpg';
        const estimatedDate = calculateEstimatedDate(repair.status, repair.progression);
        const averageRating = repair.average_rating ? `${repair.average_rating} ★` : '';

        const card = document.createElement('div');
        card.className = `reparation-card`;
        card.setAttribute('data-status', statusClass);
        card.innerHTML = `
            <img src="${bikeImage}" alt="${escapeHTML(repair.bike_type)}" class="bike-image">
            <h3>${escapeHTML(repair.bike_type)} #${escapeHTML(repair.bike_id)}</h3>
            <p>Pièce: ${escapeHTML(repair.stock_item_name || 'Aucune pièce')}</p>
            <p class="status ${statusClass}">Statut: ${escapeHTML(repair.status)}</p>
            <div class="progress-bar">
                <div class="progress" style="width: ${repair.progression}%;"></div>
            </div>
            <p>Date estimée: ${estimatedDate}</p>
            ${repair.progression == 100 && averageRating ? `<p>Évaluation moyenne: ${averageRating}</p>` : ''}
            <button class="detail" onclick='showRepairDetails(${JSON.stringify(repair)})'>Détails</button>
            <button class="stock-btn" onclick="showStockDetails(${repair.stock_id || 0})" ${!repair.stock_id ? 'disabled' : ''}>
                Détails du stock
            </button>
        `;
        grid.appendChild(card);
    });
}

function calculateEstimatedDate(status, progression) {
    const baseDate = new Date('2025-05-15');
    let daysToAdd = status === 'En cours' ? Math.round((100 - progression) / 10) : 11;
    baseDate.setDate(baseDate.getDate() + daysToAdd);
    return baseDate.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

function showStockDetails(stockId) {
    if (!stockId || stockId === 0) {
        alert('Aucune pièce assignée');
        return;
    }
    fetch('/projetweb/api/get_stock.php')
        .then(response => {
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            return response.json();
        })
        .then(data => {
            const stockItem = data.find(item => item.id == stockId);
            if (!stockItem) {
                alert('Pièce de stock non trouvée');
                return;
            }
            const stockImages = {
                'Freins': '/projetweb/images/freins.jpg',
                'Chaîne': '/projetweb/images/chain.jpg'
            };
            const stockImage = stockImages[stockItem.item_name] || '/projetweb/images/vv.jpg';

            const modal = document.getElementById('stock-modal');
            const stockDetails = document.getElementById('stock-details');
            stockDetails.innerHTML = `
                <img src="${stockImage}" alt="${escapeHTML(stockItem.item_name)}" class="modal-stock-image">
                <p><strong>Pièce:</strong> ${escapeHTML(stockItem.item_name)}</p>
                <p><strong>Catégorie:</strong> ${escapeHTML(stockItem.category)}</p>
                <p><strong>Quantité:</strong> ${stockItem.quantity}</p>
                <p><strong>Prix:</strong> ${stockItem.price.toFixed(2)} TND</p>
            `;
            modal.style.display = 'flex';
        })
        .catch(error => {
            console.error('Error fetching stock:', error);
            alert('Erreur lors du chargement des détails du stock');
        });
}

function showRepairDetails(repair) {
    currentRepairId = repair.id;
    const modal = document.getElementById('details-modal');
    const repairDetails = document.getElementById('repair-details');
    const reviewForm = document.getElementById('review-form');
    const reviewAverage = document.getElementById('review-average');
    const estimatedDate = calculateEstimatedDate(repair.status, repair.progression);
    const bikeImages = {
        'Vélo de Ville': '/projetweb/images/vv.jpg',
        'Vélo de Course': '/projetweb/images/co.jpg',
        'Vélo de Montagne': '/projetweb/images/mm.jpg'
    };
    const bikeImage = bikeImages[repair.bike_type] || '/projetweb/images/vv.jpg';

    repairDetails.innerHTML = `
        <img src="${bikeImage}" alt="${escapeHTML(repair.bike_type)}" class="modal-bike-image">
        <p><strong>ID du vélo:</strong> ${escapeHTML(repair.bike_id)}</p>
        <p><strong>Type de vélo:</strong> ${escapeHTML(repair.bike_type)}</p>
        <p><strong>Pièce:</strong> ${escapeHTML(repair.stock_item_name || 'Aucune pièce')}</p>
        <p><strong>Statut:</strong> ${escapeHTML(repair.status)}</p>
        <p><strong>Progrès:</strong> ${repair.progression}%</p>
        <p><strong>Date estimée:</strong> ${estimatedDate}</p>
    `;

    if (repair.progression == 100) {
        reviewForm.style.display = 'block';
        resetStarRating();
        if (repair.average_rating) {
            reviewAverage.style.display = 'block';
            reviewAverage.querySelector('span').textContent = `${repair.average_rating} ★`;
        } else {
            reviewAverage.style.display = 'none';
        }
    } else {
        reviewForm.style.display = 'none';
        reviewAverage.style.display = 'none';
    }

    modal.style.display = 'flex';
}

function resetStarRating() {
    selectedRating = 0;
    const ratingDisplay = document.getElementById('rating-display');
    ratingDisplay.textContent = '';

    document.querySelectorAll('.star').forEach(star => {
        star.classList.remove('selected');
        star.tabIndex = 0;

        star.addEventListener('click', () => {
            selectedRating = parseInt(star.getAttribute('data-value'));
            updateStarSelection();
        });

        star.addEventListener('mouseover', () => {
            const value = parseInt(star.getAttribute('data-value'));
            document.querySelectorAll('.star').forEach(s => {
                s.classList.remove('selected');
                if (parseInt(s.getAttribute('data-value')) <= value) {
                    s.classList.add('selected');
                }
            });
        });

        star.addEventListener('mouseout', () => {
            updateStarSelection();
        });

        star.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                selectedRating = parseInt(star.getAttribute('data-value'));
                updateStarSelection();
            }
        });
    });

    function updateStarSelection() {
        document.querySelectorAll('.star').forEach(s => {
            s.classList.remove('selected');
            if (parseInt(s.getAttribute('data-value')) <= selectedRating) {
                s.classList.add('selected');
            }
        });
        ratingDisplay.textContent = selectedRating ? `${selectedRating} ${selectedRating === 1 ? 'étoile' : 'étoiles'}` : '';
    }
}

function submitReview() {
    if (!selectedRating) {
        alert('Veuillez sélectionner une évaluation');
        return;
    }

    fetch('/projetweb/api/submit_repair_rating.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ repair_id: currentRepairId, rating: selectedRating })
    })
        .then(response => {
            if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
            return response.json();
        })
        .then(data => {
            if (data.success) {
                document.getElementById('success-message').innerText = 'Évaluation soumise avec succès !';
                document.getElementById('success-message').style.display = 'block';
                document.getElementById('review-form').style.display = 'none';
                fetchRepairs();
            } else {
                document.getElementById('error-message').innerText = data.error || 'Erreur lors de la soumission';
                document.getElementById('error-message').style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Error submitting rating:', error);
            document.getElementById('error-message').innerText = 'Erreur lors de la soumission de l\'évaluation';
            document.getElementById('error-message').style.display = 'block';
        });
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    modal.style.display = 'none';
    document.getElementById('error-message').style.display = 'none';
    document.getElementById('success-message').style.display = 'none';
}

document.querySelectorAll('.modal').forEach(modal => {
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            closeModal(modal.id);
        }
    });
});

function searchBikes() {
    const searchInput = document.getElementById('search-input').value.toLowerCase();
    const filteredRepairs = allRepairs.filter(repair => 
        repair.bike_id.toLowerCase().includes(searchInput) || 
        repair.bike_type.toLowerCase().includes(searchInput) ||
        (repair.stock_item_name && repair.stock_item_name.toLowerCase().includes(searchInput))
    );
    renderRepairs(filteredRepairs);
}

document.querySelectorAll('.filter-btn').forEach(button => {
    button.addEventListener('click', () => {
        document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
        button.classList.add('active');

        const filter = button.getAttribute('data-filter');
        let filteredRepairs = allRepairs;

        if (filter !== 'all') {
            const statusFilterMap = {
                'en-cours': 'En cours',
                'en-attente-de-pièces': 'En attente de pièces',
                'terminé': 'Terminé'
            };
            filteredRepairs = allRepairs.filter(repair => repair.status === statusFilterMap[filter]);
        }

        renderRepairs(filteredRepairs);
    });
});

document.addEventListener('DOMContentLoaded', () => {
    fetchRepairs();
});