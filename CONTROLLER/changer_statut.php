<?php
// CONTROLLER/changer_statut.php

// --- Dependency: Include the Database configuration ---
require_once '../CONFIG/database.php';

// --- Initialization ---
$redirect_url = '../VIEW/reclamation/index.php?error=unknown'; // Default redirect in case something unexpected happens
$reclamation_id_raw = $_POST['reclamation_id'] ?? null; // Use null coalescing for cleaner check

// --- Process Request ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check required fields
    if (isset($_POST['reclamation_id']) && isset($_POST['statut'])) {
        $reclamation_id_raw = $_POST['reclamation_id']; // Keep raw value for redirects on validation failure
        $statut_raw = $_POST['statut'];

        // --- Input Validation ---
        $reclamation_id = filter_var($reclamation_id_raw, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
        $statut = trim($statut_raw);
        // Define allowed statuses here for validation
        $allowed_statuses = ['ouverte', 'en cours', 'résolue'];

        if ($reclamation_id !== false && !empty($statut) && in_array($statut, $allowed_statuses)) {
            // Validation successful, proceed with database operation
            $pdo = null; // Initialize PDO variable
            try {
                // --- Database Connection ---
                $database = new Database();
                $pdo = $database->getConnection(); // Get PDO connection object

                if (!$pdo) {
                    throw new RuntimeException("Database connection failed.");
                }

                // --- Prepare and Execute SQL ---
                $sql = "UPDATE reclamations SET statut = :statut WHERE id = :id";
                $stmt = $pdo->prepare($sql);

                // Bind the validated values
                $stmt->bindParam(':statut', $statut, PDO::PARAM_STR);
                $stmt->bindParam(':id', $reclamation_id, PDO::PARAM_INT);

                $success = $stmt->execute();

                // --- Determine Redirect based on Success ---
                if ($success) {
                    // Updated successfully
                    $redirect_url = "../VIEW/reclamation/voir_reclamation.php?id=" . $reclamation_id . "&status_update=success";
                } else {
                    // execute() returned false (database error likely)
                    error_log("Database update failed for reclamation ID {$reclamation_id} with status '{$statut}'. PDO error info: " . implode(", ", $stmt->errorInfo()));
                    $redirect_url = "../VIEW/reclamation/voir_reclamation.php?id=" . $reclamation_id . "&status_update=error_db";
                }
            } catch (PDOException $e) {
                // Catch specific PDO exceptions during prepare/execute
                error_log("PDOException in changer_statut.php for ID {$reclamation_id}: " . $e->getMessage());
                $redirect_url = "../VIEW/reclamation/voir_reclamation.php?id=" . $reclamation_id . "&status_update=error_pdo";
            } catch (Exception $e) {
                // Catch other exceptions (like connection failure)
                error_log("General Exception in changer_statut.php for ID {$reclamation_id_raw}: " . $e->getMessage());
                $redirect_url = "../VIEW/reclamation/voir_reclamation.php?id=" . $reclamation_id_raw . "&status_update=error_system";
            } finally {
                // Close connection (optional, PDO usually handles this, but good practice for long scripts)
                $stmt = null;
                $pdo = null;
            }
        } else {
            // Input validation failed
            error_log("Invalid input for status update: ID='{$reclamation_id_raw}', Status='{$statut_raw}'");
            $redirect_url = "../VIEW/reclamation/voir_reclamation.php?id=" . $reclamation_id_raw . "&status_update=error_input";
        }
    } else {
        // Required POST fields missing
        error_log("Missing POST data in changer_statut.php request.");
        $redirect_url = $reclamation_id_raw ? "../VIEW/reclamation/voir_reclamation.php?id=" . $reclamation_id_raw . "&status_update=error_missing" : "../VIEW/reclamation/index.php?error=missing_data";
    }
} else {
    // Request method was not POST
    error_log("Invalid request method used for changer_statut.php.");
    $redirect_url = "../VIEW/reclamation/index.php?error=invalid_request";
}

// --- Final Redirect ---
header("Location: " . $redirect_url);
exit; // ALWAYS exit after a header redirect
?>