<?php
session_start();

// Include database configuration
require_once '../components/db-config.php';
$conn = getDBConnection();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $inquiry_type = $_POST['inquiry_type'] ?? 'general';
    $message = trim($_POST['message'] ?? '');
    
    // Optional fields for opportunity suggestions
    $opportunity_name = trim($_POST['opportunity_name'] ?? '');
    $opportunity_state = trim($_POST['opportunity_state'] ?? '');
    $opportunity_category = trim($_POST['opportunity_category'] ?? '');
    $opportunity_website = trim($_POST['opportunity_website'] ?? '');
    
    // Validate required fields
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        header("Location: ../contact.php?error=" . urlencode('Please fill in all required fields.'));
        exit();
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: ../contact.php?error=" . urlencode('Please enter a valid email address.'));
        exit();
    }
    
    // Prepare and execute insert statement
    $stmt = $conn->prepare("INSERT INTO contact_submissions (name, email, inquiry_type, subject, message, opportunity_name, opportunity_state, opportunity_category, opportunity_website) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    if ($stmt === false) {
        header("Location: ../contact.php?error=" . urlencode('Database error. Please try again later.'));
        exit();
    }
    
    $stmt->bind_param("sssssssss", 
        $name, 
        $email, 
        $inquiry_type, 
        $subject, 
        $message, 
        $opportunity_name, 
        $opportunity_state, 
        $opportunity_category, 
        $opportunity_website
    );
    
    if ($stmt->execute()) {
        header("Location: ../contact.php?success=1");
        exit();
    } else {
        header("Location: ../contact.php?error=" . urlencode('There was an error submitting your message. Please try again.'));
        exit();
    }
    
    $stmt->close();
    $conn->close();
} else {
    // If not POST request, redirect back to contact page
    header("Location: ../contact.php");
    exit();
}
?>