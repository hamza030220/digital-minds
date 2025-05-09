console.log('Loading repairs.js');

let repairItems = [];
let currentRepairForm = null;

const CONTROLLER_PATH = '/green/controllers/RepairController.php';

function escapeHTML(str) {
    return str.replace(/[&<>"']/g, match => ({
        '&': '&',
        '<': '<',
        '>': '>',
        '"': '"',
        "'": '',
    })[match]);
}

function fetchRepairs(callback) {
    const url = `${CONTROLLER_PATH}?action=get_all`;
    console.log(`Fetching repairs from: ${url}`);
    fetch(url)
        .then(response => {
            console.log(`Fetch response status: ${response.status} (${response.statusText})`);
            if (!response.ok) {
                return response.text().then(text => {
                    console.error(`Fetch error: HTTP ${response.status} (${response.statusText}): ${text}`);
                    throw new Error(`HTTP ${response.status} (${response.statusText}): ${text}`);
                });
            }
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error(`JSON parse error: ${e.message}, Response: ${text}`);
                    throw new Error(`Invalid JSON: ${text}`);
                }
            });
        })
        .then(data => {
            console.log('Repair data received:', data);
            repairItems = data;
            if (callback) callback();
        })
        .catch(error => {
            console.error('Error fetching repairs:', error);
            const errorSpan = document.createElement('span');
            errorSpan.className = 'error-message';
            errorSpan.textContent = `Erreur lors du chargement des réparations: ${error.message}`;
            document.getElementById('repair-form-container').appendChild(errorSpan);
        });
}

function fetchRepairItems() {
    const url = `${CONTROLLER_PATH}?action=get_all`;
    console.log(`Fetching repair items from: ${url}`);
    fetch(url)
        .then(response => {
            console.log(`Fetch response status: ${response.status} (${response.statusText})`);
            if (!response.ok) {
                return response.text().then(text => {
                    console.error(`Fetch error: HTTP ${response.status} (${response.statusText}): ${text}`);
                    throw new Error(`HTTP ${response.status} (${response.statusText}): ${text}`);
                });
            }
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error(`JSON parse error: ${e.message}, Response: ${text}`);
                    throw new Error(`Invalid JSON: ${text}`);
                }
            });
        })
        .then(data => {
            console.log('Repair items received:', data);
            const tbody = document.getElementById('repairs-table-body');
            tbody.innerHTML = '';
            if (!Array.isArray(data)) {
                console.error('Expected an array, got:', data);
                throw new Error('Expected an array of repair items');
            }
            if (data.length === 0) {
                console.log('No repair items found in database');
                tbody.innerHTML = '<tr><td colspan="5">Aucune réparation enregistrée</td></tr>';
                return;
            }
            data.forEach((item, index) => {
                console.log(`Repair ${index + 1}:`, {
                    id: item.id,
                    bike_id: item.bike_id,
                    bike_type: item.bike_type,
                    problem: item.problem,
                    status: item.status,
                    progression: item.progression,
                    stock_id: item.stock_id
                });

                if (!item.id) console.warn(`Repair ${index + 1} missing id`);
                if (!item.bike_id) console.warn(`Repair ${index + 1} missing bike_id`);
                if (!item.bike_type) console.warn(`Repair ${index + 1} missing bike_type`);
                if (!item.status) console.warn(`Repair ${index + 1} missing status`);

                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${escapeHTML(item.id?.toString() || 'N/A')}</td>
                    <td>${escapeHTML(item.bike_id || 'N/A')}</td>
                    <td>${escapeHTML(item.bike_type || 'N/A')}</td>
                    <td>${escapeHTML(item.problem || 'N/A')}</td>
                    <td>${escapeHTML(item.status || 'N/A')}</td>
                    <td>
                        <a href="#" class="btn edit" onclick="editRepairItem(
                            ${item.id || 0},
                            '${escapeHTML(item.bike_id || '')}',
                            '${escapeHTML(item.bike_type || '')}',
                            '${escapeHTML(item.problem || '')}',
                            '${escapeHTML(item.status || 'En attente')}',
                            ${item.progression || 0},
                            ${item.stock_id || 0}
                        )">Modifier</a>
                        <a href="#" class="btn delete" onclick="deleteRepairItem(${item.id || 0})">Supprimer</a>
                    </td>
                `;
                tbody.appendChild(row);
            });
            console.log('Repairs table updated with', data.length, 'items');
        })
        .catch(error => {
            console.error('Error fetching repair items:', error);
            const tbody = document.getElementById('repairs-table-body');
            tbody.innerHTML = `<tr><td colspan="5">Erreur lors du chargement des données: ${escapeHTML(error.message)}</td></tr>`;
        });
}

function addRepairItem() {
    if (currentRepairForm) {
        currentRepairForm.remove();
        currentRepairForm = null;
    }

    const repairFormContainer = document.getElementById('repair-form-container');
    const repairForm = document.createElement('div');
    repairForm.className = 'add-form';
    repairForm.innerHTML = `
        <div class="form-header">
            <h3>Ajouter une Réparation</h3>
        </div>
        <div class="form-body">
            <label>Bike ID: 
                <input type="text" id="addBikeId" placeholder="Ex: 012">
                <span class="error-message" id="addBikeIdError"></span>
            </label>
            <label>Bike Type: 
                <input type="text" id="addBikeType" placeholder="Ex: Vélo de Course">
                <span class="error-message" id="addBikeTypeError"></span>
            </label>
            <label>Problème: 
                <input type="text" id="addProblem" placeholder="Ex: Pneu crev">
                <span class="error-message" id="addProblemError"></span>
            </label>
            <label>Statut: 
                <select id="addStatus">
                    <option value="En cours">En cours</option>
                    <option value="Terminé">Terminé</option>
                    <option value="En attente">En attente</option>
                </select>
                <span class="error-message" id="addStatusError"></span>
            </label>
            <label>Progression (%): 
                <input type="number" id="addProgression" min="0" max="100" value="0">
                <span class="error-message" id="addProgressionError"></span>
            </label>
            <label>Stock ID: 
                <input type="number" id="addStockId" min="1" value="1">
                <span class="error-message" id="addStockIdError"></span>
            </label>
        </div>
        <div class="form-footer">
            <button onclick="saveNewRepairItem()">Enregistrer</button>
            <button onclick="cancelRepairAdd()">Annuler</button>
        </div>
    `;
    repairFormContainer.appendChild(repairForm);
    currentRepairForm = repairForm;
}

function saveNewRepairItem() {
    const bikeId = document.getElementById('addBikeId').value.trim();
    const bikeType = document.getElementById('addBikeType').value.trim();
    const problem = document.getElementById('addProblem').value.trim();
    const status = document.getElementById('addStatus').value;
    const progression = parseInt(document.getElementById('addProgression').value);
    const stockId = parseInt(document.getElementById('addStockId').value);

    document.getElementById('addBikeIdError').textContent = '';
    document.getElementById('addBikeTypeError').textContent = '';
    document.getElementById('addProblemError').textContent = '';
    document.getElementById('addStatusError').textContent = '';
    document.getElementById('addProgressionError').textContent = '';
    document.getElementById('addStockIdError').textContent = '';

    let hasError = false;

    if (!bikeId || bikeId.length > 10) {
        document.getElementById('addBikeIdError').textContent = 'Bike ID is required and must be less than 10 characters';
        hasError = true;
    }
    if (!bikeType || bikeType.length > 50) {
        document.getElementById('addBikeTypeError').textContent = 'Bike Type is required and must be less than 50 characters';
        hasError = true;
    }
    if (problem.length > 65535) {
        document.getElementById('addProblemError').textContent = 'Problem must be less than 65535 characters';
        hasError = true;
    }
    if (!status) {
        document.getElementById('addStatusError').textContent = 'Status is required';
        hasError = true;
    }
    if (isNaN(progression) || progression < 0 || progression > 100) {
        document.getElementById('addProgressionError').textContent = 'Progression must be between 0 and 100';
        hasError = true;
    }
    if (isNaN(stockId) || stockId < 1) {
        document.getElementById('addStockIdError').textContent = 'Stock ID must be a positive number';
        hasError = true;
    }

    if (hasError) return;

    const data = { bike_id: bikeId, bike_type: bikeType, problem, status, progression, stock_id: stockId };

    console.log(`Adding repair item to: ${CONTROLLER_PATH}?action=add`, data);
    fetch(`${CONTROLLER_PATH}?action=add`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
        .then(response => {
            console.log(`Add response status: ${response.status} (${response.statusText})`);
            if (!response.ok) {
                return response.text().then(text => {
                    console.error(`Fetch error: HTTP ${response.status} (${response.statusText}): ${text}`);
                    throw new Error(`HTTP ${response.status} (${response.statusText}): ${text}`);
                });
            }
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error(`JSON parse error: ${e.message}, Response: ${text}`);
                    throw new Error(`Invalid JSON: ${text}`);
                }
            });
        })
        .then(data => {
            console.log('Add repair response:', data);
            if (data.success) {
                currentRepairForm.remove();
                currentRepairForm = null;
                fetchRepairItems();
            } else {
                document.getElementById('repair-form-container').insertAdjacentHTML('beforeend', `<span class="error-message">${escapeHTML(data.error || 'Ajout échoué')}</span>`);
            }
        })
        .catch(error => {
            console.error('Error adding repair item:', error);
            document.getElementById('repair-form-container').insertAdjacentHTML('beforeend', `<span class="error-message">Erreur lors de l'ajout: ${escapeHTML(error.message)}</span>`);
        });
}

function editRepairItem(id, bikeId, bikeType, problem, status, progression, stockId) {
    if (currentRepairForm) {
        currentRepairForm.remove();
        currentRepairForm = null;
    }

    const repairFormContainer = document.getElementById('repair-form-container');
    const repairForm = document.createElement('div');
    repairForm.className = 'edit-form';
    repairForm.innerHTML = `
        <div class="form-header">
            <h3>Modifier la Réparation #${id}</h3>
        </div>
        <div class="form-body">
            <label>Bike ID: 
                <input type="text" id="editBikeId" value="${escapeHTML(bikeId)}">
                <span class="error-message" id="editBikeIdError"></span>
            </label>
            <label>Bike Type: 
                <input type="text" id="editBikeType" value="${escapeHTML(bikeType)}">
                <span class="error-message" id="editBikeTypeError"></span>
            </label>
            <label>Problème: 
                <input type="text" id="editProblem" value="${escapeHTML(problem)}">
                <span class="error-message" id="editProblemError"></span>
            </label>
            <label>Statut: 
                <select id="editStatus">
                    <option value="En cours" ${status === 'En cours' ? 'selected' : ''}>En cours</option>
                    <option value="Terminé" ${status === 'Terminé' ? 'selected' : ''}>Terminé</option>
                    <option value="En attente" ${status === 'En attente' ? 'selected' : ''}>En attente</option>
                </select>
                <span class="error-message" id="editStatusError"></span>
            </label>
            <label>Progression (%): 
                <input type="number" id="editProgression" min="0" max="100" value="${progression}">
                <span class="error-message" id="editProgressionError"></span>
            </label>
            <label>Stock ID: 
                <input type="number" id="editStockId" min="1" value="${stockId}">
                <span class="error-message" id="editStockIdError"></span>
            </label>
        </div>
        <div class="form-footer">
            <button onclick="saveEditedRepairItem(${id})">Enregistrer</button>
            <button onclick="cancelRepairAdd()">Annuler</button>
        </div>
    `;
    repairFormContainer.appendChild(repairForm);
    currentRepairForm = repairForm;
}

function saveEditedRepairItem(id) {
    const bikeId = document.getElementById('editBikeId').value.trim();
    const bikeType = document.getElementById('editBikeType').value.trim();
    const problem = document.getElementById('editProblem').value.trim();
    const status = document.getElementById('editStatus').value;
    const progression = parseInt(document.getElementById('editProgression').value);
    const stockId = parseInt(document.getElementById('editStockId').value);

    document.getElementById('editBikeIdError').textContent = '';
    document.getElementById('editBikeTypeError').textContent = '';
    document.getElementById('editProblemError').textContent = '';
    document.getElementById('editStatusError').textContent = '';
    document.getElementById('editProgressionError').textContent = '';
    document.getElementById('editStockIdError').textContent = '';

    let hasError = false;

    if (!bikeId || bikeId.length > 10) {
        document.getElementById('editBikeIdError').textContent = 'Bike ID is required and must be less than 10 characters';
        hasError = true;
    }
    if (!bikeType || bikeType.length > 50) {
        document.getElementById('editBikeTypeError').textContent = 'Bike Type is required and must be less than 50 characters';
        hasError = true;
    }
    if (problem.length > 65535) {
        document.getElementById('editProblemError').textContent = 'Problem must be less than 65535 characters';
        hasError = true;
    }
    if (!status) {
        document.getElementById('editStatusError').textContent = 'Status is required';
        hasError = true;
    }
    if (isNaN(progression) || progression < 0 || progression > 100) {
        document.getElementById('editProgressionError').textContent = 'Progression must be between 0 and 100';
        hasError = true;
    }
    if (isNaN(stockId) || stockId < 1) {
        document.getElementById('editStockIdError').textContent = 'Stock ID must be a positive number';
        hasError = true;
    }

    if (hasError) return;

    const data = { id, bike_id: bikeId, bike_type: bikeType, problem, status, progression, stock_id: stockId };

    console.log(`Updating repair item to: ${CONTROLLER_PATH}?action=update`, data);
    fetch(`${CONTROLLER_PATH}?action=update`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
        .then(response => {
            console.log(`Update response status: ${response.status} (${response.statusText})`);
            if (!response.ok) {
                return response.text().then(text => {
                    console.error(`Fetch error: HTTP ${response.status} (${response.statusText}): ${text}`);
                    throw new Error(`HTTP ${response.status} (${response.statusText}): ${text}`);
                });
            }
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error(`JSON parse error: ${e.message}, Response: ${text}`);
                    throw new Error(`Invalid JSON: ${text}`);
                }
            });
        })
        .then(data => {
            console.log('Update repair response:', data);
            if (data.success) {
                currentRepairForm.remove();
                currentRepairForm = null;
                fetchRepairItems();
            } else {
                document.getElementById('repair-form-container').insertAdjacentHTML('beforeend', `<span class="error-message">${escapeHTML(data.error || 'Modification échouée')}</span>`);
            }
        })
        .catch(error => {
            console.error('Error updating repair item:', error);
            document.getElementById('repair-form-container').insertAdjacentHTML('beforeend', `<span class="error-message">Erreur lors de la modification: ${escapeHTML(error.message)}</span>`);
        });
}

function cancelRepairAdd() {
    if (currentRepairForm) {
        currentRepairForm.remove();
        currentRepairForm = null;
    }
}

function deleteRepairItem(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette réparation ?')) {
        console.log(`Deleting repair item from: ${CONTROLLER_PATH}?action=delete`, { id });
        fetch(`${CONTROLLER_PATH}?action=delete`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        })
            .then(response => {
                console.log(`Delete response status: ${response.status} (${response.statusText})`);
                if (!response.ok) {
                    return response.text().then(text => {
                        console.error(`Fetch error: HTTP ${response.status} (${response.statusText}): ${text}`);
                        throw new Error(`HTTP ${response.status} (${response.statusText}): ${text}`);
                    });
                }
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error(`JSON parse error: ${e.message}, Response: ${text}`);
                        throw new Error(`Invalid JSON: ${text}`);
                    }
                });
            })
            .then(data => {
                console.log('Delete repair response:', data);
                if (data.success) {
                    fetchRepairItems();
                } else {
                    const tbody = document.getElementById('repairs-table-body');
                    tbody.insertAdjacentHTML('beforeend', `<tr><td colspan="5"><span class="error-message">${escapeHTML(data.error || 'Suppression échouée')}</span></td></tr>`);
                }
            })
            .catch(error => {
                console.error('Error deleting repair item:', error);
                const tbody = document.getElementById('repairs-table-body');
                tbody.innerHTML = `<tr><td colspan="5"><span class="error-message">Erreur lors de la suppression: ${escapeHTML(error.message)}</td></tr>`;
            });
    }
}

// Open Repair Export Modal
function openRepairExportModal() {
    const exportBox = document.getElementById('repairExportBox');
    const exportOverlay = document.getElementById('repairExportOverlay');
    const exportList = document.getElementById('repairExportList');
    const exportError = document.getElementById('repairExportError');
    exportList.innerHTML = '';
    exportError.textContent = '';

    console.log(`Fetching repairs for export from: ${CONTROLLER_PATH}?action=get_all`);
    fetch(`${CONTROLLER_PATH}?action=get_all`)
        .then(response => {
            console.log(`Export response status: ${response.status} (${response.statusText})`);
            if (!response.ok) {
                return response.text().then(text => {
                    console.error(`Fetch error: HTTP ${response.status} (${response.statusText}): ${text}`);
                    throw new Error(`HTTP ${response.status} (${response.statusText}): ${text}`);
                });
            }
            return response.json();
        })
        .then(data => {
            console.log('Export repair data:', data);
            if (data.error) throw new Error(data.error);
            if (!Array.isArray(data)) throw new Error('Expected an array of repair items');

            if (data.length === 0) {
                console.log('No repair items for export');
                exportList.innerHTML = '<p class="no-items">Aucune réparation enregistrée</p>';
            } else {
                exportList.innerHTML = `
                    <label class="export-item select-all">
                        <input type="checkbox" id="selectAllRepairs" onchange="toggleSelectAll()">
                        Tout sélectionner
                    </label>
                    ${data.map(item => `
                        <label class="export-item">
                            <input type="checkbox" name="repairExport" value="${item.id}">
                            Réparation #${item.id} (Bike ID: ${escapeHTML(item.bike_id || 'N/A')}, Type: ${escapeHTML(item.bike_type || 'N/A')}, Statut: ${escapeHTML(item.status || 'N/A')})
                        </label>
                    `).join('')}
                `;
            }

            exportBox.classList.add('active');
            exportOverlay.classList.add('active');

            // Update selected count
            updateSelectedCount();
        })
        .catch(error => {
            console.error('Error fetching repairs for export:', error);
            exportList.innerHTML = '';
            exportError.textContent = `Erreur lors du chargement des réparations: ${escapeHTML(error.message)}`;
            exportBox.classList.add('active');
            exportOverlay.classList.add('active');
        });
}

// Toggle Select All checkboxes
function toggleSelectAll() {
    const selectAllCheckbox = document.getElementById('selectAllRepairs');
    const checkboxes = document.querySelectorAll('#repairExportList input[name="repairExport"]');
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAllCheckbox.checked;
    });
    updateSelectedCount();
}

// Update selected items count
function updateSelectedCount() {
    const checkboxes = document.querySelectorAll('#repairExportList input[name="repairExport"]:checked');
    const countDisplay = document.getElementById('selectedRepairCount');
    if (countDisplay) {
        countDisplay.textContent = `${checkboxes.length} réparation(s) sélectionnée(s)`;
    }
}

// Close Repair Export Modal
function closeRepairExportModal() {
    const exportBox = document.getElementById('repairExportBox');
    const exportOverlay = document.getElementById('repairExportOverlay');
    exportBox.classList.remove('active');
    exportOverlay.classList.remove('active');
    document.getElementById('repairExportList').innerHTML = '';
    document.getElementById('repairExportError').textContent = '';
}

// Export Repairs to PDF
function exportRepairsToPDF() {
    const checkboxes = document.querySelectorAll('#repairExportList input[name="repairExport"]:checked');
    const exportError = document.getElementById('repairExportError');

    if (checkboxes.length === 0) {
        exportError.textContent = 'Veuillez sélectionner au moins une réparation';
        return;
    }

    const selectedIds = Array.from(checkboxes).map(cb => parseInt(cb.value));

    console.log(`Fetching repairs for PDF export from: ${CONTROLLER_PATH}?action=get_all`);
    fetch(`${CONTROLLER_PATH}?action=get_all`)
        .then(response => {
            console.log(`PDF export response status: ${response.status} (${response.statusText})`);
            if (!response.ok) {
                return response.text().then(text => {
                    console.error(`Fetch error: HTTP ${response.status} (${response.statusText}): ${text}`);
                    throw new Error(`HTTP ${response.status} (${response.statusText}): ${text}`);
                });
            }
            return response.json();
        })
        .then(data => {
            console.log('PDF export data:', data);
            if (data.error) throw new Error(data.error);
            if (!Array.isArray(data)) throw new Error('Expected an array of repair items');

            const selectedItems = data.filter(item => selectedIds.includes(parseInt(item.id)));

            if (selectedItems.length === 0) {
                exportError.textContent = 'Aucune réparation sélectionnée correspond aux données disponibles';
                return;
            }

            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            let yOffset = 20;

            const logoWidth = 50;
            const logoHeight = 20;
            const pageWidth = doc.internal.pageSize.getWidth();
            const logoX = (pageWidth - logoWidth) / 2;
            try {
                doc.addImage('../images/logo.png', 'PNG', logoX, yOffset, logoWidth, logoHeight);
                yOffset += logoHeight + 10;
            } catch (error) {
                console.error('Error adding logo:', error);
                doc.text('Erreur: Logo non chargé', logoX, yOffset);
                yOffset += 10;
            }

            doc.setFontSize(14);
            doc.text('Rapport des Réparations - GreenTN', 20, yOffset);
            yOffset += 10;

            const repairTable = selectedItems.map(item => [
                item.id.toString(),
                item.bike_id || 'N/A',
                item.bike_type || 'N/A',
                item.problem || 'N/A',
                item.status || 'N/A',
                item.progression.toString() + '%',
                item.stock_id.toString()
            ]);

            doc.autoTable({
                startY: yOffset,
                head: [['ID', 'Bike ID', 'Type de Vélo', 'Problème', 'Statut', 'Progression', 'Stock ID']],
                body: repairTable,
                theme: 'striped',
                headStyles: { fillColor: [46, 125, 50] },
                margin: { top: 10 },
                styles: { fontSize: 10 },
                columnStyles: {
                    0: { cellWidth: 15 },
                    1: { cellWidth: 25 },
                    2: { cellWidth: 30 },
                    3: { cellWidth: 50 },
                    4: { cellWidth: 25 },
                    5: { cellWidth: 20 },
                    6: { cellWidth: 20 }
                }
            });

            const pageHeight = doc.internal.pageSize.getHeight();
            doc.setFontSize(12);
            doc.text(`Date: ${new Date().toLocaleDateString()}`, 20, pageHeight - 30);
            doc.text('Signature: ________________', pageWidth - 60, pageHeight - 20);

            const pdfDataUri = doc.output('datauristring');
            const pdfWindow = window.open('', '_blank', 'width=800,height=600');
            if (pdfWindow) {
                pdfWindow.document.write(`
                    <html>
                        <head>
                            <title>GreenTN Repair Export</title>
                            <style>
                                body { margin: 0; }
                                embed { width: 100%; height: 100vh; }
                            </style>
                        </head>
                        <body>
                            <embed src="${pdfDataUri}" type="application/pdf">
                        </body>
                    </html>
                `);
                pdfWindow.document.close();
                closeRepairExportModal();
            } else {
                exportError.textContent = 'Erreur: Impossible d\'ouvrir la fenêtre de prévisualisation. Veuillez vérifier les paramètres du bloqueur de popups.';
            }
        })
        .catch(error => {
            console.error('Error exporting repairs to PDF:', error);
            exportError.textContent = `Erreur lors de l'exportation: ${escapeHTML(error.message)}`;
        });
}

document.addEventListener('DOMContentLoaded', () => {
    console.log('Initializing repairs table');
    fetchRepairItems();

    // Add event listener for dynamic selected count updates
    const exportList = document.getElementById('repairExportList');
    if (exportList) {
        exportList.addEventListener('change', updateSelectedCount);
    }
});