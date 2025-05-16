<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../../../CONFIG/db.php';

$error = '';
$success = '';

try {
    $pdo = getDBConnection();
    
    // Fetch city as well
    $stmt = $pdo->query("SELECT id, name, city, location, status FROM stations ");
    $stations = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log($e->getMessage());
    $error = "Erreur lors de la récupération des stations.";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Stations - Green Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/styles.css" rel="stylesheet">
</head>
<body>
<?php 
    $basePath = '../';
    $currentPage = 'stations';

?>
<div id="main" class="main-content">
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Liste des Stations</h1>
        </div>
        <div class="d-flex flex-column align-items-end mb-3">
            <a href="add.php" class="btn btn-success mb-2">
                <i class="bi bi-plus-circle"></i> Nouvelle Station
            </a>
            <button id="showStationsTableModalBtn" class="btn btn-success">
                <i class="bi bi-table"></i> Afficher le tableau complet
            </button>
        </div>
        <!-- Search bar for main table (moved below buttons) -->
        <div class="mb-3">
            <input type="text" id="mainStationSearchInput" class="form-control" placeholder="Rechercher par nom ou gouvernorat...">
            <div id="mainStationSearchMessage" class="mt-2" style="display:none;"></div>
        </div>
        <!-- Modal for full stations table -->
        <div class="modal fade" id="stationsTableModal" tabindex="-1" aria-labelledby="stationsTableModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <div class="w-100 d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-2">
                                <!-- Sort Dropdown Button (now on the left) -->
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" id="sortStationsBtn" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bi bi-funnel"></i> Trier
                                    </button>
                                    <ul class="dropdown-menu" aria-labelledby="sortStationsBtn">
                                        <li><a class="dropdown-item" href="#" id="sortByName">Nom (A-Z)</a></li>
                                        <li><a class="dropdown-item" href="#" id="sortByLocation">Localisation (A-Z)</a></li>
                                        <li><a class="dropdown-item" href="#" id="sortByStatus">Statut (A-Z)</a></li>
                                    </ul>
                                </div>
                                <button id="exportPdfBtn" class="btn btn-outline-danger btn-sm">
                                    <i class="bi bi-file-earmark-pdf"></i> Exporter PDF
                                </button>
                            </div>
                            <h5 class="modal-title mb-0" id="stationsTableModalLabel">
                                <i class="bi bi-table"></i> Tableau complet des stations
                            </h5>
                            <button type="button" class="btn-close ms-2" data-bs-dismiss="modal" aria-label="Fermer"></button>
                        </div>
                    </div>
                    <div class="modal-body" style="max-height:70vh; overflow:auto;">
                        <div class="mb-3">
                            <input type="text" id="stationSearchInput" class="form-control" placeholder="Rechercher par nom ou gouvernorat...">
                            <div id="stationSearchMessage" class="mt-2" style="display:none;"></div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered mb-0" id="stationsFullTable">
                                <thead>
                                    <tr>
                                        <th style="width:40px;">
                                            <input type="checkbox" id="checkAllRows" />
                                        </th>
                                        <th>ID</th>
                                        <th>Nom</th>
                                        <th>Localisation</th>
                                        <th>Statut</th>
                                        <th style="display:none;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($stations)): ?>
                                        <?php foreach ($stations as $station): ?>
                                            <tr>
                                                <td>
                                                    <input type="checkbox" class="row-checkbox" />
                                                </td>
                                                <td><?php echo htmlspecialchars($station['id']); ?></td>
                                                <td><?php echo htmlspecialchars($station['name']); ?></td>
                                                <!-- Show city instead of coordinates -->
                                                <td data-city="<?php echo htmlspecialchars($station['city']); ?>">
                                                    <?php echo htmlspecialchars($station['city']); ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($station['status']); ?></td>
                                                <td style="display:none;">
                                                    <a href="edit.php?id=<?php echo $station['id']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <a href="delete.php?id=<?php echo $station['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette station ?');">
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
        <!-- jsPDF & autotable CDN -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                document.getElementById('showStationsTableModalBtn').addEventListener('click', function() {
                    // Use getOrCreateInstance to avoid duplicate modals
                    var modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('stationsTableModal'));
                    modal.show();
                });

                // Check all rows logic
                const checkAllRows = document.getElementById('checkAllRows');
                const getRowCheckboxes = () => document.querySelectorAll('#stationsFullTable .row-checkbox');
                checkAllRows.addEventListener('change', function() {
                    getRowCheckboxes().forEach(cb => cb.checked = checkAllRows.checked);
                });
                document.querySelector('#stationsFullTable tbody').addEventListener('change', function(e) {
                    if (e.target.classList.contains('row-checkbox')) {
                        if (!e.target.checked) checkAllRows.checked = false;
                        else if ([...getRowCheckboxes()].every(cb => cb.checked)) checkAllRows.checked = true;
                    }
                });

                // PDF Export logic
                document.getElementById('exportPdfBtn').addEventListener('click', function() {
                    const table = document.getElementById('stationsFullTable');
                    // Only get headers except the first (checkbox) and last (Actions)
                    const headers = Array.from(table.querySelectorAll('thead th'))
                        .slice(1, -1)
                        .map(th => th.textContent.trim());
                    const selectedRows = [];
                    table.querySelectorAll('tbody tr').forEach(tr => {
                        const cb = tr.querySelector('.row-checkbox');
                        if (cb && cb.checked) {
                            // Only get tds except the first (checkbox) and last (Actions)
                            const tds = Array.from(tr.children).slice(1, -1);
                            // Use city name from data attribute for the location column
                            const rowData = tds.map((td, idx) => {
                                if (idx === 2) {
                                    return td.getAttribute('data-city') || td.textContent.trim();
                                }
                                return td.textContent.trim();
                            });
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
                        doc.text('Liste des Stations', pageWidth / 2, imgHeight + 25, { align: 'center' });

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

                        doc.save('stations.pdf');
                    };
                    img.onerror = function() {
                        alert("Logo introuvable ou erreur de chargement.");
                    };
                });
            });
        </script>
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="stationsTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nom</th>
                                <th>Localisation</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="stationsTableBody">
                            <?php if (!empty($stations)): ?>
                                <?php foreach ($stations as $station): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($station['id']); ?></td>
                                        <td><?php echo htmlspecialchars($station['name']); ?></td>
                                        <td><?php echo htmlspecialchars($station['city']); ?></td>
                                        <td><?php echo htmlspecialchars($station['status']); ?></td>
                                        <td>
                                            <a href="edit.php?id=<?php echo $station['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="delete.php?id=<?php echo $station['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette station ?');">
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
            </div>
        </div>
        <div class="card mb-4">
            <div class="card-header">
                <i class="bi bi-map"></i> Carte des Stations
            </div>
            <div class="card-body" style="height: 600px;">
                <div id="stationsMap" style="height: 100%; width: 100%;"></div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Leaflet CSS & JS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // Prepare stations data for JS
        const stations = <?php echo json_encode($stations); ?>;
        // Initialize map
        const map = L.map('stationsMap').setView([36.8, 10.18], 8); // Centered on Tunisia

        // Add OpenStreetMap tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        // Define custom icons
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

        // Add markers for each station with color based on status
        let bounds = [];
        stations.forEach(station => {
            if (station.location && station.location.includes(',')) {
                const [lat, lng] = station.location.split(',').map(coord => parseFloat(coord.trim()));
                if (!isNaN(lat) && !isNaN(lng)) {
                    let icon = blueIcon;
                    if (station.status && station.status.toLowerCase() === 'active') {
                        icon = greenIcon;
                    } else if (station.status && station.status.toLowerCase() === 'inactive') {
                        icon = redIcon;
                    }
                    const marker = L.marker([lat, lng], { icon: icon }).addTo(map)
                        .bindPopup(`<b>${station.name}</b><br>${station.location}<br>Statut: ${station.status}`);
                    bounds.push([lat, lng]);
                }
            }
        });
        if (bounds.length > 0) {
            map.fitBounds(bounds, {padding: [30, 30]});
        }
    </script>
</div>
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
                    <a class="sidebar-nav-link" href="velos.php?super_admin=1" data-translate="bikes_batteries">
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
                        <a class="sidebar-nav-link" href="repairs.html" data-translate="repair_issues">
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

        .main-content {
            margin-left: 70px;
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
</body>
</html>


<script>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('showStationsTableModalBtn').addEventListener('click', function() {
        var modal = new bootstrap.Modal(document.getElementById('stationsTableModal'));
        modal.show();
    });

    // Check all rows logic
    const checkAllRows = document.getElementById('checkAllRows');
    const getRowCheckboxes = () => document.querySelectorAll('#stationsFullTable .row-checkbox');
    checkAllRows.addEventListener('change', function() {
        getRowCheckboxes().forEach(cb => cb.checked = checkAllRows.checked);
    });
    document.querySelector('#stationsFullTable tbody').addEventListener('change', function(e) {
        if (e.target.classList.contains('row-checkbox')) {
            if (!e.target.checked) checkAllRows.checked = false;
            else if ([...getRowCheckboxes()].every(cb => cb.checked)) checkAllRows.checked = true;
        }
    });

    // PDF Export logic
    document.getElementById('exportPdfBtn').addEventListener('click', function() {
        const table = document.getElementById('stationsFullTable');
        // Only get headers except the first (checkbox) and last (Actions)
        const headers = Array.from(table.querySelectorAll('thead th'))
            .slice(1, -1)
            .map(th => th.textContent.trim());
        const selectedRows = [];
        table.querySelectorAll('tbody tr').forEach(tr => {
            const cb = tr.querySelector('.row-checkbox');
            if (cb && cb.checked) {
                // Only get tds except the first (checkbox) and last (Actions)
                const tds = Array.from(tr.children).slice(1, -1);
                // Use city name from data attribute for the location column
                const rowData = tds.map((td, idx) => {
                    if (idx === 2) {
                        return td.getAttribute('data-city') || td.textContent.trim();
                    }
                    return td.textContent.trim();
                });
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
            doc.text('Liste des Stations', pageWidth / 2, imgHeight + 25, { align: 'center' });
        
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
        
            doc.save('stations.pdf');
        };
        img.onerror = function() {
            alert("Logo introuvable ou erreur de chargement.");
        };
    });
});

// --- Sorting Logic ---
function sortStationsTable(compareFn) {
    const tbody = document.querySelector('#stationsFullTable tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    rows.sort(compareFn);
    rows.forEach(row => tbody.appendChild(row));
}

document.getElementById('sortByName').addEventListener('click', function(e) {
    e.preventDefault();
    sortStationsTable((a, b) => {
        const nameA = a.children[2].textContent.trim().toLowerCase();
        const nameB = b.children[2].textContent.trim().toLowerCase();
        return nameA.localeCompare(nameB);
    });
});

document.getElementById('sortByLocation').addEventListener('click', function(e) {
    e.preventDefault();
    // Localisation is in the 4th column (index 3), stored as "Gouvernorat ..."
    sortStationsTable((a, b) => {
        const locA = a.children[3].textContent.trim().toLowerCase();
        const locB = b.children[3].textContent.trim().toLowerCase();
        return locA.localeCompare(locB);
    });
});

document.getElementById('sortByStatus').addEventListener('click', function(e) {
    e.preventDefault();
    // Statut is in the 5th column (index 4)
    sortStationsTable((a, b) => {
        const statusA = a.children[4].textContent.trim().toLowerCase();
        const statusB = b.children[4].textContent.trim().toLowerCase();
        return statusA.localeCompare(statusB);
    });
});
document.getElementById('stationsTableModal').addEventListener('hidden.bs.modal', function () {
    // Remove any lingering modal-backdrop
    document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
    // Remove modal-open class from body if present
    document.body.classList.remove('modal-open');
});
// --- Search Bar Logic ---
document.getElementById('stationSearchInput').addEventListener('input', function() {
    const searchValue = this.value.trim().toLowerCase();
    const table = document.getElementById('stationsFullTable');
    const rows = table.querySelectorAll('tbody tr');
    let found = false;

    // Remove previous highlights
    rows.forEach(row => {
        row.style.backgroundColor = '';
    });

    if (searchValue === '') {
        document.getElementById('stationSearchMessage').style.display = 'none';
        return;
    }

    rows.forEach(row => {
        const name = row.children[2].textContent.trim().toLowerCase();
        const city = row.children[3].textContent.trim().toLowerCase();
        if (name.includes(searchValue) || city.includes(searchValue)) {
            row.style.backgroundColor = '#ffe082'; // Highlight color
            found = true;
        }
    });

    const messageDiv = document.getElementById('stationSearchMessage');
    if (!found) {
        messageDiv.textContent = "Aucune station trouvée pour cette recherche.";
        messageDiv.className = "alert alert-warning";
        messageDiv.style.display = 'block';
    } else {
        messageDiv.style.display = 'none';
    }
});
// --- Main Table Search Bar Logic ---
document.getElementById('mainStationSearchInput').addEventListener('input', function() {
    const searchValue = this.value.trim().toLowerCase();
    const tableBody = document.getElementById('stationsTableBody');
    const rows = tableBody.querySelectorAll('tr');
    let found = false;

    // Remove previous highlights
    rows.forEach(row => {
        row.style.backgroundColor = '';
    });

    if (searchValue === '') {
        document.getElementById('mainStationSearchMessage').style.display = 'none';
        return;
    }

    rows.forEach(row => {
        // name: 2nd column, city: 3rd column
        const name = row.children[1].textContent.trim().toLowerCase();
        const city = row.children[2].textContent.trim().toLowerCase();
        if (name.includes(searchValue) || city.includes(searchValue)) {
            row.style.backgroundColor = '#ffe082'; // Highlight color
            found = true;
        }
    });

    const messageDiv = document.getElementById('mainStationSearchMessage');
    if (!found) {
        messageDiv.textContent = "Aucune station trouvée pour cette recherche.";
        messageDiv.className = "alert alert-warning";
        messageDiv.style.display = 'block';
    } else {
        messageDiv.style.display = 'none';
    }
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const rowsPerPage = 7; // Nombre de lignes par page
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
<script>
document.addEventListener('DOMContentLoaded', function () {
    const rowsPerPageModal = 7; // Nombre de lignes par page dans le modal
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