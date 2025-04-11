<?php
/**
 * Station Controller
 * 
 * Handles all station-related operations and routing
 */
class StationController extends Controller {
    /**
     * Station model instance
     * @var StationModel
     */
    private $model;
    
    /**
     * Constructor - initialize model and settings
     */
    public function __construct() {
        // Call parent constructor
        parent::__construct();
        
        // Initialize the station model
        require_once 'models/StationModel.php';
        $this->model = new StationModel();
    }

    /**
     * Display the station list page
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
        
        // Get stations with pagination from model
        $result = $this->model->getAll($page, $itemsPerPage);
        
        // Check for errors
        if (isset($result['error'])) {
            $this->setMessage('Error: ' . $result['error'], 'danger');
        }
        // Render the view
        $this->render('stations/list', ['result' => $result]);
    }
    
    /**
     * Display the add station form
     *
     * @return void
     */
    public function addFormAction() {
        // Check if the user is logged in
        $this->requireLogin();
        
        // Render the view
        $this->render('stations/add');
    }

    /**
     * Process the station creation form
     *
     * @param array $data POST data
     * @return void
     */
    public function createAction($data) {
        // Check if the user is logged in
        $this->requireLogin();
        
        // Sanitize input data
        $data = $this->sanitize($data);
        
        // Validate form data
        if (!$this->validateRequired($data, ['name', 'location'])) {
            $this->setMessage('Error: ' . $this->getError(), 'danger');
            $_SESSION['form_data'] = $data; // Store form data for re-displaying
            $this->redirect('stations/add');
        }
        
        // Set default status if not provided
        if (!isset($data['status'])) {
            $data['status'] = 'active';
        }
        
        // Create station using the model
        $stationId = $this->model->create([
            'name' => $data['name'],
            'location' => $data['location'],
            'status' => $data['status']
        ]);
        
        // Handle success or failure
        if ($stationId) {
            $this->setMessage('Station created successfully', 'success');
            $this->redirect('stations');
        } else {
            $this->setMessage('Error: ' . $this->model->getError(), 'danger');
            $_SESSION['form_data'] = $data; // Store form data for re-displaying
            $this->redirect('stations/add');
        }
    }
    
    /**
     * Display the edit station form
     *
     * @param array $params URL parameters
     * @return void
     */
    public function editFormAction($params) {
        // Check if the user is logged in
        $this->requireLogin();
        
        // Validate ID parameter
        if (!isset($params['id']) || !is_numeric($params['id'])) {
            $this->setMessage('Error: Invalid station ID', 'danger');
            $this->redirect('stations');
        }
        
        // Get station data from model
        $station = $this->model->getById($params['id']);
        
        // Check if station exists
        if (!$station) {
            $this->setMessage('Error: ' . $this->model->getError(), 'danger');
            $this->redirect('stations');
        }
        
        // Render the view
        $this->render('stations/edit', ['station' => $station]);
    }

    /**
     * Process the station update form
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
            $this->setMessage('Error: Invalid station ID', 'danger');
            $this->redirect('stations');
        }
        
        // Sanitize input data
        $data = $this->sanitize($data);
        
        // Validate form data
        if (!$this->validateRequired($data, ['name', 'location'])) {
            $this->setMessage('Error: ' . $this->getError(), 'danger');
            $this->redirect('stations/edit?id=' . $params['id']);
        }
        
        // Update station using the model
        $success = $this->model->update($params['id'], [
            'name' => $data['name'],
            'location' => $data['location'],
            'status' => $data['status'] ?? 'active'
        ]);
        
        // Handle success or failure
        if ($success) {
            $this->setMessage('Station updated successfully', 'success');
            $this->redirect('stations');
        } else {
            $this->setMessage('Error: ' . $this->model->getError(), 'danger');
            $this->redirect('stations/edit?id=' . $params['id']);
        }
    }

    /**
     * Confirm station deletion
     *
     * @param array $params URL parameters
     * @return void
     */
    public function deleteConfirmAction($params) {
        // Check if the user is logged in
        $this->requireLogin();
        
        // Validate ID parameter
        if (!isset($params['id']) || !is_numeric($params['id'])) {
            $this->setMessage('Error: Invalid station ID', 'danger');
            $this->redirect('stations');
        }
        
        // Get station data from model
        $station = $this->model->getById($params['id']);
        
        // Check if station exists
        if (!$station) {
            $this->setMessage('Error: ' . $this->model->getError(), 'danger');
            $this->redirect('stations');
        }
        
        // Render the confirmation view
        $this->render('stations/delete', ['station' => $station]);
    }

    /**
     * Process station deletion
     *
     * @param array $params URL parameters
     * @return void
     */
    public function deleteAction($params) {
        // Check if the user is logged in
        $this->requireLogin();
        
        // Validate ID parameter
        if (!isset($params['id']) || !is_numeric($params['id'])) {
            $this->setMessage('Error: Invalid station ID', 'danger');
            $this->redirect('stations');
        }
        
        // Delete station using the model
        $success = $this->model->delete($params['id']);
        
        // Handle success or failure
        if ($success) {
            $this->setMessage('Station deleted successfully', 'success');
        } else {
            $this->setMessage('Error: ' . $this->model->getError(), 'danger');
        }
        
        $this->redirect('stations');
    }

    /**
     * Toggle station status (active/inactive)
     *
     * @param array $params URL parameters
     * @return void
     */
    public function toggleStatusAction($params) {
        // Check if the user is logged in
        $this->requireLogin();
        
        // Validate ID parameter
        if (!isset($params['id']) || !is_numeric($params['id'])) {
            $this->setMessage('Error: Invalid station ID', 'danger');
            $this->redirect('stations');
        }
        
        // Toggle status using the model
        $success = $this->model->toggleStatus($params['id']);
        
        // Handle success or failure
        if ($success) {
            $this->setMessage('Station status updated successfully', 'success');
        } else {
            $this->setMessage('Error: ' . $this->model->getError(), 'danger');
        }
        
        $this->redirect('stations');
    }

    /**
     * Handle station dashboard statistics
     *
     * @return array Station statistics
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
                
            case 'toggle-status':
                $this->toggleStatusAction($params);
                break;
                
            default:
                // Handle 404 or redirect to list
                $this->setMessage('Page not found', 'danger');
                $this->redirect('stations');
        }
    }
}

