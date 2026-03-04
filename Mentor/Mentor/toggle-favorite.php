<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in']);
    exit();
}

// Include database configuration
require_once 'components/db-config.php';
$conn = getDBConnection();

// Get POST data
$opportunity_id = isset($_POST['opportunity_id']) ? (int)$_POST['opportunity_id'] : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';
$user_id = $_SESSION['user_id'];

// Validate inputs
if ($opportunity_id <= 0 || !in_array($action, ['add', 'remove'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

// Verify opportunity exists
$check_stmt = $conn->prepare("SELECT id FROM pathways_opportunities WHERE id = ?");
$check_stmt->bind_param("i", $opportunity_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Opportunity not found']);
    $check_stmt->close();
    $conn->close();
    exit();
}
$check_stmt->close();

if ($action === 'add') {
    // Add to favorites
    $stmt = $conn->prepare("INSERT IGNORE INTO saved_opportunities (user_id, opportunity_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $user_id, $opportunity_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Added to favorites']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add favorite']);
    }
    $stmt->close();
    
} elseif ($action === 'remove') {
    // Remove from favorites
    $stmt = $conn->prepare("DELETE FROM saved_opportunities WHERE user_id = ? AND opportunity_id = ?");
    $stmt->bind_param("ii", $user_id, $opportunity_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Removed from favorites']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to remove favorite']);
    }
    $stmt->close();
}

closeDBConnection($conn)
?>