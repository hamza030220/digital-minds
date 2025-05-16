<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../../../CONFIG/db.php';
// Debug check - remove this after fixing


$error = '';
$success = '';

try {
    // Initialize database connection
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new PDOException("Failed to connect to database");
    }
    
    // Debug: Vérifier les stations
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM stations");
    $stationsCount = $stmt->fetch()['count'];
    error_log("Nombre de stations dans la base: " . $stationsCount);

    // Récupérer tous les trajets
    $stmt = $pdo->query("SELECT t.id, t.route_coordinates FROM trajets t WHERE t.route_coordinates IS NOT NULL LIMIT 1");
    $sampleTrajet = $stmt->fetch();
    //error_log("Exemple de coordonnées de trajet: " . print_r($sampleTrajet['route_coordinates'], true));

    // Récupérer tous les trajets (version originale)
    $stmt = $pdo->query("SELECT t.id, t.distance, t.route_description AS description, 
                         t.start_point_name, t.end_point_name, t.route_coordinates,
                         t.co2_saved, t.battery_energy, t.fuel_saved
                         FROM trajets t"); // <-- Removed ORDER BY t.id DESC
    $trajets = $stmt->fetchAll();

    // Get all stations with coordinates
    $stmt = $pdo->query("SELECT id, name, 
                     CAST(TRIM(SUBSTRING_INDEX(location, ',', 1)) AS DECIMAL(10,6)) as latitude,
                     CAST(TRIM(SUBSTRING_INDEX(location, ',', -1)) AS DECIMAL(10,6)) as longitude 
                     FROM stations");
    $stations = $stmt->fetchAll();

    // Calculate nearest station for each route with Haversine formula
    foreach ($trajets as &$trajet) {
        $nearestStation = null;
        $minDistance = PHP_FLOAT_MAX;
        
        if (!empty($trajet['route_coordinates'])) {
            $routeCoords = json_decode($trajet['route_coordinates'], true);
            $startPoint = $routeCoords[0];
            
            foreach ($stations as $station) {
                // Haversine formula for accurate distance
                $lat1 = deg2rad($startPoint['lat']);
                $lon1 = deg2rad($startPoint['lng']);
                $lat2 = deg2rad($station['latitude']);
                $lon2 = deg2rad($station['longitude']);
                
                $dlat = $lat2 - $lat1;
                $dlon = $lon2 - $lon1;
                
                $a = sin($dlat/2) * sin($dlat/2) + 
                     cos($lat1) * cos($lat2) * 
                     sin($dlon/2) * sin($dlon/2);
                $c = 2 * atan2(sqrt($a), sqrt(1-$a));
                $distance = 6371 * $c; // Earth radius in km
                
                if ($distance < $minDistance) {
                    $minDistance = $distance;
                    $nearestStation = $station['name'] . ' (' . round($distance, 2) . ' km)';
                }
            }
        }
        
        $trajet['nearest_station'] = $nearestStation ?? 'N/A';
    }
    unset($trajet);

} catch (PDOException $e) {
    error_log($e->getMessage());
    $error = "Erreur lors de la récupération des trajets.";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Trajets - Green Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/styles.css" rel="stylesheet">
    <link href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" rel="stylesheet">
    <style>
        /* Sidebar Styles */
        .sidebar {
            height: 100vh;
            width: 250px;
            position: fixed;
            top: 0;
            left: -250px;
            background-color: rgba(96, 186, 151, 0.9);
            backdrop-filter: blur(5px);
            transition: left 0.3s ease;
            z-index: 1000;
        }

        .sidebar.show {
            left: 0;
        }

        .sidebar-header {
            padding: 20px;
            text-align: center;
        }

        .sidebar-brand img {
            width: 60%;
            height: auto;
        }

        .sidebar-content {
            padding: 20px;
        }

        .sidebar-nav {
            list-style: none;
            padding: 0;
        }

        .sidebar-nav-item {
            margin-bottom: 10px;
        }

        .sidebar-nav-link {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #d0f0d6;
            text-decoration: none;
            font-size: 15px;
            border-radius: 6px;
            transition: background-color 0.3s ease;
        }

        .sidebar-nav-link:hover {
            background-color: #1b5e20;
            color: white;
        }

        .sidebar-nav-link.active {
            background-color: #388e3c;
            color: white;
        }

        .sidebar-nav-icon {
            margin-right: 10px;
        }

        .sidebar-footer {
            position: absolute;
            bottom: 20px;
            width: 100%;
            padding: 20px;
            text-align: center;
        }

        .sidebar-footer .btn {
            font-size: 14px;
            width: 100%;
            margin-bottom: 10px;
        }

        /* Sidebar Toggle Button */
        .sidebar-toggler {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1100;
            background-color: #60BA97;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .sidebar-toggler:hover {
            background-color: #388e3c;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .sidebar {
                left: -250px;
            }

            .sidebar.show {
                left: 0;
            }
        }

        /* Dark Mode */
        body.dark-mode {
            background: linear-gradient(45deg, #1a3c34, #2c3e50, #34495e, #2e7d32);
            background-size: 200% 200%;
            animation: gradientShift 15s ease infinite;
            color: #e0e0e0;
        }

        body.dark-mode .sidebar {
            background-color: rgba(51, 51, 51, 0.9);
        }

        body.dark-mode .sidebar-nav-link {
            color: #ffffff;
        }

        body.dark-mode .sidebar-nav-link:hover {
            background-color: #444444;
        }

        body.dark-mode .main-content {
            background: rgba(30, 30, 30, 0.8);
        }

        body.dark-mode .main-content h1 {
            color: #4caf50;
        }

        body.dark-mode .section-content,
        body.dark-mode .stat-card,
        body.dark-mode #userChart,
        body.dark-mode #ageChart {
            background-color: rgba(50, 50, 50, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        body.dark-mode .task-bar {
            background-color: #1b5e20;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        body.dark-mode .translate-btn {
            background-color: #388e3c;
        }

        body.dark-mode .translate-btn:hover {
            background-color: #4caf50;
        }

        body.dark-mode .user-table {
            background-color: rgba(50, 50, 50, 0.9);
            border: 1px solid rgba(76, 175, 80, 0.5);
        }

        body.dark-mode .user-table th {
            background-color: rgba(56, 142, 60, 0.95);
            color: #ffffff;
        }

        body.dark-mode .user-table td {
            border-color: rgba(76, 175, 80, 0.5);
            color: #ffffff;
        }
    </style>
</head>
<body>
<?php 
    $basePath = '../';
    $currentPage = 'trajets';

?>
<div id="main" class="main-content">
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Liste des Trajets</h1>
            <div class="d-flex flex-column align-items-end mb-3">
                <a href="add.php" class="btn btn-success mb-2">
                    <i class="bi bi-plus-circle"></i> Nouveau Trajet
                </a>
                <button id="showTrajetsTableModalBtn" class="btn btn-success">
                    <i class="bi bi-table"></i> Afficher le tableau complet
                </button>
            </div>
        </div>
        <!-- Move the search bar below the buttons -->
        <div class="mb-3">
            <input type="text" id="mainTrajetSearchInput" class="form-control" placeholder="Rechercher par description ou départ...">
            <div id="mainTrajetSearchMessage" class="mt-2" style="display:none;"></div>
        </div>
        <!-- Modal for full trajets table -->
        </div>
        <div class="modal fade" id="trajetsTableModal" tabindex="-1" aria-labelledby="trajetsTableModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <div class="w-100 d-flex flex-wrap align-items-center justify-content-between">
                            <div>
                                <h5 class="modal-title" id="trajetsTableModalLabel">
                                    <i class="bi bi-table"></i> Tableau complet des trajets
                                </h5>
                            </div>
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <button id="exportTrajetsPdfBtn" class="btn btn-outline-danger btn-sm">
                                    <i class="bi bi-file-earmark-pdf"></i> Exporter PDF
                                </button>
                                <button class="btn btn-outline-secondary btn-sm" onclick="sortTrajetsTable(5, 'float')">
                                    <i class="bi bi-arrow-down-up"></i> Trier par Distance
                                </button>
                                <button class="btn btn-outline-secondary btn-sm" onclick="sortTrajetsTable(6, 'float')">
                                    <i class="bi bi-arrow-down-up"></i> Trier par CO₂
                                </button>
                                <button class="btn btn-outline-secondary btn-sm" onclick="sortTrajetsTable(7, 'float')">
                                    <i class="bi bi-arrow-down-up"></i> Trier par Batterie
                                </button>
                            </div>
                        </div>
                        <button type="button" class="btn-close ms-2" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <!-- Add search bar for modal table here -->
                    <div class="mb-3 px-3">
                        <input type="text" id="modalTrajetSearchInput" class="form-control" placeholder="Rechercher dans le tableau complet...">
                        <div id="modalTrajetSearchMessage" class="mt-2" style="display:none;"></div>
                    </div>
                    <div class="modal-body" >
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered mb-0" id="trajetsFullTable">
                                <thead>
                                    <tr>
                                        <th style="width:40px;">
                                            <input type="checkbox" id="checkAllTrajetRows" />
                                        </th>
                                        <th>ID</th>
                                        <th>Description</th>
                                        <th>Départ</th>
                                        <th>Arrivée</th>
                                        <th>Distance</th>
                                        <th>CO₂ (g)</th>
                                        <th>Batterie (Wh)</th>
                                        <th>Carburant (L)</th>
                                        <th>Station la plus proche</th>
                                        <th style="display:none;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($trajets)): ?>
                                        <?php foreach ($trajets as $trajet): ?>
                                            <tr>
                                                <td>
                                                    <input type="checkbox" class="trajet-row-checkbox" />
                                                </td>
                                                <td><?php echo htmlspecialchars($trajet['id']); ?></td>
                                                <td><?php echo htmlspecialchars($trajet['description']); ?></td>
                                                <td><?php echo htmlspecialchars($trajet['start_point_name']); ?></td>
                                                <td><?php echo htmlspecialchars($trajet['end_point_name']); ?></td>
                                                <td><?php echo htmlspecialchars($trajet['distance']); ?> km</td>
                                                <td><?php echo htmlspecialchars($trajet['co2_saved']); ?></td>
                                                <td><?php echo htmlspecialchars($trajet['battery_energy']); ?></td>
                                                <td><?php echo htmlspecialchars($trajet['fuel_saved']); ?></td>
                                                <td><?php echo htmlspecialchars($trajet['nearest_station']); ?></td>
                                                <td style="display:none;">
                                                    <a href="edit.php?id=<?php echo $trajet['id']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <a href="delete.php?id=<?php echo $trajet['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce trajet ?');">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="11" class="text-center">Aucun trajet trouvé</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div id="modalTrajetsPaginationControls" class="d-flex justify-content-center mt-3"></div>
                    </div>
                </div>
            </div>
        </div>
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="mainTrajetsTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Description</th>
                                <th>Départ</th>
                                <th>Arrivée</th>
                                <th>Distance</th>
                                <th>CO₂ (g)</th>
                                <th>Batterie (Wh)</th>
                                <th>Carburant (L)</th>
                                <th>Station la plus proche</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($trajets)): ?>
                                <?php foreach ($trajets as $trajet): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($trajet['id']); ?></td>
                                        <td><?php echo htmlspecialchars($trajet['description']); ?></td>
                                        <td><?php echo htmlspecialchars($trajet['start_point_name']); ?></td>
                                        <td><?php echo htmlspecialchars($trajet['end_point_name']); ?></td>
                                        <td><?php echo htmlspecialchars($trajet['distance']); ?> km</td>
                                        <td><?php echo htmlspecialchars($trajet['co2_saved']); ?></td>
                                        <td><?php echo htmlspecialchars($trajet['battery_energy']); ?></td>
                                        <td><?php echo htmlspecialchars($trajet['fuel_saved']); ?></td>
                                        <td><?php echo htmlspecialchars($trajet['nearest_station']); ?></td>
                                        <td>
                                            <a href="edit.php?id=<?php echo $trajet['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="delete.php?id=<?php echo $trajet['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce trajet ?');">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="10" class="text-center">Aucun trajet trouvé</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div id="trajetsPaginationControls" class="d-flex justify-content-center mt-3"></div>
            </div>
        </div>
    </div>
    <!-- Map card: place inside the same container as the table for matching width -->
    <div class="container mt-4">
        <div class="d-flex justify-content-center">
            <div class="card mb-4" style="width: 100%;">
                <div class="card-header">
                    <i class="bi bi-map"></i> Carte des trajets
                </div>
                <div class="card-body" style="height: 400px;">
                    <div id="trajetsMap" style="height: 100%;"></div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
 
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
   <!-- <script src="../assets/js/scripts_trajets_list.js"></script> -->
    <script>
        // Move this function OUTSIDE of DOMContentLoaded!
        function sortTrajetsTable(colIndex, type) {
            const table = document.getElementById('trajetsFullTable');
            const tbody = table.tBodies[0];
            const rows = Array.from(tbody.querySelectorAll('tr'));
            let asc = table.getAttribute('data-sort-dir'+colIndex) !== 'asc';
            rows.sort((a, b) => {
                let aText = a.children[colIndex].textContent.trim().replace(' km', '');
                let bText = b.children[colIndex].textContent.trim().replace(' km', '');
                if (type === 'float') {
                    aText = parseFloat(aText.replace(',', '.')) || 0;
                    bText = parseFloat(bText.replace(',', '.')) || 0;
                }
                if (aText < bText) return asc ? -1 : 1;
                if (aText > bText) return asc ? 1 : -1;
                return 0;
            });
            rows.forEach(row => tbody.appendChild(row));
            // Store sort direction per column
            table.setAttribute('data-sort-dir'+colIndex, asc ? 'asc' : 'desc');
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Modal show logic
            document.getElementById('showTrajetsTableModalBtn').addEventListener('click', function() {
                var modal = new bootstrap.Modal(document.getElementById('trajetsTableModal'));
                modal.show();
            });

            // Check all rows logic
            const checkAllRows = document.getElementById('checkAllTrajetRows');
            const getRowCheckboxes = () => document.querySelectorAll('#trajetsFullTable .trajet-row-checkbox');
            checkAllRows.addEventListener('change', function() {
                getRowCheckboxes().forEach(cb => cb.checked = checkAllRows.checked);
            });
            document.querySelector('#trajetsFullTable tbody').addEventListener('change', function(e) {
                if (e.target.classList.contains('trajet-row-checkbox')) {
                    if (!e.target.checked) checkAllRows.checked = false;
                    else if ([...getRowCheckboxes()].every(cb => cb.checked)) checkAllRows.checked = true;
                }
            });
            



            // PDF Export logic
            document.getElementById('exportTrajetsPdfBtn').addEventListener('click', function() {
                const table = document.getElementById('trajetsFullTable');
                // Only get headers except the first (checkbox) and last (Actions)
                const headers = Array.from(table.querySelectorAll('thead th'))
                    .slice(1, -1)
                    .map(th => th.textContent.trim());
                const selectedRows = [];
                table.querySelectorAll('tbody tr').forEach(tr => {
                    const cb = tr.querySelector('.trajet-row-checkbox');
                    if (cb && cb.checked) {
                        // Only get tds except the first (checkbox) and last (Actions)
                        const tds = Array.from(tr.children).slice(1, -1);
                        const rowData = tds.map(td => td.textContent.trim());
                        selectedRows.push(rowData);
                    }
                });
                if (selectedRows.length === 0) {
                    alert('Veuillez sélectionner au moins une ligne à exporter.');
                    return;
                }
                const { jsPDF } = window.jspdf;
                const doc = new jsPDF();

                // Add logo centered at the top
                const img = new Image();
                img.src = '../image/ho.png';
                img.onload = function() {
                    const pageWidth = doc.internal.pageSize.getWidth();
                    const imgWidth = 40;
                    const aspectRatio = img.naturalWidth / img.naturalHeight;
                    const imgHeight = imgWidth / aspectRatio;
                    const x = (pageWidth - imgWidth) / 2;
                    doc.addImage(img, 'PNG', x, 10, imgWidth, imgHeight);

                    // Title
                    doc.setFont('helvetica', 'bold');
                    doc.setFontSize(18);
                    doc.text('Liste des Trajets', pageWidth / 2, imgHeight + 25, { align: 'center' });

                    // Subtitle (date)
                    doc.setFont('helvetica', 'normal');
                    doc.setFontSize(12);
                    doc.text('Exporté le : ' + new Date().toLocaleDateString(), pageWidth / 2, imgHeight + 33, { align: 'center' });

                    // Table
                    doc.autoTable({
                        head: [headers],
                        body: selectedRows,
                        startY: imgHeight + 40,
                        theme: 'grid',
                        styles: { fontSize: 11, cellPadding: 3 },
                        headStyles: { fillColor: [96,186,151], textColor: 255, fontStyle: 'bold' },
                        alternateRowStyles: { fillColor: [240, 250, 245] },
                        margin: { left: 10, right: 10 },
                        tableWidth: 'auto',
                    });

                    // Footer line
                    const pageHeight = doc.internal.pageSize.getHeight();
                    doc.setDrawColor(96,186,151);
                    doc.setLineWidth(0.5);
                    doc.line(10, pageHeight - 35, pageWidth - 10, pageHeight - 35);

                    // Signature and date, right-aligned
                    doc.setFontSize(12);
                    doc.text("Signature:", pageWidth - 60, pageHeight - 25);
                    doc.text("Date: " + new Date().toLocaleDateString(), pageWidth - 60, pageHeight - 15);

                    // Footer text
                    doc.setFontSize(10);
                    doc.setTextColor(150);
                    doc.text('Green Admin - Export PDF', pageWidth / 2, pageHeight - 5, { align: 'center' });

                    doc.save('trajets.pdf');
                };
                img.onerror = function() {
                    alert("Logo introuvable ou erreur de chargement.");
                };
            });
        });
    // --- Main Table Search Bar Logic ---
    document.getElementById('mainTrajetSearchInput').addEventListener('input', function() {
        const searchValue = this.value.trim().toLowerCase();
        const tableBody = document.querySelector('#mainTrajetsTable tbody'); // <-- More specific selector
        const rows = tableBody.querySelectorAll('tr');
        let found = false;
    
        // Remove previous highlights
        rows.forEach(row => {
            row.style.backgroundColor = '';
        });
    
        if (searchValue === '') {
            document.getElementById('mainTrajetSearchMessage').style.display = 'none';
            return;
        }
    
        rows.forEach(row => {
            const description = row.children[1].textContent.trim().toLowerCase();
            const startPoint = row.children[2].textContent.trim().toLowerCase();
            const distance = row.children[4].textContent.trim().toLowerCase().replace(' km', '');
            const nearestStation = row.children[8].textContent.trim().toLowerCase();
    
            if (
                description.includes(searchValue) ||
                startPoint.includes(searchValue) ||
                distance.includes(searchValue) ||
                nearestStation.includes(searchValue)
            ) {
                row.style.backgroundColor = '#ffe082';
                found = true;
            }
        });
    
        const messageDiv = document.getElementById('mainTrajetSearchMessage');
        if (!found) {
            messageDiv.textContent = "Aucun trajet trouvé pour cette recherche.";
            messageDiv.className = "alert alert-warning";
            messageDiv.style.display = 'block';
        } else {
            messageDiv.style.display = 'none';
        }
    });
    // --- Modal Table Search Bar Logic ---
    document.getElementById('modalTrajetSearchInput').addEventListener('input', function() {
        const searchValue = this.value.trim().toLowerCase();
        const tableBody = document.querySelector('#trajetsFullTable tbody');
        const rows = tableBody.querySelectorAll('tr');
        let found = false;
    
        // Remove previous highlights
        rows.forEach(row => {
            row.style.backgroundColor = '';
        });
    
        if (searchValue === '') {
            document.getElementById('modalTrajetSearchMessage').style.display = 'none';
            return;
        }
    
        rows.forEach(row => {
            // Modal table: description (2), start_point_name (3), distance (5), nearest_station (9)
            const description = row.children[2].textContent.trim().toLowerCase();
            const startPoint = row.children[3].textContent.trim().toLowerCase();
            const distance = row.children[5].textContent.trim().toLowerCase().replace(' km', '');
            const nearestStation = row.children[9].textContent.trim().toLowerCase();
    
            if (
                description.includes(searchValue) ||
                startPoint.includes(searchValue) ||
                distance.includes(searchValue) ||
                nearestStation.includes(searchValue)
            ) {
                row.style.backgroundColor = '#ffe082';
                found = true;
            }
        });
    
        const messageDiv = document.getElementById('modalTrajetSearchMessage');
        if (!found) {
            messageDiv.textContent = "Aucun trajet trouvé pour cette recherche.";
            messageDiv.className = "alert alert-warning";
            messageDiv.style.display = 'block';
        } else {
            messageDiv.style.display = 'none';
        }
    });
    // --- Trajets Map Logic ---
    document.addEventListener('DOMContentLoaded', function() {
        // Modal and PDF
    
        // Trajets map
        const trajets = <?php echo json_encode($trajets); ?>;
        let allCoords = [];
        const map = L.map('trajetsMap').setView([36.8, 10.18], 7); // Centered on Tunisia
    
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);
    
        trajets.forEach(trajet => {
            if (trajet.route_coordinates) {
                try {
                    const coords = JSON.parse(trajet.route_coordinates);
                    if (Array.isArray(coords) && coords.length > 1) {
                        const latlngs = coords.map(pt => [pt.lat, pt.lng]);
                        allCoords = allCoords.concat(latlngs);
                        // Draw polyline for the trajet
                        L.polyline(latlngs, {
                            color: '#2196f3',
                            weight: 4,
                            opacity: 0.8
                        }).addTo(map)
                        .bindPopup(
                            `<b>Trajet #${trajet.id}</b><br>
                            ${trajet.description ? trajet.description + '<br>' : ''}
                            Départ: ${trajet.start_point_name || ''}<br>
                            Arrivée: ${trajet.end_point_name || ''}<br>
                            Distance: ${trajet.distance || ''} km`
                        );
                        // Optionally, mark start and end points
                        L.circleMarker(latlngs[0], {radius:6, color:'#43a047', fillColor:'#43a047', fillOpacity:0.9}).addTo(map)
                            .bindTooltip('Départ');
                        L.circleMarker(latlngs[latlngs.length-1], {radius:6, color:'#e53935', fillColor:'#e53935', fillOpacity:0.9}).addTo(map)
                            .bindTooltip('Arrivée');
                    }
                } catch(e) {
                    // Ignore invalid JSON
                }
            }
        });
        // Fit map to all trajets
        if (allCoords.length > 0) {
            map.fitBounds(allCoords);
        }
    });
    </script>
    <script>
document.addEventListener('DOMContentLoaded', function () {
    const rowsPerPageModal = 5; // Nombre de lignes par page dans le modal
    const modalTableBody = document.querySelector('#trajetsFullTable tbody');
    const modalRows = Array.from(modalTableBody.querySelectorAll('tr'));
    const modalPaginationControls = document.getElementById('modalTrajetsPaginationControls');

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
            button.className = 'stations-pagination-btn'; // Réutilisation du style des boutons
            button.addEventListener('click', () => {
                displayModalPage(i);
                document.querySelectorAll('#modalTrajetsPaginationControls button').forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');
            });

            if (i === 1) button.classList.add('active'); // Activer le premier bouton par défaut
            modalPaginationControls.appendChild(button);
        }
    }

    // Initialisation
    if (modalRows.length > 0) {
        displayModalPage(1);
        setupModalPagination();
    }
});
</script>
    <script>
document.addEventListener('DOMContentLoaded', function () {
    const rowsPerPage = 4; // Nombre de lignes par page
    const tableBody = document.querySelector('#mainTrajetsTable tbody');
    const rows = Array.from(tableBody.querySelectorAll('tr'));
    const paginationControls = document.getElementById('trajetsPaginationControls');

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
            button.className = 'stations-pagination-btn'; // Réutilisation du style des boutons
            button.addEventListener('click', () => {
                displayPage(i);
                document.querySelectorAll('#trajetsPaginationControls button').forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');
            });

            if (i === 1) button.classList.add('active'); // Activer le premier bouton par défaut
            paginationControls.appendChild(button);
        }
    }

    // Initialisation
    if (rows.length > 0) {
        displayPage(1);
        setupPagination();
    }
});
</script>
<style>
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
</div>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a class="sidebar-brand" href="<?php echo $basePath; ?>dashboard.php?section=stats">
                <img src="<?php echo $basePath; ?>logo.jpg" alt="Green.tn">
            </a>
        </div>
        <div class="sidebar-content">
            <ul class="sidebar-nav">
                <li class="sidebar-nav-item">
                    <a class="sidebar-nav-link" href="<?php echo $basePath; ?>dashboard.php?section=stats" data-translate="home">
                        <span class="sidebar-nav-icon"><i class="bi bi-house-door"></i></span>
                        <span class="sidebar-nav-text">Accueil</span>
                    </a>
                </li>
                <li class="sidebar-nav-item">
                    <a class="sidebar-nav-link" href="?page=gestion_utilisateurs" data-translate="profile_management">
                        <span class="sidebar-nav-icon"><i class="bi bi-person"></i></span>
                        <span class="sidebar-nav-text">Gestion de votre profil</span>
                    </a>
                </li>
                <li class="sidebar-nav-item">
                    <a class="sidebar-nav-link" href="<?php echo $basePath; ?>reservation.php" data-translate="reservations">
                        <span class="sidebar-nav-icon"><i class="bi bi-calendar"></i></span>
                        <span class="sidebar-nav-text">Voir réservations</span>
                    </a>
                </li>
               <li class="sidebar-nav-item">
                <a class="sidebar-nav-link" href="<?php echo $basePath; ?>../../VIEW/reclamation/reclamations_utilisateur.php" data-translate="complaints">
                    <span class="sidebar-nav-icon"><i class="bi bi-envelope"></i></span>
                    <span class="sidebar-nav-text">Réclamations</span>
                </a>
            </li>
            <li class="sidebar-nav-item">
                    <a class="sidebar-nav-link" href="../../reclamation/liste_avis.php">
                        <span class="sidebar-nav-icon"><i class="bi bi-star"></i></span>
                        <span class="sidebar-nav-text"> Avis</span>
                    </a>
                <li class="sidebar-nav-item">
                <a class="sidebar-nav-link" href="../velos.php" data-translate="bikes_batteries">
                        <span class="sidebar-nav-icon"><i class="bi bi-bicycle"></i></span>
                        <span class="sidebar-nav-text">Vélos & Batteries</span>
                    </a>
                </li>
                <li class="sidebar-nav-item">
                    <a class="sidebar-nav-link" href="<?php echo $basePath; ?>forum_admin.php" data-translate="forum">
                        <span class="sidebar-nav-icon"><i class="bi bi-chat"></i></span>
                        <span class="sidebar-nav-text">Forum</span>
                    </a>
                </li>
                <li class="sidebar-nav-item">
                    <a class="sidebar-nav-link" href="<?php echo $basePath; ?>stations/list.php">
                        <span class="sidebar-nav-icon"><i class="bi bi-geo-alt"></i></span>
                        <span class="sidebar-nav-text">Stations</span>
                    </a>
                </li>
                <li class="sidebar-nav-item">
                    <a class="sidebar-nav-link <?php echo $currentPage === 'trajets' ? 'active' : ''; ?>" href="<?php echo $basePath; ?>trajets/list.php">
                        <span class="sidebar-nav-icon"><i class="bi bi-map"></i></span>
                        <span class="sidebar-nav-text">Trajets</span>
                    </a>
                </li>
                <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'technicien')): ?>
                    <li class="sidebar-nav-item">
                        <a class="sidebar-nav-link" href="../repairs.html" data-translate="repair_issues">
                            <span class="sidebar-nav-icon"><i class="bi bi-tools"></i></span>
                            <span class="sidebar-nav-text">Réparer les pannes</span>
                        </a>
                    </li>
                    <li class="sidebar-nav-item">
                        <a class="sidebar-nav-link" href="update_profil_admin.php" data-translate="profile_management">
                            <span class="sidebar-nav-icon"><i class="bi bi-person"></i></span>
                            <span class="sidebar-nav-text">Editer mon profil</span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
        <div class="sidebar-footer">
            <a href="<?php echo $basePath; ?>logout.php" class="btn btn-outline-light" data-translate="logout">
                <i class="bi bi-box-arrow-right"></i> Déconnexion
            </a>
            <a href="#" id="darkModeToggle" class="btn btn-outline-light mt-2" data-translate="dark_mode">
                <i class="bi bi-moon"></i> Mode Sombre
            </a>
        </div>
    </div>

    <!-- Sidebar Toggle Button -->
    <button class="sidebar-toggler" type="button" id="sidebarToggle">
        <i class="bi bi-list"></i>
    </button>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const darkModeToggle = document.getElementById('darkModeToggle');
            const mainContent = document.getElementById('main');

            // Toggle sidebar
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('show');
                    mainContent.classList.toggle('main-content-expanded');
                });
            }

            // Dark mode toggle
            if (darkModeToggle) {
                darkModeToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    document.body.classList.toggle('dark-mode');
                    
                    // Save preference to localStorage
                    const isDarkMode = document.body.classList.contains('dark-mode');
                    localStorage.setItem('darkMode', isDarkMode);
                    
                    // Update button icon
                    const icon = this.querySelector('i');
                    if (isDarkMode) {
                        icon.classList.remove('bi-moon');
                        icon.classList.add('bi-sun');
                        this.querySelector('span').textContent = 'Mode Clair';
                    } else {
                        icon.classList.remove('bi-sun');
                        icon.classList.add('bi-moon');
                        this.querySelector('span').textContent = 'Mode Sombre';
                    }
                });

                // Check for saved dark mode preference
                if (localStorage.getItem('darkMode') === 'true') {
                    document.body.classList.add('dark-mode');
                    const icon = darkModeToggle.querySelector('i');
                    icon.classList.remove('bi-moon');
                    icon.classList.add('bi-sun');
                    darkModeToggle.querySelector('span').textContent = 'Mode Clair';
                }
            }

            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', function(e) {
                if (window.innerWidth <= 992) {
                    if (sidebar && !sidebar.contains(e.target) && sidebarToggle && !sidebarToggle.contains(e.target)) {
                        sidebar.classList.remove('show');
                        mainContent.classList.remove('main-content-expanded');
                    }
                }
            });

            // Handle window resize
            window.addEventListener('resize', function() {
                if (window.innerWidth > 992) {
                    sidebar.classList.remove('show');
                    mainContent.classList.remove('main-content-expanded');
                }
            });
        });
    </script>
</body>
</html>
