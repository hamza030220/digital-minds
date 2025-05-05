<!-- modal_trajets.php -->
<div class="modal fade" id="trajetStatsModal" tabindex="-1" aria-labelledby="trajetStatsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="trajetStatsModalLabel">Statistiques des trajets</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
      </div>
      <div class="modal-body">
        <div class="chart-container" style="height: 400px;">
          <canvas id="co2Chart"></canvas>
        </div>
        <div class="chart-container mt-4" style="height: 400px;">
          <canvas id="energyChart"></canvas>
        </div>
        <div class="chart-container mt-4" style="height: 400px;">
          <canvas id="distanceChart"></canvas>
        </div>
      </div>
    </div>
  </div>
</div>
