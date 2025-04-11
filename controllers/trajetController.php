<?php
/**
 * Trajet Controller
 * 
 * Handles all trajet (route) related operations and routing
 */
class TrajetController extends Controller {
    /**
     * Trajet model instance
     * @var TrajetModel
     */
    private $model;
    
    /**
     * Station model instance (for fetching stations)
     * @var StationModel
     */
    private $stationModel;
    
    /**
     * Constructor - initialize models and settings
     */
    public function __construct() {
        // Call parent constructor
        parent::__construct();
        
        // Initialize the trajet model (to be created)
        require_once 'models/TrajetModel.php';
        $this->model = new TrajetModel();
        
        // Initialize station model for station selection
        require_once 'models/StationModel.php';
        $this->stationModel = new StationModel();
    }
    
    /**
     * Display the trajet list page
     *
     * @param array $params Query parameters
     * @return void
     */
    public function listAction($params = []) {
        // Check if the user is logged in
        $this->requireLogin();
        
        // Get page parameter for pagination
        $page = isset($params['page']) ? (int)$params['page'] : 1;
        $itemsPerPage = 10;
        
        // Get trajets with pagination from model
        $result = $this->model->getAll($page, $itemsPerPage);
        
        // Check for errors
        if (isset($result['error'])) {
            $this->setMessage('Error: ' . $result['error'], 'danger');
        }
        
        // Render the view
        $this->render('trajets/list', ['result' => $result]);
    }
    
    /**
     * Display the add trajet form
     *
     * @return void
     */
    public function addFormAction() {
        // Check if the user is logged in
        $this->requireLogin();
        
        // Get all active stations for the form
        $stations = $this->stationModel->getAll()['stations'];
        
        // Filter active stations only
        $activeStations = [];
        foreach ($stations as $station) {
            if ($station['status'] === 'active') {
                $activeStations[] = $station;
            }
        }
        
        // Render the view
        $this->render('trajets/add', ['stations' => $activeStations]);
    }
    
    /**
     * Process the trajet creation form
     *
     * @param array $data POST data
     * @return void
     */
    public function createAction($data) {
        // Check if the user is logged in
        $this->requireLogin();
        
        // Sanitize input data
        $data = $this->sanitize($data);
        
        // Validate required fields
        $requiredFields = ['start_station_id', 'end_station_id', 'distance', 'description'];
        if (!$this->validateRequired($data, $requiredFields)) {
            $this->setMessage('Error: ' . $this->getError(), 'danger');
            $_SESSION['form_data'] = $data; // Store form data for re-displaying
            $this->redirect('trajets/add');
        }
        
        // Validate start and end stations are different
        if ($data['start_station_id'] === $data['end_station_id']) {
            $this->setMessage('Error: Start and end stations cannot be the same', 'danger');
            $_SESSION['form_data'] = $data;
            $this->redirect('trajets/add');
        }
        
        // Validate distance is numeric and positive
        if (!is_numeric($data['distance']) || $data['distance'] <= 0) {
            $this->setMessage('Error: Distance must be a positive number', 'danger');
            $_SESSION['form_data'] = $data;
            $this->redirect('trajets/add');
        }
        
        // Validate route coordinates if provided
        if (!empty($data['route_coordinates'])) {
            // Try to decode JSON to validate it
            $coordinates = json_decode($data['route_coordinates'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->setMessage('Error: Invalid route coordinates format', 'danger');
                $_SESSION['form_data'] = $data;
                $this->redirect('trajets/add');
            }
        }
        
        // Create trajet using the model
        $trajetId = $this->model->create([
            'start_station_id' => $data['start_station_id'],
            'end_station_id' => $data['end_station_id'],
            'distance' => $data['distance'],
            'description' => $data['description'],
            'route_coordinates' => $data['route_coordinates'] ?? null,
            'route_description' => $data['route_description'] ?? null
        ]);
        
        // Handle success or failure
        if ($trajetId) {
            $this->setMessage('Trajet created successfully', 'success');
            $this->redirect('trajets');
        } else {
            $this->setMessage('Error: ' . $this->model->getError(), 'danger');
            $_SESSION['form_data'] = $data; // Store form data for re-displaying
            $this->redirect('trajets/add');
        }
    }
    
    /**
     * Display the edit trajet form
     *
     * @param array $params URL parameters
     * @return void
     */
    public function editFormAction($params) {
        // Check if the user is logged in
        $this->requireLogin();
        
        // Validate ID parameter
        if (!isset($params['id']) || !is_numeric($params['id'])) {
            $this->setMessage('Error: Invalid trajet ID', 'danger');
            $this->redirect('trajets');
        }
        
        // Get trajet data from model
        $trajet = $this->model->getById($params['id']);
        
        // Check if trajet exists
        if (!$trajet) {
            $this->setMessage('Error: ' . $this->model->getError(), 'danger');
            $this->redirect('trajets');
        }
        
        // Get all active stations for the form
        $stations = $this->stationModel->getAll()['stations'];
        
        // Filter active stations only
        $activeStations = [];
        foreach ($stations as $station) {
            if ($station['status'] === 'active') {
                $activeStations[] = $station;
            }
        }
        
        // Render the view
        $this->render('trajets/edit', [
            'trajet' => $trajet,
            'stations' => $activeStations
        ]);
    }
    
    /**
     * Process the trajet update form
     *
     * @param array $data POST data
     * @param array $params URL parameters
     * @return void
     */
    public function updateAction($data, $params) {
        // Check if the user is logged in
        $this->requireLogin();
        
        // Validate ID parameter
        if (!isset($params['id']) || !is_numeric($params['id'])) {
            $this->setMessage('Error: Invalid trajet ID', 'danger');
            $this->redirect('trajets');
        }
        
        // Sanitize input data
        $data = $this->sanitize($data);
        
        // Validate required fields
        $requiredFields = ['start_station_id', 'end_station_id', 'distance', 'description'];
        if (!$this->validateRequired($data, $requiredFields)) {
            $this->setMessage('Error: ' . $this->getError(), 'danger');
            $this->redirect('trajets/edit?id=' . $params['id']);
        }
        
        // Validate start and end stations are different
        if ($data['start_station_id'] === $data['end_station_id']) {
            $this->setMessage('Error: Start and end stations cannot be the same', 'danger');
            $this->redirect('trajets/edit?id=' . $params['id']);
        }
        
        // Validate distance is numeric and positive
        if (!is_numeric($data['distance']) || $data['distance'] <= 0) {
            $this->setMessage('Error: Distance must be a positive number', 'danger');
            $this->redirect('trajets/edit?id=' . $params['id']);
        }
        
        // Validate route coordinates if provided
        if (!empty($data['route_coordinates'])) {
            // Try to decode JSON to validate it
            $coordinates = json_decode($data['route_coordinates'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->setMessage('Error: Invalid route coordinates format', 'danger');
                $this->redirect('trajets/edit?id=' . $params['id']);
            }
        }
        
        // Update trajet using the model
        $success = $this->model->update($params['id'], [
            'start_station_id' => $data['start_station_id'],
            'end_station_id' => $data['end_station_id'],
            'distance' => $data['distance'],
            'description' => $data['description'],
            'route_coordinates' => $data['route_coordinates'] ?? null,
            'route_description' => $data['route_description'] ?? null
        ]);
        
        // Handle success or failure
        if ($success) {
            $this->setMessage('Trajet updated successfully', 'success');
            $this->redirect('trajets');
        } else {
            $this->setMessage('Error: ' . $this->model->getError(), 'danger');
            $this->redirect('trajets/edit?id=' . $params['id']);
        }
    }
    
    /**
     * Confirm trajet deletion
     *
     * @param array $params URL parameters
     * @return void
     */
    public function deleteConfirmAction($params) {
        // Check if the user is logged in
        $this->requireLogin();
        
        // Validate ID parameter
        if (!isset($params['id']) || !is_numeric($params['id'])) {
            $this->setMessage('Error: Invalid trajet ID', 'danger');
            $this->redirect('trajets');
        }
        
        // Get trajet data from model
        $trajet = $this->model->getById($params['id']);
        
        // Check if trajet exists
        if (!$trajet) {
            $this->setMessage('Error: ' . $this->model->getError(), 'danger');
            $this->redirect('trajets');
        }
        
        // Render the confirmation view
        $this->render('trajets/delete', ['trajet' => $trajet]);
    }
    
    /**
     * Process trajet deletion
     *
     * @param array $params URL parameters
     * @return void
     */
    public function deleteAction($params) {
        // Check if the user is logged in
        $this->requireLogin();
        
        // Validate ID parameter
        if (!isset($params['id']) || !is_numeric($params['id'])) {
            $this->setMessage('Error: Invalid trajet ID', 'danger');
            $this->redirect('trajets');
        }
        
        // Delete trajet using the model
        $success = $this->model->delete($params['id']);
        
        // Handle success or failure
        if ($success) {
            $this->setMessage('Trajet deleted successfully', 'success');
        } else {
            $this->setMessage('Error: ' . $this->model->getError(), 'danger');
        }
        
        $this->redirect('trajets');
    }
    
    /**
     * Handle trajet statistics for dashboard
     * 
     * @return array Trajet statistics
     */
    public function getStatistics() {
        return $this->model->getStatistics();
    }
    
    /**
     * Display the dashboard view
     * 
     * @return void
     */
    public function indexAction() {
        // Alias for listAction with default params
        $this->listAction();
    }
    
    /**
     * Route the request to the appropriate action
     *
     * @param string $action Action name
     * @param array $params Request parameters
     * @return void
     */
    public function route($action = 'list', $params = []) {
        switch ($action) {
            case 'list':
                $this->listAction($params);
                break;
                
            case 'add':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $this->createAction($_POST);
                } else {
                    $this->addFormAction();
                }
                break;
                
            case 'edit':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $this->updateAction($_POST, $params);
                } else {
                    $this->editFormAction($params);
                }
                break;
                
            case 'delete':
                if (isset($params['confirm']) && $params['confirm'] === 'yes') {
                    $this->deleteAction($params);
                } else {
                    $this->deleteConfirmAction($params);
                }
                break;
                
            default:
                // Handle 404 or redirect to list
                $this->setMessage('Page not found', 'danger');
                $this->redirect('trajets');
        }
    }
}

