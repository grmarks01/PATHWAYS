<?php
session_start();

// Check if user was redirected here due to expired password
if (!isset($_SESSION['password_expired']) || !isset($_SESSION['password_reset_user_id'])) {
    header("Location: login-page.php");
    exit();
}

// Get the expired password message
$expired_message = isset($_SESSION['password_expired_message']) ? $_SESSION['password_expired_message'] : "Your password has expired. Please reset it to continue.";

// Handle password reset submission
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
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
        // Include database configuration
        require_once 'components/db-config.php';
        $conn = getDBConnection();
        
        // Hash the new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Get the user's password interval to calculate new expiration
        $user_id = $_SESSION['password_reset_user_id'];
        $stmt = $conn->prepare("SELECT password_interval_days FROM users WHERE User_Id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        
        // Calculate new expiration date FROM TODAY (not from old expiration)
        if ($user['password_interval_days']) {
            $interval_days = intval($user['password_interval_days']);
            $new_expiration = date('Y-m-d H:i:s', strtotime("+$interval_days days"));
        } else {
            // If no interval is set, don't set an expiration
            $new_expiration = null;
        }
        
        // Update password, expiration date, and track when password was changed
        if ($new_expiration) {
            $stmt = $conn->prepare("UPDATE users SET Hash = ?, password_expires_at = ?, password_changed_at = NOW() WHERE User_Id = ?");
            $stmt->bind_param("ssi", $hashed_password, $new_expiration, $user_id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET Hash = ?, password_expires_at = NULL, password_changed_at = NOW() WHERE User_Id = ?");
            $stmt->bind_param("si", $hashed_password, $user_id);
        }
        
        if ($stmt->execute()) {
            $success = true;
            
            // Clear the password reset session variables
            unset($_SESSION['password_expired']);
            unset($_SESSION['password_reset_user_id']);
            unset($_SESSION['password_reset_email']);
            unset($_SESSION['password_expired_message']);
            
            // Set success message
            if ($new_expiration) {
                $_SESSION['success_message'] = "Password reset successfully! Your password will expire on " . date('M d, Y', strtotime($new_expiration)) . ". You can now login with your new password.";
            } else {
                $_SESSION['success_message'] = "Password reset successfully! You can now login with your new password.";
            }
            
            $stmt->close();
            closeDBConnection($conn);
            
            // Redirect to login page
            header("Location: login-page.php");
            exit();
        } else {
            $errors[] = "Failed to reset password. Please try again.";
        }
        
        $stmt->close();
        closeDBConnection($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Reset Password - Pathways</title>
  <meta name="description" content="">
  <meta name="keywords" content="">

  <!-- Favicons -->
  <link href="assets/img/favicon.png" rel="icon">
  <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,300;1,400;1,500;1,600;1,700;1,800&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Raleway:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
  <link href="assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">

  <!-- Main CSS File -->
  <link href="assets/css/main.css" rel="stylesheet">

  <style>
    .reset-section {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 60px 0;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    
    .reset-card {
      background: #fff;
      padding: 40px;
      border-radius: 10px;
      box-shadow: 0 0 30px rgba(0, 0, 0, 0.3);
      max-width: 500px;
      width: 100%;
    }
    
    .reset-card .icon-container {
      text-align: center;
      margin-bottom: 20px;
    }
    
    .reset-card .icon-container i {
      font-size: 64px;
      color: #dc3545;
    }
    
    .reset-card h2 {
      color: #dc3545;
      margin-bottom: 10px;
      font-weight: 700;
      text-align: center;
    }
    
    .reset-card .expired-message {
      background: #fff3cd;
      padding: 15px;
      border-radius: 5px;
      border-left: 4px solid #ffc107;
      margin-bottom: 25px;
      font-size: 14px;
      color: #856404;
    }
    
    .reset-card p {
      color: #666;
      margin-bottom: 30px;
      text-align: center;
    }
    
    .form-group {
      margin-bottom: 20px;
    }
    
    .form-group label {
      display: block;
      margin-bottom: 8px;
      color: #333;
      font-weight: 500;
    }
    
    .form-group input {
      width: 100%;
      padding: 12px 15px;
      border: 1px solid #ddd;
      border-radius: 5px;
      font-size: 15px;
      transition: border-color 0.3s;
    }
    
    .form-group input:focus {
      outline: none;
      border-color: #667eea;
    }
    
    .password-requirements {
      background: #e3f2fd;
      padding: 12px;
      border-radius: 5px;
      margin-bottom: 20px;
      font-size: 13px;
    }
    
    .password-requirements ul {
      margin: 8px 0 0 0;
      padding-left: 20px;
    }
    
    .password-requirements li {
      color: #1976d2;
      margin: 3px 0;
    }
    
    .btn-reset {
      width: 100%;
      padding: 12px;
      background: #667eea;
      color: #fff;
      border: none;
      border-radius: 5px;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.3s;
    }
    
    .btn-reset:hover {
      background: #5568d3;
    }
    
    .btn-reset:disabled {
      background: #ccc;
      cursor: not-allowed;
    }
    
    .alert {
      padding: 15px;
      margin-bottom: 20px;
      border-radius: 5px;
    }
    
    .alert-danger {
      background: #f8d7da;
      border-left: 4px solid #dc3545;
      color: #721c24;
    }
    
    .password-strength {
      margin-top: 8px;
      height: 5px;
      background: #e0e0e0;
      border-radius: 3px;
      overflow: hidden;
    }
    
    .password-strength-bar {
      height: 100%;
      transition: width 0.3s, background-color 0.3s;
      width: 0%;
    }
    
    .password-match-indicator {
      font-size: 13px;
      margin-top: 5px;
    }
    
    .match-success {
      color: #28a745;
    }
    
    .match-error {
      color: #dc3545;
    }
  </style>
</head>

<body>

  <main class="main">

    <!-- Reset Section -->
    <section class="reset-section">
      <div class="container">
        <div class="row justify-content-center">
          <div class="col-lg-6 col-md-8">
            <div class="reset-card" data-aos="fade-up">
              
              <div class="icon-container">
                <i class="bi bi-shield-lock"></i>
              </div>
              
              <h2>Password Expired</h2>
              <p>Your password must be reset to continue</p>
              
              <div class="expired-message">
                <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($expired_message); ?>
              </div>
              
              <?php if (!empty($errors)): ?>
              <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                  <p style="margin: 5px 0;"><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
              </div>
              <?php endif; ?>
              
              <form method="POST" id="resetForm">
                
                <div class="form-group">
                  <label for="email">Email Address</label>
                  <input type="email" id="email" value="<?php echo htmlspecialchars($_SESSION['password_reset_email']); ?>" readonly style="background: #f8f9fa;">
                </div>
                
                <div class="password-requirements">
                  <strong><i class="bi bi-info-circle"></i> Password Requirements:</strong>
                  <ul>
                    <li>Minimum 8 characters</li>
                    <li>Mix of uppercase and lowercase letters recommended</li>
                    <li>Include numbers and special characters for stronger security</li>
                  </ul>
                </div>
                
                <div class="form-group">
                  <label for="new_password">New Password</label>
                  <input type="password" id="new_password" name="new_password" placeholder="Enter new password" required minlength="8">
                  <div class="password-strength">
                    <div class="password-strength-bar" id="strength-bar"></div>
                  </div>
                  <small id="strength-text" style="font-size: 12px; color: #666;"></small>
                </div>
                
                <div class="form-group">
                  <label for="confirm_password">Confirm New Password</label>
                  <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password" required>
                  <div class="password-match-indicator" id="match-indicator"></div>
                </div>
                
                <button type="submit" class="btn-reset" id="submit-btn">
                  <i class="bi bi-key"></i> Reset Password & Login
                </button>
              </form>
              
            </div>
          </div>
        </div>
      </div>
    </section>

  </main>

  <!-- Vendor JS Files -->
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/aos/aos.js"></script>

  <!-- Main JS File -->
  <script src="assets/js/main.js"></script>

  <script>
    // Password strength checker
    document.getElementById('new_password').addEventListener('input', function() {
      const password = this.value;
      const strengthBar = document.getElementById('strength-bar');
      const strengthText = document.getElementById('strength-text');
      
      let strength = 0;
      
      if (password.length >= 8) strength += 25;
      if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength += 25;
      if (password.match(/[0-9]/)) strength += 25;
      if (password.match(/[^a-zA-Z0-9]/)) strength += 25;
      
      strengthBar.style.width = strength + '%';
      
      if (strength <= 25) {
        strengthBar.style.backgroundColor = '#dc3545';
        strengthText.textContent = 'Weak password';
        strengthText.style.color = '#dc3545';
      } else if (strength <= 50) {
        strengthBar.style.backgroundColor = '#ffc107';
        strengthText.textContent = 'Fair password';
        strengthText.style.color = '#ffc107';
      } else if (strength <= 75) {
        strengthBar.style.backgroundColor = '#17a2b8';
        strengthText.textContent = 'Good password';
        strengthText.style.color = '#17a2b8';
      } else {
        strengthBar.style.backgroundColor = '#28a745';
        strengthText.textContent = 'Strong password';
        strengthText.style.color = '#28a745';
      }
      
      checkPasswordMatch();
    });
    
    // Password match checker
    document.getElementById('confirm_password').addEventListener('input', checkPasswordMatch);
    
    function checkPasswordMatch() {
      const newPass = document.getElementById('new_password').value;
      const confirmPass = document.getElementById('confirm_password').value;
      const matchIndicator = document.getElementById('match-indicator');
      const submitBtn = document.getElementById('submit-btn');
      
      if (confirmPass.length === 0) {
        matchIndicator.textContent = '';
        submitBtn.disabled = false;
        return;
      }
      
      if (newPass === confirmPass) {
        matchIndicator.innerHTML = '<i class="bi bi-check-circle"></i> Passwords match';
        matchIndicator.className = 'password-match-indicator match-success';
        submitBtn.disabled = false;
      } else {
        matchIndicator.innerHTML = '<i class="bi bi-x-circle"></i> Passwords do not match';
        matchIndicator.className = 'password-match-indicator match-error';
        submitBtn.disabled = true;
      }
    }
    
    // Initialize AOS
    AOS.init({
      duration: 800,
      easing: 'ease-in-out',
      once: true
    });
  </script>

</body>
</html>