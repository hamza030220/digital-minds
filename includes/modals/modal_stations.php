<div class="modal fade" id="stationsStatsModal" tabindex="-1" aria-labelledby="stationsStatsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="stationsStatsModalLabel">Statistiques des Stations</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Répartition par Statut</h6>
                        <canvas id="statusChart"></canvas>
                    </div>
                    <div class="col-md-6">
                        <h6>Stations par Ville</h6>
                        <canvas id="locationChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>