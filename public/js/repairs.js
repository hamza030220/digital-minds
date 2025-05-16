console.log('Loading repairs.js');

let repairItems = [];
let currentAddForm = null;
let currentEditRow = null;
let stockItems = [];

const CONTROLLER_PATH = '/projetweb/CONTROLLER/RepairController.php';
const STOCK_CONTROLLER_PATH = '/projetweb/CONTROLLER/StockController.php';

// Fetch stock items for dropdown
function fetchStock(callback) {
    fetch(`${STOCK_CONTROLLER_PATH}?action=get_all`)
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            stockItems = data;
            if (callback) callback();
        })
        .catch(error => {
            console.error('Error fetching stock:', error);
            const errorSpan = document.createElement('span');
            errorSpan.className = 'error-message';
            errorSpan.textContent = `Erreur lors du chargement des stocks: ${escapeHTML(error.message)}`;
            document.getElementById('add-form-container')?.appendChild(errorSpan);
        });
}

// Fetch and display repairs
function fetchRepairs() {
    console.log(`Fetching repair items from: ${CONTROLLER_PATH}?action=get_all`);
    fetch(`${CONTROLLER_PATH}?action=get_all`)
        .then(response => {
            console.log(`Fetch response status: ${response.status} (${response.statusText})`);
            if (!response.ok) throw new Error(`HTTP ${response.status} (${response.statusText})`);
            return response.json();
        })
        .then(data => {
            if (data.error) throw new Error(data.error);
            if (!Array.isArray(data)) throw new Error('Expected an array of repairs');
            repairItems = data; // Store repairs globally
            const tbody = document.getElementById('repairs-table-body');
            tbody.innerHTML = '';
            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6">Aucune réparation trouvée</td></tr>';
                return;
            }

            // Get sorting criteria
            const sortSelect = document.getElementById('sortRepairs');
            const sortValue = sortSelect ? sortSelect.value : 'progress-desc';
            const [sortField, sortOrder] = sortValue.split('-');

            // Sort data by progression
            data.sort((a, b) => {
                const progA = parseInt(a.progression);
                const progB = parseInt(b.progression);
                return sortOrder === 'asc' ? progA - progB : progB - progA;
            });

            // Render sorted data
            data.forEach(repair => {
                if (parseInt(repair.progression) === 100) {
                    repair.status = 'Terminé';
                }
                const row = document.createElement('tr');
                const statusClass = repair.status.toLowerCase().replace(' ', '-') || 'en-cours';
                row.innerHTML = `
                    <td>${escapeHTML(repair.bike_id)}</td>
                    <td>${escapeHTML(repair.bike_type)}</td>
                    <td>${escapeHTML(repair.problem || 'Aucun')}</td>
                    <td><span class="status ${statusClass}">${escapeHTML(repair.status)}</span></td>
                    <td>
                        <div class="progress-bar">
                            <div class="progress" style="width: ${repair.progression}%;"></div>
                        </div>
                    </td>
                    <td>
                        <a href="#" class="btn edit" onclick="editRepair(${repair.id}, '${escapeHTML(repair.bike_id)}', '${escapeHTML(repair.bike_type)}', '${escapeHTML(repair.problem)}', ${repair.stock_id || 0}, '${escapeHTML(repair.status)}', ${repair.progression})">Modifier</a>
                        <a href="#" class="btn delete" onclick="deleteRepair(${repair.id})">Supprimer</a>
                    </td>
                `;
                tbody.appendChild(row);
            });
        })
        .catch(error => {
            console.error('Error fetching repair items:', error);
            const tbody = document.getElementById('repairs-table-body');
            tbody.innerHTML = `<tr><td colspan="6">Erreur lors du chargement des données: ${escapeHTML(error.message)}</td></tr>`;
        });
}

// Add repair form
function addRepair() {
    if (currentAddForm) {
        currentAddForm.remove();
        currentAddForm = null;
    }
    if (currentEditRow) {
        currentEditRow.remove();
        currentEditRow = null;
    }

    fetchStock(() => {
        const addFormContainer = document.getElementById('add-form-container');
        addFormContainer.innerHTML = ''; // Clear previous content
        const addForm = document.createElement('div');
        addForm.className = 'add-form';
        addForm.innerHTML = `
            <div class="form-header">
                <h3>Ajouter une Réparation</h3>
            </div>
            <div class="form-body">
                <label>ID Vélo: 
                    <input type="text" id="addBikeId" placeholder="Ex: 001">
                    <span class="error-message" id="addBikeIdError"></span>
                </label>
                <label>Type: 
                    <input type="text" id="addBikeType" placeholder="Ex: Vélo de Ville">
                    <span class="error-message" id="addBikeTypeError"></span>
                </label>
                <label>Problème: 
                    <input type="text" id="addProblem" placeholder="Ex: Freins usés">
                    <span class="error-message" id="addProblemError"></span>
                </label>
                <label>Pièce Utilisée: 
                    <select id="addStockId" class="stock-select" onchange="updateAddStatus()">
                        <option value="0" disabled selected>Sélectionner une pièce</option>
                        ${stockItems.map(item => `<option value="${item.id}" data-quantity="${item.quantity}">${escapeHTML(item.item_name)} (${item.category}, Stock: ${item.quantity})</option>`).join('')}
                    </select>
                    <span class="error-message" id="addStockIdError"></span>
                </label>
                <label>Statut: 
                    <select id="addStatus">
                        <option value="En attente">En attente</option>
                        <option value="En cours">En cours</option>
                        <option value="Terminé">Terminé</option>
                    </select>
                    <span class="error-message" id="addStatusError"></span>
                </label>
                <label>Progression (%): 
                    <input type="number" id="addProgression" value="0" min="0" max="100" onchange="updateAddStatus()">
                    <span class="error-message" id="addProgressionError"></span>
                </label>
            </div>
            <div class="form-footer">
                <button onclick="saveNewRepair()">Enregistrer</button>
                <button onclick="cancelAdd()">Annuler</button>
            </div>
        `;
        addFormContainer.appendChild(addForm);
        currentAddForm = addForm;
    });
}

function updateAddStatus() {
    const statusSelect = document.getElementById('addStatus');
    const progression = parseInt(document.getElementById('addProgression').value);

    if (progression === 100) {
        statusSelect.innerHTML = `<option value="Terminé" selected>Terminé</option>`;
        return;
    }

    statusSelect.innerHTML = `
        <option value="En attente">En attente</option>
        <option value="En cours">En cours</option>
        <option value="Terminé">Terminé</option>
    `;
}

function saveNewRepair() {
    const bikeId = document.getElementById('addBikeId').value.trim();
    const bikeType = document.getElementById('addBikeType').value.trim();
    const problem = document.getElementById('addProblem').value.trim();
    const stockId = parseInt(document.getElementById('addStockId').value);
    let status = document.getElementById('addStatus').value;
    const progression = parseInt(document.getElementById('addProgression').value);

    document.getElementById('addBikeIdError').textContent = '';
    document.getElementById('addBikeTypeError').textContent = '';
    document.getElementById('addProblemError').textContent = '';
    document.getElementById('addStockIdError').textContent = '';
    document.getElementById('addStatusError').textContent = '';
    document.getElementById('addProgressionError').textContent = '';

    let hasError = false;

    if (!bikeId.match(/^[a-zA-Z0-9-]+$/)) {
        document.getElementById('addBikeIdError').textContent = 'L\'ID du vélo doit être alphanumérique';
        hasError = true;
    }
    if (!bikeType || bikeType.length > 50) {
        document.getElementById('addBikeTypeError').textContent = 'Le type de vélo est requis et doit être inférieur à 50 caractères';
        hasError = true;
    }
    if (!problem) {
        document.getElementById('addProblemError').textContent = 'Le problème est requis';
        hasError = true;
    }
    if (stockId === 0) {
        document.getElementById('addStockIdError').textContent = 'Veuillez sélectionner une pièce';
        hasError = true;
    }
    if (!['En cours', 'En attente', 'Terminé'].includes(status)) {
        document.getElementById('addStatusError').textContent = 'Statut invalide';
        hasError = true;
    }
    if (isNaN(progression) || progression < 0 || progression > 100) {
        document.getElementById('addProgressionError').textContent = 'La progression doit être un nombre entre 0 et 100';
        hasError = true;
    }

    if (progression === 100) {
        status = 'Terminé';
    }

    if (hasError) return;

    const data = { bike_id: bikeId, bike_type: bikeType, problem, status, progression, stock_id: stockId };

    fetch(`${CONTROLLER_PATH}?action=add`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            if (data.success) {
                currentAddForm.remove();
                currentAddForm = null;
                fetchRepairs();
            } else {
                document.getElementById('add-form-container').insertAdjacentHTML('beforeend', `<span class="error-message">${escapeHTML(data.error || 'Ajout échoué')}</span>`);
            }
        })
        .catch(error => {
            console.error('Error adding repair:', error);
            document.getElementById('add-form-container').insertAdjacentHTML('beforeend', `<span class="error-message">Erreur lors de l'ajout: ${escapeHTML(error.message)}</span>`);
        });
}

function cancelAdd() {
    if (currentAddForm) {
        currentAddForm.remove();
        currentAddForm = null;
    }
}

function editRepair(id, bikeId, bikeType, problem, stockId, status, progression) {
    if (currentEditRow) {
        currentEditRow.remove();
        currentEditRow = null;
    }
    if (currentAddForm) {
        currentAddForm.remove();
        currentAddForm = null;
    }

    fetchStock(() => {
        const addFormContainer = document.getElementById('add-form-container');
        addFormContainer.innerHTML = ''; // Clear previous content
        const editForm = document.createElement('div');
        editForm.className = 'edit-form';
        editForm.innerHTML = `
            <div class="form-header">
                <h3>Modifier la Réparation</h3>
            </div>
            <div class="form-body">
                <input type="hidden" id="editId" value="${id}">
                <label>ID Vélo: 
                    <input type="text" id="editBikeId" value="${escapeHTML(bikeId)}">
                    <span class="error-message" id="editBikeIdError"></span>
                </label>
                <label>Type: 
                    <input type="text" id="editBikeType" value="${escapeHTML(bikeType)}">
                    <span class="error-message" id="editBikeTypeError"></span>
                </label>
                <label>Problème: 
                    <input type="text" id="editProblem" value="${escapeHTML(problem)}">
                    <span class="error-message" id="editProblemError"></span>
                </label>
                <label>Pièce Utilisée: 
                    <select id="editStockId" class="stock-select" onchange="updateEditStatus()">
                        <option value="0" disabled>Sélectionner une pièce</option>
                        ${stockItems.map(item => `
                            <option value="${item.id}" data-quantity="${item.quantity}" ${item.id == stockId ? 'selected' : ''}>
                                ${escapeHTML(item.item_name)} (${item.category}, Stock: ${item.quantity})
                            </option>
                        `).join('')}
                    </select>
                    <span class="error-message" id="editStockIdError"></span>
                </label>
                <label>Statut: 
                    <select id="editStatus">
                        <option value="En attente" ${status === 'En attente' ? 'selected' : ''}>En attente</option>
                        <option value="En cours" ${status === 'En cours' ? 'selected' : ''}>En cours</option>
                        <option value="Terminé" ${status === 'Terminé' ? 'selected' : ''}>Terminé</option>
                    </select>
                    <span class="error-message" id="editStatusError"></span>
                </label>
                <label>Progression (%): 
                    <input type="number" id="editProgression" value="${progression}" min="0" max="100" onchange="updateEditStatus()">
                    <span class="error-message" id="editProgressionError"></span>
                </label>
            </div>
            <div class="form-footer">
                <button onclick="saveRepair()">Enregistrer</button>
                <button onclick="cancelEdit()">Annuler</button>
            </div>
        `;
        addFormContainer.appendChild(editForm);
        currentEditRow = editForm;
        updateEditStatus();
    });
}

function updateEditStatus() {
    const statusSelect = document.getElementById('editStatus');
    const progression = parseInt(document.getElementById('editProgression').value);

    if (progression === 100) {
        statusSelect.innerHTML = `<option value="Terminé" selected>Terminé</option>`;
        return;
    }

    statusSelect.innerHTML = `
        <option value="En attente">En attente</option>
        <option value="En cours">En cours</option>
        <option value="Terminé">Terminé</option>
    `;
}

function saveRepair() {
    const id = document.getElementById('editId').value;
    const bikeId = document.getElementById('editBikeId').value.trim();
    const bikeType = document.getElementById('editBikeType').value.trim();
    const problem = document.getElementById('editProblem').value.trim();
    const stockId = parseInt(document.getElementById('editStockId').value);
    let status = document.getElementById('editStatus').value;
    const progression = parseInt(document.getElementById('editProgression').value);

    document.getElementById('editBikeIdError').textContent = '';
    document.getElementById('editBikeTypeError').textContent = '';
    document.getElementById('editProblemError').textContent = '';
    document.getElementById('editStockIdError').textContent = '';
    document.getElementById('editStatusError').textContent = '';
    document.getElementById('editProgressionError').textContent = '';

    let hasError = false;

    if (!bikeId.match(/^[a-zA-Z0-9-]+$/)) {
        document.getElementById('editBikeIdError').textContent = 'L\'ID du vélo doit être alphanumérique';
        hasError = true;
    }
    if (!bikeType || bikeType.length > 50) {
        document.getElementById('editBikeTypeError').textContent = 'Le type de vélo est requis et doit être inférieur à 50 caractères';
        hasError = true;
    }
    if (!problem) {
        document.getElementById('editProblemError').textContent = 'Le problème est requis';
        hasError = true;
    }
    if (stockId === 0) {
        document.getElementById('editStockIdError').textContent = 'Veuillez sélectionner une pièce';
        hasError = true;
    }
    if (!['En cours', 'En attente', 'Terminé'].includes(status)) {
        document.getElementById('editStatusError').textContent = 'Statut invalide';
        hasError = true;
    }
    if (isNaN(progression) || progression < 0 || progression > 100) {
        document.getElementById('editProgressionError').textContent = 'La progression doit être un nombre entre 0 et 100';
        hasError = true;
    }

    if (progression === 100) {
        status = 'Terminé';
    }

    if (hasError) return;

    const data = { id, bike_id: bikeId, bike_type: bikeType, problem, status, progression, stock_id: stockId };

    fetch(`${CONTROLLER_PATH}?action=update`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            if (data.success) {
                currentEditRow.remove();
                currentEditRow = null;
                fetchRepairs();
            } else {
                currentEditRow.insertAdjacentHTML('beforeend', `<span class="error-message">${escapeHTML(data.error || 'Modification échouée')}</span>`);
            }
        })
        .catch(error => {
            console.error('Error updating repair:', error);
            currentEditRow.insertAdjacentHTML('beforeend', `<span class="error-message">Erreur lors de la modification: ${escapeHTML(error.message)}</span>`);
        });
}

function cancelEdit() {
    if (currentEditRow) {
        currentEditRow.remove();
        currentEditRow = null;
    }
}

function deleteRepair(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette réparation ?')) {
        fetch(`${CONTROLLER_PATH}?action=delete`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        })
            .then(response => {
                return response.json().then(data => {
                    if (!response.ok) {
                        throw new Error(data.error || `HTTP ${response.status}: Network response was not ok`);
                    }
                    return data;
                });
            })
            .then(data => {
                if (data.success) {
                    fetchRepairs();
                } else {
                    const tbody = document.getElementById('repairs-table-body');
                    tbody.insertAdjacentHTML('beforeend', `<tr><td colspan="6"><span class="error-message">${escapeHTML(data.error || 'Suppression échouée')}</span></td></tr>`);
                }
            })
            .catch(error => {
                console.error('Error deleting repair:', error);
                const tbody = document.getElementById('repairs-table-body');
                tbody.insertAdjacentHTML('beforeend', `<tr><td colspan="6"><span class="error-message">Erreur lors de la suppression: ${escapeHTML(error.message)}</span></td></tr>`);
            });
    }
}

// Open Repairs Export Modal
function openRepairsExportModal() {
    const modal = document.getElementById('repairsExportModal');
    const exportList = document.getElementById('repairsExportList');
    const exportError = document.getElementById('repairsExportError');
    exportError.textContent = '';
    exportList.innerHTML = '';

    fetch(`${CONTROLLER_PATH}?action=get_all`)
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            if (data.error) throw new Error(data.error);
            if (!Array.isArray(data)) throw new Error('Expected an array of repairs');

            if (data.length === 0) {
                exportList.innerHTML = '<p class="no-data">Aucune réparation trouvée</p>';
                modal.style.display = 'flex';
                return;
            }

            data.forEach(repair => {
                const statusClass = repair.status.toLowerCase().replace(' ', '-') || 'en-cours';
                const repairId = String(repair.id);
                const item = document.createElement('label');
                item.innerHTML = `
                    <input type="checkbox" id="export-repair-${repairId}" value='${JSON.stringify(repair)}' checked>
                    ${escapeHTML(repair.bike_id)} - ${escapeHTML(repair.bike_type)} 
                    (<span class="status ${statusClass}">${escapeHTML(repair.status)}</span>, ${repair.progression}%)
                `;
                exportList.appendChild(item);
            });

            modal.style.display = 'flex';
        })
        .catch(error => {
            console.error('Error fetching repairs for export:', error);
            exportError.textContent = `Erreur lors du chargement des données: ${escapeHTML(error.message)}`;
            exportList.innerHTML = '';
            modal.style.display = 'flex';
        });
}

// Close Repairs Export Modal
function closeRepairsExportModal() {
    document.getElementById('repairsExportModal').style.display = 'none';
}

// Export Repairs to PDF
function exportRepairsToPDF() {
    fetchStock(() => {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();

        try {
            // Get selected repairs from the modal
            const checkboxes = document.querySelectorAll('#repairsExportList input[type="checkbox"]:checked');
            if (checkboxes.length === 0) {
                document.getElementById('repairsExportError').textContent = 'Veuillez sélectionner au moins une réparation';
                return;
            }

            // Prepare table data
            const tableData = Array.from(checkboxes).map(checkbox => {
                const repair = JSON.parse(checkbox.value);
                return [
                    repair.bike_id,
                    repair.bike_type,
                    repair.problem || 'Aucun',
                    repair.status,
                    `${repair.progression}%`,
                    repair.stock_id ? stockItems.find(item => item.id === repair.stock_id)?.item_name || 'N/A' : 'N/A'
                ];
            });

            // Generate PDF with autoTable
            doc.setFontSize(16);
            doc.text('Rapport des Réparations', 14, 20);
            doc.setFontSize(12);
            doc.text(`Généré le: ${new Date().toLocaleDateString('fr-FR')}`, 14, 30);

            doc.autoTable({
                head: [['ID Vélo', 'Type', 'Problème', 'Statut', 'Progression', 'Pièce Utilisée']],
                body: tableData,
                startY: 40,
                theme: 'striped',
                headStyles: { fillColor: [0, 123, 255] },
                margin: { top: 40 }
            });

            doc.save('repairs_report.pdf');
            closeRepairsExportModal();
        } catch (error) {
            console.error('Error exporting PDF:', error);
            document.getElementById('repairsExportError').textContent = `Erreur lors de l'exportation: ${escapeHTML(error.message)}`;
        }
    });
}

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    console.log('Initializing repairs table');
    const sidebar = document.getElementById('sidebar');
    const main = document.getElementById('main');
    const sidebarToggle = document.getElementById('sidebarToggle');

    // Sidebar toggle
    sidebarToggle.addEventListener('click', function() {
        sidebar.classList.toggle('show');
    });

    // Handle responsive behavior
    function handleResize() {
        if (window.innerWidth <= 992) {
            sidebar.classList.remove('show');
            main.classList.remove('main-content-expanded');
        } else {
            sidebar.classList.add('show');
            main.classList.add('main-content-expanded');
        }
    }

    handleResize();
    window.addEventListener('resize', handleResize);

    // Load repairs
    fetchRepairs();
});