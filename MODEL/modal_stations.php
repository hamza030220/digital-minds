<!-- modal_stations.php -->
<div class="modal fade" id="statModal" tabindex="-1" aria-labelledby="statModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="statModalLabel">Statistiques des stations</h5>
        <button type="button" class="btn-close" id="closeStatModal" aria-label="Fermer"></button>
      </div>
      <div class="modal-body">
        <div id="slide-0" class="chart-container" style="height: 400px;">
          <canvas id="pieChart"></canvas>
        </div>
        <div id="slide-1" class="chart-container" style="height: 400px; display: none;">
          <canvas id="barChart"></canvas>
        </div>
        <div class="modal-footer">
          <button id="slideLeft" class="btn btn-secondary" style="display: none;">
            <i class="bi bi-chevron-left"></i> Précédent
          </button>
          <button id="slideRight" class="btn btn-secondary">
            Suivant <i class="bi bi-chevron-right"></i>
          </button>
        </div>
      </div>
    </div>
  </div>
</div>
