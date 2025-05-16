<!-- modal_trajets.php -->
<div class="modal fade" id="trajetsStatsModal" tabindex="-1" aria-labelledby="trajetsStatsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="trajetsStatsModalLabel">Statistiques des trajets</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
      </div>
      <div class="modal-body">
        <div id="trajet-slide-0" class="chart-container" style="height: 400px;">
          <canvas id="trajetCo2Chart"></canvas>
        </div>
        <div id="trajet-slide-1" class="chart-container" style="height: 400px; display: none;">
          <canvas id="trajetEnergyChart"></canvas>
        </div>
        <div id="trajet-slide-2" class="chart-container" style="height: 400px; display: none;">
          <canvas id="trajetDistanceChart"></canvas>
        </div>
        <div class="modal-footer">
          <button id="trajetSlideLeft" class="btn btn-secondary" style="display: none;">
            <i class="bi bi-chevron-left"></i> Précédent
          </button>
          <button id="trajetSlideRight" class="btn btn-secondary">
            Suivant <i class="bi bi-chevron-right"></i>
          </button>
        </div>
      </div>
    </div>
  </div>
</div>
