<?php
// frontend/controllers/FrontRepairController.php
require_once '../models/FrontRepairModel.php';

class FrontRepairController {
    private $repairModel;

    public function __construct() {
        $this->repairModel = new FrontRepairModel();
    }

    public function index() {
        // Fetch repairs from model
        $repairs = $this->repairModel->getAllRepairs();
        // Pass data to view
        require_once '../views/repairs.php';
    }
}