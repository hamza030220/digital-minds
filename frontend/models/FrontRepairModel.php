<?php
// frontend/models/FrontRepairModel.php
require_once '../models/FrontRepairModel.php';

class FrontRepairModel {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function getAllRepairs() {
        $query = "SELECT * FROM repairs";
        $result = $this->db->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}