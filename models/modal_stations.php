<!-- modal_stations.php -->
<div class="modal fade" id="stationStatsModal" tabindex="-1" aria-labelledby="stationStatsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="stationStatsModalLabel">Statistiques des stations</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
      </div>
      <div class="modal-body">
        <div class="chart-container" style="height: 400px;">
          <canvas id="statusChart"></canvas>
        </div>
        <div class="chart-container mt-4" style="height: 400px;">
          <canvas id="cityChart"></canvas>
        </div>
      </div>
    </div>
  </div>
</div>
