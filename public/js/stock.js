console.log('escapeHTML function initialized');

// Global variable for current stock form
let currentStockForm = null;

// Base paths for API and resources
const API_BASE_PATH = '../../CONTROLLER/StockController.php';
const LOGO_PATH = '../../images/logo.png';

// Fetch and display stock items
function fetchStockItems() {
    const apiUrl = `${API_BASE_PATH}?action=get_all`;
    console.log(`Fetching stock items from: ${apiUrl}`);
    fetch(apiUrl)
        .then(response => {
            console.log(`Fetch response status: ${response.status} (${response.statusText})`);
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            return response.json();
        })
        .then(data => {
            console.log('Stock items received:', data);
            if (data.error) throw new Error(data.error);
            if (!Array.isArray(data)) throw new Error('Expected an array of stock items');
            const tbody = document.getElementById('stock-table-body');
            tbody.innerHTML = '';
            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6">Aucun article en stock</td></tr>';
                return;
            }
            data.forEach(item => {
                const row = document.createElement('tr');
                const quantityClass = item.quantity <= 5 ? 'low-stock' : '';
                row.innerHTML = `
                    <td>${escapeHTML(item.id.toString())}</td>
                    <td>${escapeHTML(item.item_name)}</td>
                    <td>${escapeHTML(item.category)}</td>
                    <td class="${quantityClass}">${escapeHTML(item.quantity.toString())}</td>
                    <td>${item.price ? parseFloat(item.price).toFixed(2) : 'N/A'}</td>
                    <td>
                        <a href="#" class="btn edit" onclick="editStockItem(${item.id}, '${escapeHTML(item.item_name)}', '${escapeHTML(item.category)}', ${item.quantity}, ${item.price || 0}, this)">Modifier</a>
                        <a href="#" class="btn delete" onclick="deleteStockItem(${item.id})">Supprimer</a>
                    </td>
                `;
                tbody.appendChild(row);
            });
        })
        .catch(error => {
            console.error('Error fetching stock items:', error);
            const tbody = document.getElementById('stock-table-body');
            tbody.innerHTML = `<tr><td colspan="6">Erreur lors du chargement des données: ${error.message}</td></tr>`;
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
            <label>Prix: 
                <input type="number" id="addPrice" min="0" step="0.01" value="0">
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

    // Clear previous error messages
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
        document.getElementById('addCategoryError').textContent = 'La catégorie est requise et doit être inférieur à 50 caractères';
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
    const apiUrl = `${API_BASE_PATH}?action=add`;
    console.log(`Adding stock item to: ${apiUrl}`, data);

    fetch(apiUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
        .then(response => {
            console.log(`Add response status: ${response.status} (${response.statusText})`);
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            return response.json();
        })
        .then(data => {
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

// Cancel adding stock item
function cancelStockAdd() {
    if (currentStockForm) {
        currentStockForm.remove();
        currentStockForm = null;
    }
}

// Edit stock item
function editStockItem(id, itemName, category, quantity, price, button) {
    if (currentStockForm) {
        currentStockForm.remove();
        currentStockForm = null;
    }

    const stockFormContainer = document.getElementById('stock-form-container');
    const stockForm = document.createElement('div');
    stockForm.className = 'edit-form';
    stockForm.innerHTML = `
        <div class="form-header">
            <h3>Modifier un Article</h3>
        </div>
        <div class="form-body">
            <input type="hidden" id="editId" value="${id}">
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
            <label>Prix: 
                <input type="number" id="editPrice" min="0" step="0.01" value="${price}">
                <span class="error-message" id="editPriceError"></span>
            </label>
        </div>
        <div class="form-footer">
            <button onclick="saveEditedStockItem()">Enregistrer</button>
            <button onclick="cancelStockEdit()">Annuler</button>
        </div>
    `;
    stockFormContainer.appendChild(stockForm);
    currentStockForm = stockForm;
}

// Save edited stock item
function saveEditedStockItem() {
    const id = document.getElementById('editId').value;
    const itemName = document.getElementById('editItemName').value.trim();
    const category = document.getElementById('editCategory').value.trim();
    const quantity = parseInt(document.getElementById('editQuantity').value);
    const price = parseFloat(document.getElementById('editPrice').value);

    // Clear previous error messages
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
        document.getElementById('editCategoryError').textContent = 'La catégorie est requise et doit être inférieur à 50 caractères';
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
    const apiUrl = `${API_BASE_PATH}?action=update`;
    console.log(`Updating stock item at: ${apiUrl}`, data);

    fetch(apiUrl, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
        .then(response => {
            console.log(`Update response status: ${response.status} (${response.statusText})`);
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            return response.json();
        })
        .then(data => {
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

// Cancel editing stock item
function cancelStockEdit() {
    if (currentStockForm) {
        currentStockForm.remove();
        currentStockForm = null;
    }
}

// Delete stock item
function deleteStockItem(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cet article ?')) {
        const apiUrl = `${API_BASE_PATH}?action=delete`;
        console.log(`Deleting stock item at: ${apiUrl}`, { id });
        fetch(apiUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        })
            .then(response => {
                console.log(`Delete response status: ${response.status} (${response.statusText})`);
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                return response.json();
            })
            .then(data => {
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
                tbody.innerHTML = `<tr><td colspan="6"><span class="error-message">Erreur lors de la suppression: ${error.message}</td></tr>`;
            });
    }
}

// Open Stock Export Modal
function openStockExportModal() {
    const modal = document.getElementById('stockExportModal');
    const exportList = document.getElementById('stockExportList');
    const exportError = document.getElementById('stockExportError');
    exportList.innerHTML = '';
    exportError.textContent = '';

    const apiUrl = `${API_BASE_PATH}?action=get_all`;
    console.log(`Fetching stock for export from: ${apiUrl}`);
    fetch(apiUrl)
        .then(response => {
            console.log(`Export fetch response status: ${response.status} (${response.statusText})`);
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            return response.json();
        })
        .then(data => {
            if (data.error) throw new Error(data.error);
            if (!Array.isArray(data)) throw new Error('Expected an array of stock items');

            if (data.length === 0) {
                exportList.innerHTML = '<p class="no-data">Aucun article en stock</p>';
                modal.style.display = 'flex';
                return;
            }

            // Calculate total quantity
            const totalQuantity = data.reduce((sum, item) => sum + parseInt(item.quantity), 0);

            // Generate checkbox list with percentage
            data.forEach(item => {
                const percentage = totalQuantity > 0 ? ((parseInt(item.quantity) / totalQuantity) * 100).toFixed(2) : '0.00';
                const label = document.createElement('label');
                label.innerHTML = `
                    <input type="checkbox" name="stockExport" value="${item.id}">
                    ${escapeHTML(item.item_name)} (${escapeHTML(item.category)}, Stock: ${item.quantity}, ${percentage}%)
                `;
                exportList.appendChild(label);
            });

            modal.style.display = 'flex';
        })
        .catch(error => {
            console.error('Error fetching stock for export:', error);
            exportError.textContent = `Erreur lors du chargement des articles: ${error.message}`;
            modal.style.display = 'flex';
        });
}

// Close Stock Export Modal
function closeStockExportModal() {
    document.getElementById('stockExportModal').style.display = 'none';
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
    const apiUrl = `${API_BASE_PATH}?action=get_all`;
    console.log(`Fetching stock for PDF export from: ${apiUrl}`);

    fetch(apiUrl)
        .then(response => {
            console.log(`PDF fetch response status: ${response.status} (${response.statusText})`);
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            return response.json();
        })
        .then(data => {
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

            // Add logo
            console.log(`Adding logo from: ${LOGO_PATH}`);
            const logoWidth = 50;
            const logoHeight = 20;
            const pageWidth = doc.internal.pageSize.getWidth();
            const logoX = (pageWidth - logoWidth) / 2;
            try {
                doc.addImage(LOGO_PATH, 'PNG', logoX, yOffset, logoWidth, logoHeight);
                yOffset += logoHeight + 10;
            } catch (error) {
                console.error('Error adding logo:', error);
                doc.text('Erreur: Logo non chargé', logoX, yOffset);
                yOffset += 10;
            }

            // Add stock table
            doc.setFontSize(14);
            doc.text('Stock Sélectionné', 20, yOffset);
            yOffset += 10;

            const stockTable = selectedItems.map(item => [
                item.id.toString(),
                item.item_name || 'N/A',
                item.category || 'N/A',
                item.quantity.toString(),
                item.price ? parseFloat(item.price).toFixed(2) : 'N/A'
            ]);

            doc.autoTable({
                startY: yOffset,
                head: [['ID', 'Nom de l\'Article', 'Catégorie', 'Quantité', 'Prix']],
                body: stockTable,
                theme: 'striped',
                headStyles: { fillColor: [46, 125, 50] },
                margin: { top: 10 }
            });

            // Add signature
            const pageHeight = doc.internal.pageSize.getHeight();
            doc.setFontSize(12);
            doc.text('Signature: ________________', pageWidth - 60, pageHeight - 20);

            // Save PDF
            doc.save('GreenTN_Stock_Export.pdf');
            closeStockExportModal();
        })
        .catch(error => {
            console.error('Error exporting stock to PDF:', error);
            exportError.textContent = `Erreur lors de l'exportation: ${error.message}`;
        });
}

// Sidebar toggle
document.addEventListener('DOMContentLoaded', function() {
    console.log('Initializing stock table');
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

    // Close modal when clicking outside
    document.getElementById('stockExportModal').addEventListener('click', (e) => {
        if (e.target === document.getElementById('stockExportModal')) {
            closeStockExportModal();
        }
    });

    // Initial fetch
    fetchStockItems();
});