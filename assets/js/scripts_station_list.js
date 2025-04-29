document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('showStationsTableModalBtn').addEventListener('click', function() {
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
        const headers = Array.from(table.querySelectorAll('thead th'))
            .slice(1, -1)
            .map(th => th.textContent.trim());
        const selectedRows = [];
        table.querySelectorAll('tbody tr').forEach(tr => {
            const cb = tr.querySelector('.row-checkbox');
            if (cb && cb.checked) {
                const tds = Array.from(tr.children).slice(1, -1);
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
        const img = new Image();
        img.src = '../public/image/logobackend.png';
        img.onload = function() {
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
            doc.text('Exporté le : ' + new Date().toLocaleDateString(), pageWidth / 2, imgHeight + 33, { align: 'center' });
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
            const pageHeight = doc.internal.pageSize.getHeight();
            doc.setDrawColor(96,186,151);
            doc.setLineWidth(0.5);
            doc.line(10, pageHeight - 35, pageWidth - 10, pageHeight - 35);
            doc.setFontSize(12);
            doc.text("Signature:", pageWidth - 60, pageHeight - 25);
            doc.text("Date: " + new Date().toLocaleDateString(), pageWidth - 60, pageHeight - 15);
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
