<?php
/**
 * Dashboard Controller
 * 
 * Handles dashboard functionality
 */
class DashboardController extends Controller {
    private $stationModel;
    private $trajetModel;
    
    /**
     * Constructor - initialize models
     */
    public function __construct() {
        parent::__construct();
        
        // Load models
        require_once 'models/StationModel.php';
        $this->stationModel = new StationModel();
        
        // We'll create TrajetModel later
        // require_once 'models/TrajetModel.php';
        // $this->trajetModel = new TrajetModel();
    }
    
    /**
     * Dashboard index page
     * 
     * @param array $params Query parameters
     * @return void
     */
    public function indexAction($params = []) {
        // Check if the user is logged in
        $this->requireLogin();
        
        // Get statistics
        $stationsStats = $this->stationModel->getStatistics();
        
        // For now, we'll hardcode trajet stats until we create the model
        $trajetsStats = [
            'total_trajets' => 0
        ];
        
        // Render dashboard view
        $this->render('dashboard', [
            'stationsStats' => $stationsStats,
            'trajetsStats' => $trajetsStats
        ]);
    }
    
    /**
     * Route the request to the appropriate action
     * 
     * @param string $action Action name
     * @param array $params Request parameters
     * @return void
     */
    public function route($action = 'index', $params = []) {
        switch ($action) {
            case 'index':
                $this->indexAction($params);
                break;
                
            default:
                $this->setMessage('Page not found', 'danger');
                $this->redirect('');
                break;
        }
    }
}

