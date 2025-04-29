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
                img.src = '../public/image/logobackend.png';
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