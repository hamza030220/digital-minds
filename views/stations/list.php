<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../../includes/config.php';
requireLogin();
$error = '';
$success = '';

try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT id, name, city, location, status FROM stations");
    $stations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $error = "Erreur lors de la récupération des stations.";
}

requireLogin();
$error = '';
$success = '';

try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT id, name, city, location, status FROM stations");
    $stations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $error = "Erreur lors de la récupération des stations.";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Stations - Green Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../public/assets/css/styles.css" rel="stylesheet">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
</head>
<body class="d-flex">
    <?php
    $basePath = '../../';
    $currentPage = 'stations';
    include '../../includes/sidbar.php';
    ?>

    <main class="main-content flex-grow-1 p-4">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Liste des Stations</h1>
            </div>

            <!-- Action Buttons -->
            <div class="d-flex flex-column align-items-end mb-3">
                <a href="add.php" class="btn btn-success mb-2">
                    <i class="bi bi-plus-circle me-2"></i>Nouvelle Station
                </a>
                <button id="showStationsTableModalBtn" class="btn btn-success">
                    <i class="bi bi-table me-2"></i>Afficher le tableau complet
                </button>
            </div>

            <!-- Search Bar -->
            <div class="mb-3">
                <input type="text" id="mainStationSearchInput" class="form-control" placeholder="Rechercher par nom ou ville...">
                <div id="mainStationSearchMessage" class="mt-2" style="display: none;"></div>
            </div>

            <!-- Alerts -->
            <?php if (!empty($success)): ?>
                <div class="alert alert-success" role="alert"><?= htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" role="alert"><?= htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <!-- Stations Table -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="stationsTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nom</th>
                                    <th>Ville</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="stationsTableBody">
                                <?php if (!empty($stations)): ?>
                                    <?php foreach ($stations as $station): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($station['id']); ?></td>
                                            <td><?= htmlspecialchars($station['name']); ?></td>
                                            <td><?= htmlspecialchars($station['city']); ?></td>
                                            <td><?= htmlspecialchars($station['status']); ?></td>
                                            <td>
                                                <a href="edit.php?id=<?= $station['id']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="delete.php?id=<?= $station['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette station ?');">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center">Aucune station trouvée</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div id="paginationControls" class="d-flex justify-content-center mt-3"></div>
                </div>
            </div>

            <!-- Map Card -->
            <div class="card shadow-sm">
                <div class="card-header">
                    <i class="bi bi-map me-2"></i>Carte des Stations
                </div>
                <div class="card-body" style="height: 400px;">
                    <div id="stationsMap" style="height: 100%; width: 100%;"></div>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal for Full Stations Table -->
    <div class="modal fade" id="stationsTableModal" tabindex="-1" aria-labelledby="stationsTableModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="w-100 d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" id="sortStationsBtn" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-funnel me-2"></i>Trier
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="sortStationsBtn">
                                    <li><a class="dropdown-item" href="#" id="sortByName">Nom (A-Z)</a></li>
                                    <li><a class="dropdown-item" href="#" id="sortByLocation">Ville (A-Z)</a></li>
                                    <li><a class="dropdown-item" href="#" id="sortByStatus">Statut (A-Z)</a></li>
                                </ul>
                            </div>
                            <button id="exportPdfBtn" class="btn btn-outline-danger btn-sm">
                                <i class="bi bi-file-earmark-pdf me-2"></i>Exporter PDF
                            </button>
                        </div>
                        <h5 class="modal-title mb-0" id="stationsTableModalLabel">
                            <i class="bi bi-table me-2"></i>Tableau complet des stations
                        </h5>
                        <button type="button" class="btn-close ms-2" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                </div>
                <div class="modal-body" style="max-height: 70vh; overflow: auto;">
                    <div class="mb-3">
                        <input type="text" id="stationSearchInput" class="form-control" placeholder="Rechercher par nom ou ville...">
                        <div id="stationSearchMessage" class="mt-2" style="display: none;"></div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered mb-0" id="stationsFullTable">
                            <thead>
                                <tr>
                                    <th style="width: 40px;">
                                        <input type="checkbox" id="checkAllRows" />
                                    </th>
                                    <th>ID</th>
                                    <th>Nom</th>
                                    <th>Ville</th>
                                    <th>Statut</th>
                                    <th style="display: none;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($stations)): ?>
                                    <?php foreach ($stations as $station): ?>
                                        <tr>
                                            <td>
                                                <input type="checkbox" class="row-checkbox" />
                                            </td>
                                            <td><?= htmlspecialchars($station['id']); ?></td>
                                            <td><?= htmlspecialchars($station['name']); ?></td>
                                            <td data-city="<?= htmlspecialchars($station['city']); ?>">
                                                <?= htmlspecialchars($station['city']); ?>
                                            </td>
                                            <td><?= htmlspecialchars($station['status']); ?></td>
                                            <td style="display: none;">
                                                <a href="edit.php?id=<?= $station['id']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="delete.php?id=<?= $station['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette station ?');">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center">Aucune station trouvée</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div id="modalPaginationControls" class="d-flex justify-content-center mt-3"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
    <script src="../../public/assets/js/sidebar.js"></script>

    <!-- Stations Data -->
    <script>
        const stations = <?= json_encode($stations ?? []); ?>;
    </script>

    <!-- Map Initialization -->
    <script>
        const map = L.map('stationsMap').setView([36.8, 10.18], 8); // Centered on Tunisia
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        const blueIcon = new L.Icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-blue.png',
            shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });
        const greenIcon = new L.Icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-green.png',
            shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });
        const redIcon = new L.Icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-red.png',
            shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });

        let bounds = [];
        stations.forEach(station => {
            if (station.location && station.location.includes(',')) {
                const [lat, lng] = station.location.split(',').map(coord => parseFloat(coord.trim()));
                if (!isNaN(lat) && !isNaN(lng)) {
                    let icon = blueIcon;
                    if (station.status?.toLowerCase() === 'active') icon = greenIcon;
                    else if (station.status?.toLowerCase() === 'inactive') icon = redIcon;
                    const marker = L.marker([lat, lng], { icon }).addTo(map)
                        .bindPopup(`<b>${station.name}</b><br>${station.city}<br>Statut: ${station.status}`);
                    bounds.push([lat, lng]);
                }
            }
        });
        if (bounds.length > 0) {
            map.fitBounds(bounds, { padding: [30, 30] });
        }
    </script>

    <!-- JavaScript Logic -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Modal Handling
            const stationsTableModal = new bootstrap.Modal(document.getElementById('stationsTableModal'), { backdrop: 'static' });
            document.getElementById('showStationsTableModalBtn').addEventListener('click', () => {
                stationsTableModal.show();
            });

            // Modal Cleanup
            document.getElementById('stationsTableModal').addEventListener('hidden.bs.modal', () => {
                document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
                document.body.classList.remove('modal-open');
                document.body.style.paddingRight = '';
            });

            // Checkbox Logic
            const checkAllRows = document.getElementById('checkAllRows');
            const getRowCheckboxes = () => document.querySelectorAll('#stationsFullTable .row-checkbox');
            checkAllRows.addEventListener('change', () => {
                getRowCheckboxes().forEach(cb => cb.checked = checkAllRows.checked);
            });
            document.querySelector('#stationsFullTable tbody').addEventListener('change', e => {
                if (e.target.classList.contains('row-checkbox')) {
                    checkAllRows.checked = !e.target.checked ? false : [...getRowCheckboxes()].every(cb => cb.checked);
                }
            });

            // PDF Export
            document.getElementById('exportPdfBtn').addEventListener('click', () => {
                const table = document.getElementById('stationsFullTable');
                const headers = Array.from(table.querySelectorAll('thead th')).slice(1, -1).map(th => th.textContent.trim());
                const selectedRows = [];
                table.querySelectorAll('tbody tr').forEach(tr => {
                    const cb = tr.querySelector('.row-checkbox');
                    if (cb?.checked) {
                        const tds = Array.from(tr.children).slice(1, -1);
                        const rowData = tds.map((td, idx) => idx === 2 ? td.getAttribute('data-city') || td.textContent.trim() : td.textContent.trim());
                        selectedRows.push(rowData);
                    }
                });

                if (selectedRows.length === 0) {
                    alert('Veuillez sélectionner au moins une ligne à exporter.');
                    return;
                }

                const { jsPDF } = window.jspdf;
                const doc = new jsPDF();
                const img = new Image();
                img.src = '../../public/image/logobackend.png';

                img.onload = () => {
                    const pageWidth = doc.internal.pageSize.getWidth();
                    const imgWidth = 40;
                    const aspectRatio = img.naturalWidth / img.naturalHeight;
                    const imgHeight = imgWidth / aspectRatio;
                    const x = (pageWidth - imgWidth) / 2;
                    doc.addImage(img, 'PNG', x, 10, imgWidth, imgHeight);

                    doc.setFont('helvetica', 'bold');
                    doc.setFontSize(18);
                    doc.text('Liste des Stations', pageWidth / 2, imgHeight + 25, { align: 'center' });

                    doc.setFont('helvetica', 'normal');
                    doc.setFontSize(12);
                    doc.text(`Exporté le : ${new Date().toLocaleDateString()}`, pageWidth / 2, imgHeight + 33, { align: 'center' });

                    doc.autoTable({
                        head: [headers],
                        body: selectedRows,
                        startY: imgHeight + 40,
                        theme: 'grid',
                        styles: { fontSize: 11, cellPadding: 3 },
                        headStyles: { fillColor: [96, 186, 151], textColor: 255, fontStyle: 'bold' },
                        alternateRowStyles: { fillColor: [240, 250, 245] },
                        margin: { left: 10, right: 10 },
                        tableWidth: 'auto'
                    });

                    const pageHeight = doc.internal.pageSize.getHeight();
                    doc.setDrawColor(96, 186, 151);
                    doc.setLineWidth(0.5);
                    doc.line(10, pageHeight - 35, pageWidth - 10, pageHeight - 35);

                    doc.setFontSize(12);
                    doc.text("Signature:", pageWidth - 60, pageHeight - 25);
                    doc.text(`Date: ${new Date().toLocaleDateString()}`, pageWidth - 60, pageHeight - 15);

                    doc.setFontSize(10);
                    doc.setTextColor(150);
                    doc.text('Green Admin - Export PDF', pageWidth / 2, pageHeight - 5, { align: 'center' });

                    doc.save('stations.pdf');
                };
                img.onerror = () => alert('Logo introuvable ou erreur de chargement.');
            });

            // Sorting Logic
            function sortStationsTable(compareFn) {
                const tbody = document.querySelector('#stationsFullTable tbody');
                const rows = Array.from(tbody.querySelectorAll('tr'));
                rows.sort(compareFn);
                rows.forEach(row => tbody.appendChild(row));
            }

            document.getElementById('sortByName').addEventListener('click', e => {
                e.preventDefault();
                sortStationsTable((a, b) => a.children[2].textContent.trim().toLowerCase().localeCompare(b.children[2].textContent.trim().toLowerCase()));
            });

            document.getElementById('sortByLocation').addEventListener('click', e => {
                e.preventDefault();
                sortStationsTable((a, b) => a.children[3].textContent.trim().toLowerCase().localeCompare(b.children[3].textContent.trim().toLowerCase()));
            });

            document.getElementById('sortByStatus').addEventListener('click', e => {
                e.preventDefault();
                sortStationsTable((a, b) => a.children[4].textContent.trim().toLowerCase().localeCompare(b.children[4].textContent.trim().toLowerCase()));
            });

            // Search Bar Logic (Modal)
            document.getElementById('stationSearchInput').addEventListener('input', function () {
                const searchValue = this.value.trim().toLowerCase();
                const rows = document.querySelectorAll('#stationsFullTable tbody tr');
                let found = false;

                rows.forEach(row => {
                    row.style.backgroundColor = '';
                    const name = row.children[2].textContent.trim().toLowerCase();
                    const city = row.children[3].textContent.trim().toLowerCase();
                    if (name.includes(searchValue) || city.includes(searchValue)) {
                        row.style.backgroundColor = '#ffe082';
                        found = true;
                    }
                });

                const messageDiv = document.getElementById('stationSearchMessage');
                messageDiv.style.display = found ? 'none' : 'block';
                messageDiv.textContent = found ? '' : 'Aucune station trouvée pour cette recherche.';
                messageDiv.className = found ? '' : 'alert alert-warning';
            });

            // Main Table Search Bar Logic
            document.getElementById('mainStationSearchInput').addEventListener('input', function () {
                const searchValue = this.value.trim().toLowerCase();
                const rows = document.querySelectorAll('#stationsTableBody tr');
                let found = false;

                rows.forEach(row => {
                    row.style.backgroundColor = '';
                    const name = row.children[1].textContent.trim().toLowerCase();
                    const city = row.children[2].textContent.trim().toLowerCase();
                    if (name.includes(searchValue) || city.includes(searchValue)) {
                        row.style.backgroundColor = '#ffe082';
                        found = true;
                    }
                });

                const messageDiv = document.getElementById('mainStationSearchMessage');
                messageDiv.style.display = found ? 'none' : 'block';
                messageDiv.textContent = found ? '' : 'Aucune station trouvée pour cette recherche.';
                messageDiv.className = found ? '' : 'alert alert-warning';
            });

            // Pagination for Main Table
            const rowsPerPage = 7;
            const tableBody = document.getElementById('stationsTableBody');
            const rows = Array.from(tableBody.querySelectorAll('tr'));
            const paginationControls = document.getElementById('paginationControls');

            function displayPage(page) {
                const start = (page - 1) * rowsPerPage;
                const end = start + rowsPerPage;
                rows.forEach((row, index) => {
                    row.style.display = index >= start && index < end ? '' : 'none';
                });
            }

            function setupPagination() {
                const totalPages = Math.ceil(rows.length / rowsPerPage);
                paginationControls.innerHTML = '';

                for (let i = 1; i <= totalPages; i++) {
                    const button = document.createElement('button');
                    button.textContent = i;
                    button.className = 'stations-pagination-btn';
                    button.addEventListener('click', () => {
                        displayPage(i);
                        document.querySelectorAll('#paginationControls button').forEach(btn => btn.classList.remove('active'));
                        button.classList.add('active');
                    });
                    if (i === 1) button.classList.add('active');
                    paginationControls.appendChild(button);
                }
            }

            if (rows.length > 0) {
                displayPage(1);
                setupPagination();
            }

            // Pagination for Modal Table
            const rowsPerPageModal = 7;
            const modalTableBody = document.querySelector('#stationsFullTable tbody');
            const modalRows = Array.from(modalTableBody.querySelectorAll('tr'));
            const modalPaginationControls = document.getElementById('modalPaginationControls');

            function displayModalPage(page) {
                const start = (page - 1) * rowsPerPageModal;
                const end = start + rowsPerPageModal;
                modalRows.forEach((row, index) => {
                    row.style.display = index >= start && index < end ? '' : 'none';
                });
            }

            function setupModalPagination() {
                const totalPages = Math.ceil(modalRows.length / rowsPerPageModal);
                modalPaginationControls.innerHTML = '';

                for (let i = 1; i <= totalPages; i++) {
                    const button = document.createElement('button');
                    button.textContent = i;
                    button.className = 'stations-pagination-btn';
                    button.addEventListener('click', () => {
                        displayModalPage(i);
                        document.querySelectorAll('#modalPaginationControls button').forEach(btn => btn.classList.remove('active'));
                        button.classList.add('active');
                    });
                    if (i === 1) button.classList.add('active');
                    modalPaginationControls.appendChild(button);
                }
            }

            if (modalRows.length > 0) {
                displayModalPage(1);
                setupModalPagination();
            }
        });
    </script>

    <!-- Styles -->
    <style>
        #stationsTableBody {
            max-height: 320px;
            overflow-y: auto;
            scroll-behavior: smooth;
        }
        #stationsTableBody::-webkit-scrollbar {
            width: 8px;
            background: #f1f1f1;
        }
        #stationsTableBody::-webkit-scrollbar-thumb {
            background: #60BA97;
            border-radius: 4px;
        }
        .stations-pagination-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            margin: 0 4px;
            border-radius: 50%;
            border: none;
            background: #fff;
            color: #3a9856;
            font-weight: bold;
            font-size: 18px;
            box-shadow: 0 2px 6px rgba(58, 152, 86, 0.08);
            cursor: pointer;
            transition: background 0.3s, color 0.3s, transform 0.2s cubic-bezier(.4, 2, .6, 1);
            outline: none;
        }
        .stations-pagination-btn.active,
        .stations-pagination-btn:focus {
            background: #3a9856;
            color: #fff;
        }
        .stations-pagination-btn:hover:not(:disabled) {
            background: #3a9856;
            color: #fff;
            transform: scale(1.18) rotate(-6deg);
            box-shadow: 0 4px 16px rgba(58, 152, 86, 0.18);
        }
        .stations-pagination-btn:disabled {
            background: #e0e0e0;
            color: #bdbdbd;
            cursor: not-allowed;
            box-shadow: none;
        }
    </style>
</body>
</html>