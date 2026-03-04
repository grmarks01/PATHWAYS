<?php
session_start();

// Include database configuration
require_once 'components/db-config.php';
$conn = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Check if we have the user ID in session
    if (!isset($_SESSION['reset_user_id']) || !isset($_SESSION['reset_token'])) {
        $_SESSION['error_message'] = "Invalid reset session. Please start over.";
        header("Location: forgot-password.php");
        exit();
    }
    
    $user_id = $_SESSION['reset_user_id'];
    $token = $_SESSION['reset_token'];
    $token_hash = hash('sha256', $token);
    
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    $errors = [];
    
    // Validate passwords
    if (empty($new_password)) {
        $errors[] = "New password is required";
    } elseif (strlen($new_password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }
    
    if ($new_password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    if (empty($errors)) {
        // Verify token is still valid
        $stmt = $conn->prepare("SELECT User_Id, password_interval_days FROM users WHERE User_Id = ? AND reset_token_hash = ? AND reset_token_expires_at > NOW()");
        $stmt->bind_param("is", $user_id, $token_hash);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Hash the new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Calculate new password expiration FROM TODAY if user has a password policy
            $password_expires_at = null;
            if ($user['password_interval_days']) {
                $interval_days = intval($user['password_interval_days']);
                // FIXED: Calculate from TODAY, not from old expiration
                $password_expires_at = date('Y-m-d H:i:s', strtotime("+$interval_days days"));
            }
            
            // Update password, clear reset token, and update password_changed_at
            if ($password_expires_at) {
                $update_stmt = $conn->prepare("UPDATE users SET Hash = ?, reset_token_hash = NULL, reset_token_expires_at = NULL, password_expires_at = ?, password_changed_at = NOW() WHERE User_Id = ?");
                $update_stmt->bind_param("ssi", $hashed_password, $password_expires_at, $user_id);
            } else {
                $update_stmt = $conn->prepare("UPDATE users SET Hash = ?, reset_token_hash = NULL, reset_token_expires_at = NULL, password_changed_at = NOW() WHERE User_Id = ?");
                $update_stmt->bind_param("si", $hashed_password, $user_id);
            }
            
            if ($update_stmt->execute()) {
                // Clear reset session variables
                unset($_SESSION['reset_user_id']);
                unset($_SESSION['reset_token']);
                
                // Set success message with expiration info
                if ($password_expires_at) {
                    $_SESSION['success_message'] = "Your password has been reset successfully! Your password will expire on " . date('M d, Y', strtotime($password_expires_at)) . ". You can now login with your new password.";
                } else {
                    $_SESSION['success_message'] = "Your password has been reset successfully! You can now login with your new password.";
                }
                
                $update_stmt->close();
                $stmt->close();
                
                header("Location: login-page.php");
                exit();
            } else {
                $errors[] = "Failed to reset password. Please try again.";
            }
            
            $update_stmt->close();
        } else {
            $errors[] = "Invalid or expired reset link. Please request a new password reset.";
        }
        
        $stmt->close();
    }
    
    // If there are errors
    if (!empty($errors)) {
        $_SESSION['error_message'] = implode(', ', $errors);
        header("Location: reset-password.php?token=" . $token);
        exit();
    }
    
} else {
    header("Location: forgot-password.php");
    exit();
}

closeDBConnection($conn);
?>