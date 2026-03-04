<?php
// Start session
session_start();

// Include database configuration
require_once 'components/db-config.php';
$conn = getDBConnection();

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Get and sanitize form data
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);
    
    // Initialize error array
    $errors = [];
    
    // Validate inputs
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    }
    
    // If no validation errors, check credentials
    if (empty($errors)) {
        
        // Prepare SQL statement to get user - include password expiration fields
        $stmt = $conn->prepare("SELECT User_Id, Email, Hash, Role, First_Name, Last_Name, Phone, Active, password_interval_days, password_expires_at FROM users WHERE Email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['Hash'])) {
                
                // Check if user is a teacher and not approved (Active is NULL means no admin privileges)
                if ($user['Role'] === 'teacher' && $user['Active'] === NULL) {
                    $errors[] = "Your teacher account is pending approval from an administrator. You will be able to login once approved.";
                } else {
                    // Check if password has expired
                    $password_expired = false;
                    if ($user['password_expires_at'] !== NULL) {
                        $expiration_time = strtotime($user['password_expires_at']);
                        $current_time = time();
                        
                        if ($current_time >= $expiration_time) {
                            $password_expired = true;
                        }
                    }
                    
                    // If password is expired, redirect to password reset page
                    if ($password_expired) {
                        // Store user ID in session for password reset
                        $_SESSION['password_reset_user_id'] = $user['User_Id'];
                        $_SESSION['password_reset_email'] = $user['Email'];
                        $_SESSION['password_expired'] = true;
                        
                        // Calculate how many days overdue
                        $days_overdue = floor(($current_time - $expiration_time) / 86400);
                        
                        $_SESSION['password_expired_message'] = "Your password expired " . ($days_overdue > 0 ? $days_overdue . " days ago" : "today") . ". You must reset your password before you can login.";
                        
                        header("Location: force-password-reset.php");
                        exit();
                    }
                    
                    // Password is correct, user is approved, and password not expired - create session
                    $_SESSION['user_id'] = $user['User_Id'];
                    $_SESSION['user_email'] = $user['Email'];
                    $_SESSION['user_role'] = $user['Role'];
                    $_SESSION['user_first_name'] = $user['First_Name'];
                    $_SESSION['user_last_name'] = $user['Last_Name'];
                    $_SESSION['user_phone'] = $user['Phone'];
                    // Active: NULL = no admin, '1' = has admin privileges
                    $_SESSION['user_active'] = $user['Active'];
                    $_SESSION['logged_in'] = true;
                    
                    // Store password expiration info for warning messages
                    if ($user['password_expires_at'] !== NULL) {
                        $_SESSION['password_expires_at'] = $user['password_expires_at'];
                        
                        // Calculate days until expiration
                        $days_until_expiration = floor((strtotime($user['password_expires_at']) - time()) / 86400);
                        
                        // Show warning if expiring within 7 days
                        if ($days_until_expiration > 0 && $days_until_expiration <= 7) {
                            $_SESSION['password_expiring_soon'] = true;
                            $_SESSION['password_days_remaining'] = $days_until_expiration;
                        }
                    }
                    
                    // Handle "Remember Me"
                    if ($remember) {
                        // Set cookie for 30 days
                        setcookie('user_email', $email, time() + (86400 * 30), "/");
                    }
                    
                    // Redirect to profile page
                    header("Location: my-profile.php");
                    exit();
                }
                
            } else {
                $errors[] = "Invalid email or password";
            }
        } else {
            $errors[] = "Invalid email or password";
        }
        
        $stmt->close();
    }
    
    // If there are errors, store them in session and redirect back
    if (!empty($errors)) {
        $_SESSION['login_errors'] = $errors;
        $_SESSION['login_email'] = $email; // Save email to repopulate
        header("Location: login-page.php");
        exit();
    }
}

closeDBConnection($conn);
?>