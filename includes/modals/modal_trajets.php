<div class="modal fade" id="trajetsStatsModal" tabindex="-1" aria-labelledby="trajetsStatsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="trajetsStatsModalLabel">Statistiques des Trajets</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-12 mb-4">
                        <h6>CO₂ Économisé par Trajet</h6>
                        <canvas id="co2Chart"></canvas>
                    </div>
                    <div class="col-12 mb-4">
                        <h6>Énergie et Carburant par Trajet</h6>
                        <canvas id="energyChart"></canvas>
                    </div>
                    <div class="col-12">
                        <h6>Distance des Trajets</h6>
                        <canvas id="distanceChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>