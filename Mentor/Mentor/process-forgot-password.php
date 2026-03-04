<?php
session_start();

// Include database configuration
require_once 'components/db-config.php';
$conn = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    
    // Validate email
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error_message'] = "Please enter a valid email address.";
        header("Location: forgot-password.php");
        exit();
    }
    
    // Check if email exists
    $stmt = $conn->prepare("SELECT User_Id, First_Name FROM users WHERE Email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Generate a unique token
        $token = bin2hex(random_bytes(32));
        
        // Hash the token for storage
        $token_hash = hash('sha256', $token);
        
        // Set expiration time (1 hour from now)
        $expiration = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Store the hashed token and expiration in database
        $update_stmt = $conn->prepare("UPDATE users SET reset_token_hash = ?, reset_token_expires_at = ? WHERE User_Id = ?");
        $update_stmt->bind_param("ssi", $token_hash, $expiration, $user['User_Id']);
        
        if ($update_stmt->execute()) {
            // In a real application, you would send an email here
            // For now, we'll create a reset link and show it (you'll need to send this via email)
            
            $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset-password.php?token=" . $token;
            
            // TODO: Send email with reset link
            // For development, we'll store it in session to display
            // In production, you should use PHPMailer or similar to send the email
            
            // TEMPORARY: Store link in session for development (REMOVE IN PRODUCTION)
            $_SESSION['reset_link_debug'] = $reset_link;
            
            $_SESSION['success_message'] = "Password reset instructions have been sent to your email address. Please check your inbox.";
            
            // In production, uncomment this email sending code:
            /*
            $to = $email;
            $subject = "Password Reset Request - Pathways";
            $message = "Hello " . htmlspecialchars($user['First_Name']) . ",\n\n";
            $message .= "You requested a password reset. Click the link below to reset your password:\n\n";
            $message .= $reset_link . "\n\n";
            $message .= "This link will expire in 1 hour.\n\n";
            $message .= "If you didn't request this, please ignore this email.\n\n";
            $message .= "Best regards,\nPathways Team";
            
            $headers = "From: noreply@pathways.com\r\n";
            $headers .= "Reply-To: noreply@pathways.com\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion();
            
            mail($to, $subject, $message, $headers);
            */
            
        } else {
            $_SESSION['error_message'] = "Failed to process your request. Please try again.";
        }
        
        $update_stmt->close();
    } else {
        // For security, don't reveal if email exists or not
        // Show same success message
        $_SESSION['success_message'] = "If an account exists with that email, password reset instructions have been sent.";
    }
    
    $stmt->close();
	closeDBConnection($conn);
    
    header("Location: forgot-password.php");
    exit();
} else {
    header("Location: forgot-password.php");
    exit();
}
?>