<?php
session_start();

// Get any messages from session
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Forgot Password - Pathways</title>

  <!-- Favicons -->
  <link href="assets/img/favicon.png" rel="icon">
  <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&family=Raleway:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&display=swap" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">

  <!-- Main CSS File -->
  <link href="assets/css/main.css" rel="stylesheet">

  <style>
    .forgot-password-section {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 60px 0;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    
    .forgot-password-card {
      background: #fff;
      padding: 40px;
      border-radius: 10px;
      box-shadow: 0 0 30px rgba(0, 0, 0, 0.3);
      max-width: 500px;
      width: 100%;
    }
    
    .icon-container {
      text-align: center;
      margin-bottom: 20px;
    }
    
    .icon-container i {
      font-size: 64px;
      color: #667eea;
    }
    
    .forgot-password-card h2 {
      color: #333;
      margin-bottom: 10px;
      font-weight: 700;
      text-align: center;
    }
    
    .forgot-password-card p {
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
    
    .btn-submit {
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
    
    .btn-submit:hover {
      background: #5568d3;
    }
    
    .back-to-login {
      text-align: center;
      margin-top: 20px;
      color: #666;
      font-size: 14px;
    }
    
    .back-to-login a {
      color: #667eea;
      text-decoration: none;
      font-weight: 600;
    }
    
    .back-to-login a:hover {
      text-decoration: underline;
    }
    
    .alert {
      padding: 15px;
      margin-bottom: 20px;
      border-radius: 5px;
    }
    
    .alert-success {
      background: #d4edda;
      border-left: 4px solid #28a745;
      color: #155724;
    }
    
    .alert-danger {
      background: #f8d7da;
      border-left: 4px solid #dc3545;
      color: #721c24;
    }
    
    .info-box {
      background: #e3f2fd;
      padding: 15px;
      border-radius: 5px;
      border-left: 4px solid #2196f3;
      margin-bottom: 20px;
      font-size: 14px;
    }
  </style>
</head>

<body>

  <main class="main">
    <section class="forgot-password-section">
      <div class="container">
        <div class="row justify-content-center">
          <div class="col-lg-6 col-md-8">
            <div class="forgot-password-card" data-aos="fade-up">
              
              <div class="icon-container">
                <i class="bi bi-key"></i>
              </div>
              
              <h2>Forgot Password?</h2>
              <p>No worries! Enter your email address and we'll send you a link to reset your password.</p>
              
              <?php if ($success_message): ?>
              <div class="alert alert-success">
                <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
              </div>
              <?php endif; ?>
              
              <?php if ($error_message): ?>
              <div class="alert alert-danger">
                <i class="bi bi-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
              </div>
              <?php endif; ?>
              
              <div class="info-box">
                <i class="bi bi-info-circle"></i> <strong>Note:</strong> The password reset link will be valid for 1 hour.
              </div>
              
              <form action="process-forgot-password.php" method="POST">
                <div class="form-group">
                  <label for="email">Email Address</label>
                  <input type="email" id="email" name="email" placeholder="Enter your registered email" required>
                </div>
                
                <button type="submit" class="btn-submit">
                  <i class="bi bi-envelope"></i> Send Reset Link
                </button>
              </form>
              
              <div class="back-to-login">
                <i class="bi bi-arrow-left"></i> <a href="login-page.php">Back to Login</a>
              </div>
              
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
    AOS.init({
      duration: 800,
      easing: 'ease-in-out',
      once: true
    });
  </script>

</body>
</html>