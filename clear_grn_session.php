<?php
session_start();
header('Content-Type: application/json');

// Clear the current GRN session
if (isset($_SESSION['current_grn'])) {
    unset($_SESSION['current_grn']);
    echo json_encode(['status' => 'success', 'message' => 'GRN cancelled']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'No active GRN']);
}
?>