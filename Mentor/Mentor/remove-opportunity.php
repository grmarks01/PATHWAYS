<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login-page.php");
    exit();
}

// Include database configuration
require_once 'components/db-config.php';
$conn = getDBConnection();

// Get opportunity ID from URL
$opportunity_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = $_SESSION['user_id'];

if ($opportunity_id > 0) {
    // Remove from saved opportunities
    $stmt = $conn->prepare("DELETE FROM saved_opportunities WHERE user_id = ? AND opportunity_id = ?");
    $stmt->bind_param("ii", $user_id, $opportunity_id);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Opportunity removed successfully";
    } else {
        $_SESSION['error_message'] = "Failed to remove opportunity";
    }
    
    $stmt->close();
}

closeDBConnection($conn);

// Redirect back to profile
header("Location: my-profile.php");
exit();
?>