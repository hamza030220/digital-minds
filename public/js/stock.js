let stockItems = [];
let currentStockForm = null;

// Base URL for fetch requests
const CONTROLLER_PATH = '/green/controllers/StockController.php'; // Fixed typo

// Escape HTML to prevent XSS
function escapeHTML(str) {
    return str.replace(/[&<>"']/g, match => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;'
    })[match]);
}

// Fetch stock items for dropdowns or other uses
function fetchStock(callback) {
    const url = `${CONTROLLER_PATH}?action=get_all`;
    console.log(`Fetching stock from: ${url}`);
    fetch(url)
        .then(response => {
            console.log(`Fetch response status: ${response.status} (${response.statusText})`);
            if (!response.ok) {
                return response.text().then(text => {
                    console.error(`Fetch error: HTTP ${response.status} (${response.statusText}): ${text}`);
                    throw new Error(`HTTP ${response.status} (${response.statusText}): ${text}`);
                });
            }
            return response.json();
        })
        .then(data => {
            console.log('Stock data received:', data);
            stockItems = data;
            if (callback) callback();
        })
        .catch(error => {
            console.error('Error fetching stock:', error);
            const errorSpan = document.createElement('span');
            errorSpan.className = 'error-message';
            errorSpan.textContent = `Erreur lors du chargement des stocks: ${error.message}`;
            document.getElementById('stock-form-container').appendChild(errorSpan);
        });
}

// Fetch and display stock items
function fetchStockItems() {
    const url = `${CONTROLLER_PATH}?action=get_all`;
    console.log(`Fetching stock items from: ${url}`);
    fetch(url)
        .then(response => {
            console.log(`Fetch response status: ${response.status} (${response.statusText})`);
            if (!response.ok) {
                return response.text().then(text => {
                    console.error(`Fetch error: HTTP ${response.status} (${response.statusText}): ${text}`);
                    throw new Error(`HTTP ${response.status} (${response.statusText}): ${text}`);
                });
            }
            return response.json();
        })
        .then(data => {
            console.log('Stock items received:', data);
            const tbody = document.getElementById('stock-table-body');
            tbody.innerHTML = '';
            if (!Array.isArray(data)) {
                console.error('Expected an array, got:', data);
                throw new Error('Expected an array of stock items');
            }
            if (data.length === 0) {
                console.log('No stock items found in database');
                tbody.innerHTML = '<tr><td colspan="6">Aucun article en stock</td></tr>';
                return;
            }
            data.forEach(item => {
                if (!item.id || !item.item_name || !item.category || item.quantity === undefined || item.price === undefined) {
                    console.warn('Invalid stock item:', item);
                    return;
                }
                const row = document.createElement('tr');
                const quantityClass = item.quantity <= 5 ? 'low-stock' : '';
                row.innerHTML = `
                    <td>${escapeHTML(item.id.toString())}</td>
                    <td>${escapeHTML(item.item_name)}</td>
                    <td>${escapeHTML(item.category)}</td>
                    <td class="${quantityClass}">${escapeHTML(item.quantity.toString())}</td>
                    <td>${escapeHTML(item.price.toFixed(2))} TND</td>
                    <td>
                        <a href="#" class="btn edit" onclick="editStockItem(${item.id}, '${escapeHTML(item.item_name)}', '${escapeHTML(item.category)}', ${item.quantity}, ${item.price})">Modifier</a>
                        <a href="#" class="btn delete" onclick="deleteStockItem(${item.id})">Supprimer</a>
                    </td>
                `;
                tbody.appendChild(row);
            });
            console.log('Stock table updated with', data.length, 'items');
        })
        .catch(error => {
            console.error('Error fetching stock items:', error);
            const tbody = document.getElementById('stock-table-body');
            tbody.innerHTML = `<tr><td colspan="6">Erreur lors du chargement des données: ${escapeHTML(error.message)}</td></tr>`;
        });
}

// Add stock item form
function addStockItem() {
    if (currentStockForm) {
        currentStockForm.remove();
        currentStockForm = null;
    }

    const stockFormContainer = document.getElementById('stock-form-container');
    const stockForm = document.createElement('div');
    stockForm.className = 'add-form';
    stockForm.innerHTML = `
        <div class="form-header">
            <h3>Ajouter un Article</h3>
        </div>
        <div class="form-body">
            <label>Nom de l'Article: 
                <input type="text" id="addItemName" placeholder="Ex: Pneu">
                <span class="error-message" id="addItemNameError"></span>
            </label>
            <label>Catégorie: 
                <input type="text" id="addCategory" placeholder="Ex: Pièces">
                <span class="error-message" id="addCategoryError"></span>
            </label>
            <label>Quantité: 
                <input type="number" id="addQuantity" min="0" value="0">
                <span class="error-message" id="addQuantityError"></span>
            </label>
            <label>Prix (TND): 
                <input type="number" id="addPrice" min="0" step="0.01" value="0.00">
                <span class="error-message" id="addPriceError"></span>
            </label>
        </div>
        <div class="form-footer">
            <button onclick="saveNewStockItem()">Enregistrer</button>
            <button onclick="cancelStockAdd()">Annuler</button>
        </div>
    `;
    stockFormContainer.appendChild(stockForm);
    currentStockForm = stockForm;
}

// Save new stock item
function saveNewStockItem() {
    const itemName = document.getElementById('addItemName').value.trim();
    const category = document.getElementById('addCategory').value.trim();
    const quantity = parseInt(document.getElementById('addQuantity').value);
    const price = parseFloat(document.getElementById('addPrice').value);

    document.getElementById('addItemNameError').textContent = '';
    document.getElementById('addCategoryError').textContent = '';
    document.getElementById('addQuantityError').textContent = '';
    document.getElementById('addPriceError').textContent = '';

    let hasError = false;

    if (!itemName || itemName.length > 100) {
        document.getElementById('addItemNameError').textContent = 'Le nom est requis et doit être inférieur à 100 caractères';
        hasError = true;
    }
    if (!category || category.length > 50) {
        document.getElementById('addCategoryError').textContent = 'La catégorie est requise et doit être inférieure à 50 caractères';
        hasError = true;
    }
    if (isNaN(quantity) || quantity < 0) {
        document.getElementById('addQuantityError').textContent = 'La quantité doit être un nombre positif';
        hasError = true;
    }
    if (isNaN(price) || price < 0) {
        document.getElementById('addPriceError').textContent = 'Le prix doit être un nombre positif';
        hasError = true;
    }

    if (hasError) return;

    const data = { item_name: itemName, category, quantity, price };

    console.log(`Adding stock item to: ${CONTROLLER_PATH}?action=add`, data);
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
            return response.json();
        })
        .then(data => {
            console.log('Add stock response:', data);
            if (data.success) {
                currentStockForm.remove();
                currentStockForm = null;
                fetchStockItems();
            } else {
                document.getElementById('stock-form-container').insertAdjacentHTML('beforeend', `<span class="error-message">${escapeHTML(data.error || 'Ajout échoué')}</span>`);
            }
        })
        .catch(error => {
            console.error('Error adding stock item:', error);
            document.getElementById('stock-form-container').insertAdjacentHTML('beforeend', `<span class="error-message">Erreur lors de l'ajout: ${escapeHTML(error.message)}</span>`);
        });
}

// Edit stock item
function editStockItem(id, itemName, category, quantity, price) {
    if (currentStockForm) {
        currentStockForm.remove();
        currentStockForm = null;
    }

    const stockFormContainer = document.getElementById('stock-form-container');
    const stockForm = document.createElement('div');
    stockForm.className = 'edit-form';
    stockForm.innerHTML = `
        <div class="form-header">
            <h3>Modifier l'Article #${id}</h3>
        </div>
        <div class="form-body">
            <label>Nom de l'Article: 
                <input type="text" id="editItemName" value="${escapeHTML(itemName)}">
                <span class="error-message" id="editItemNameError"></span>
            </label>
            <label>Catégorie: 
                <input type="text" id="editCategory" value="${escapeHTML(category)}">
                <span class="error-message" id="editCategoryError"></span>
            </label>
            <label>Quantité: 
                <input type="number" id="editQuantity" min="0" value="${quantity}">
                <span class="error-message" id="editQuantityError"></span>
            </label>
            <label>Prix (TND): 
                <input type="number" id="editPrice" min="0" step="0.01" value="${price.toFixed(2)}">
                <span class="error-message" id="editPriceError"></span>
            </label>
        </div>
        <div class="form-footer">
            <button onclick="saveEditedStockItem(${id})">Enregistrer</button>
            <button onclick="cancelStockAdd()">Annuler</button>
        </div>
    `;
    stockFormContainer.appendChild(stockForm);
    currentStockForm = stockForm;
}

// Save edited stock item
function saveEditedStockItem(id) {
    const itemName = document.getElementById('editItemName').value.trim();
    const category = document.getElementById('editCategory').value.trim();
    const quantity = parseInt(document.getElementById('editQuantity').value);
    const price = parseFloat(document.getElementById('editPrice').value);

    document.getElementById('editItemNameError').textContent = '';
    document.getElementById('editCategoryError').textContent = '';
    document.getElementById('editQuantityError').textContent = '';
    document.getElementById('editPriceError').textContent = '';

    let hasError = false;

    if (!itemName || itemName.length > 100) {
        document.getElementById('editItemNameError').textContent = 'Le nom est requis et doit être inférieur à 100 caractères';
        hasError = true;
    }
    if (!category || category.length > 50) {
        document.getElementById('editCategoryError').textContent = 'La catégorie est requise et doit être inférieure à 50 caractères';
        hasError = true;
    }
    if (isNaN(quantity) || quantity < 0) {
        document.getElementById('editQuantityError').textContent = 'La quantité doit être un nombre positif';
        hasError = true;
    }
    if (isNaN(price) || price < 0) {
        document.getElementById('editPriceError').textContent = 'Le prix doit être un nombre positif';
        hasError = true;
    }

    if (hasError) return;

    const data = { id, item_name: itemName, category, quantity, price };

    console.log(`Updating stock item to: ${CONTROLLER_PATH}?action=update`, data);
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
            return response.json();
        })
        .then(data => {
            console.log('Update stock response:', data);
            if (data.success) {
                currentStockForm.remove();
                currentStockForm = null;
                fetchStockItems();
            } else {
                document.getElementById('stock-form-container').insertAdjacentHTML('beforeend', `<span class="error-message">${escapeHTML(data.error || 'Modification échouée')}</span>`);
            }
        })
        .catch(error => {
            console.error('Error updating stock item:', error);
            document.getElementById('stock-form-container').insertAdjacentHTML('beforeend', `<span class="error-message">Erreur lors de la modification: ${escapeHTML(error.message)}</span>`);
        });
}

// Cancel adding or editing stock item
function cancelStockAdd() {
    if (currentStockForm) {
        currentStockForm.remove();
        currentStockForm = null;
    }
}

// Delete stock item
function deleteStockItem(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cet article ?')) {
        console.log(`Deleting stock item from: ${CONTROLLER_PATH}?action=delete`, { id });
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
                return response.json();
            })
            .then(data => {
                console.log('Delete stock response:', data);
                if (data.success) {
                    fetchStockItems();
                } else {
                    const tbody = document.getElementById('stock-table-body');
                    tbody.insertAdjacentHTML('beforeend', `<tr><td colspan="6"><span class="error-message">${escapeHTML(data.error || 'Suppression échouée')}</span></td></tr>`);
                }
            })
            .catch(error => {
                console.error('Error deleting stock item:', error);
                const tbody = document.getElementById('stock-table-body');
                tbody.innerHTML = `<tr><td colspan="6"><span class="error-message">Erreur lors de la suppression: ${escapeHTML(error.message)}</td></tr>`;
            });
    }
}

// Open Stock Export Modal
function openStockExportModal() {
    const exportBox = document.getElementById('stockExportBox');
    const exportOverlay = document.getElementById('stockExportOverlay');
    const exportList = document.getElementById('stockExportList');
    const exportError = document.getElementById('stockExportError');
    exportList.innerHTML = '';
    exportError.textContent = '';

    console.log(`Fetching stock for export from: ${CONTROLLER_PATH}?action=get_all`);
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
            console.log('Export stock data:', data);
            if (data.error) throw new Error(data.error);
            if (!Array.isArray(data)) throw new Error('Expected an array of stock items');

            if (data.length === 0) {
                console.log('No stock items for export');
                exportList.innerHTML = '<p class="no-items">Aucun article en stock</p>';
            } else {
                const totalQuantity = data.reduce((sum, item) => sum + parseInt(item.quantity), 0);
                exportList.innerHTML = `
                    <label class="export-item select-all">
                        <input type="checkbox" id="selectAllStock" onchange="toggleSelectAll()">
                        Tout sélectionner
                    </label>
                    ${data.map(item => {
                        const percentage = totalQuantity > 0 ? ((parseInt(item.quantity) / totalQuantity) * 100).toFixed(2) : '0.00';
                        return `
                            <label class="export-item">
                                <input type="checkbox" name="stockExport" value="${item.id}">
                                ${escapeHTML(item.item_name)} (${escapeHTML(item.category)}, Stock: ${item.quantity}, ${percentage}%)
                            </label>
                        `;
                    }).join('')}
                `;
            }

            exportBox.classList.add('active');
            exportOverlay.classList.add('active');

            // Update selected count
            updateSelectedCount();
        })
        .catch(error => {
            console.error('Error fetching stock for export:', error);
            exportList.innerHTML = '';
            exportError.textContent = `Erreur lors du chargement des articles: ${escapeHTML(error.message)}`;
            exportBox.classList.add('active');
            exportOverlay.classList.add('active');
        });
}

// Toggle Select All checkboxes
function toggleSelectAll() {
    const selectAllCheckbox = document.getElementById('selectAllStock');
    const checkboxes = document.querySelectorAll('#stockExportList input[name="stockExport"]');
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAllCheckbox.checked;
    });
    updateSelectedCount();
}

// Update selected items count
function updateSelectedCount() {
    const checkboxes = document.querySelectorAll('#stockExportList input[name="stockExport"]:checked');
    const countDisplay = document.getElementById('selectedCount');
    if (countDisplay) {
        countDisplay.textContent = `${checkboxes.length} article(s) sélectionné(s)`;
    }
}

// Close Stock Export Modal
function closeStockExportModal() {
    const exportBox = document.getElementById('stockExportBox');
    const exportOverlay = document.getElementById('stockExportOverlay');
    exportBox.classList.remove('active');
    exportOverlay.classList.remove('active');
    document.getElementById('stockExportList').innerHTML = '';
    document.getElementById('stockExportError').textContent = '';
}

// Export Stock to PDF
function exportStockToPDF() {
    const checkboxes = document.querySelectorAll('#stockExportList input[name="stockExport"]:checked');
    const exportError = document.getElementById('stockExportError');

    if (checkboxes.length === 0) {
        exportError.textContent = 'Veuillez sélectionner au moins un article';
        return;
    }

    const selectedIds = Array.from(checkboxes).map(cb => parseInt(cb.value));

    console.log(`Fetching stock for PDF export from: ${CONTROLLER_PATH}?action=get_all`);
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
            if (!Array.isArray(data)) throw new Error('Expected an array of stock items');

            const selectedItems = data.filter(item => selectedIds.includes(parseInt(item.id)));

            if (selectedItems.length === 0) {
                exportError.textContent = 'Aucun article sélectionné correspond aux données disponibles';
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
            doc.text('Rapport de Stock - GreenTN', 20, yOffset);
            yOffset += 10;

            const stockTable = selectedItems.map(item => [
                item.id.toString(),
                item.item_name || 'N/A',
                item.category || 'N/A',
                item.quantity.toString(),
                item.price.toFixed(2) + ' TND'
            ]);

            doc.autoTable({
                startY: yOffset,
                head: [['ID', 'Nom de l\'Article', 'Catégorie', 'Quantité', 'Prix']],
                body: stockTable,
                theme: 'striped',
                headStyles: { fillColor: [46, 125, 50] },
                margin: { top: 10 },
                styles: { fontSize: 10 },
                columnStyles: {
                    0: { cellWidth: 20 },
                    1: { cellWidth: 60 },
                    2: { cellWidth: 40 },
                    3: { cellWidth: 30 },
                    4: { cellWidth: 30 }
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
                            <title>GreenTN Stock Export</title>
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
                closeStockExportModal();
            } else {
                exportError.textContent = 'Erreur: Impossible d\'ouvrir la fenêtre de prévisualisation. Veuillez vérifier les paramètres du bloqueur de popups.';
            }
        })
        .catch(error => {
            console.error('Error exporting stock to PDF:', error);
            exportError.textContent = `Erreur lors de l'exportation: ${escapeHTML(error.message)}`;
        });
}

// Initialize stock table on page load
document.addEventListener('DOMContentLoaded', () => {
    console.log('Initializing stock table');
    fetchStockItems();

    // Add event listener for dynamic selected count updates
    const exportList = document.getElementById('stockExportList');
    if (exportList) {
        exportList.addEventListener('change', updateSelectedCount);
    }
});