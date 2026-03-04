<?php
session_start();

// Get errors if any
$errors = [];
if (isset($_SESSION['login_errors'])) {
    $errors = $_SESSION['login_errors'];
    unset($_SESSION['login_errors']);
}

// Get saved email
$saved_email = '';
if (isset($_SESSION['login_email'])) {
    $saved_email = $_SESSION['login_email'];
    unset($_SESSION['login_email']);
}

// Check for success message from registration
$success_message = '';
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Login - Mentor Bootstrap Template</title>
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
    .login-section {
      min-height: calc(100vh - 200px);
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 60px 0;
    }
    
    .login-card {
      background: #fff;
      padding: 40px;
      border-radius: 10px;
      box-shadow: 0 0 30px rgba(0, 0, 0, 0.1);
      max-width: 450px;
      width: 100%;
    }
    
    .login-card h2 {
      color: #5fcf80;
      margin-bottom: 10px;
      font-weight: 700;
    }
    
    .login-card p {
      color: #777;
      margin-bottom: 30px;
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
      border-color: #5fcf80;
    }
    
    .form-options {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 25px;
      font-size: 14px;
    }
    
    .remember-me {
      display: flex;
      align-items: center;
      gap: 5px;
    }
    
    .remember-me input {
      width: auto;
    }
    
    .forgot-password {
      color: #5fcf80;
      text-decoration: none;
    }
    
    .forgot-password:hover {
      text-decoration: underline;
    }
    
    .btn-login {
      width: 100%;
      padding: 12px;
      background: #5fcf80;
      color: #fff;
      border: none;
      border-radius: 5px;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.3s;
    }
    
    .btn-login:hover {
      background: #4ab86a;
    }
    
    .signup-link {
      text-align: center;
      margin-top: 20px;
      color: #777;
      font-size: 14px;
    }
    
    .signup-link a {
      color: #5fcf80;
      text-decoration: none;
      font-weight: 600;
    }
    
    .signup-link a:hover {
      text-decoration: underline;
    }
    
    .divider {
      text-align: center;
      margin: 25px 0;
      position: relative;
    }
    
    .divider::before {
      content: "";
      position: absolute;
      top: 50%;
      left: 0;
      right: 0;
      height: 1px;
      background: #ddd;
    }
    
    .divider span {
      background: #fff;
      padding: 0 15px;
      position: relative;
      color: #999;
      font-size: 14px;
    }
    
    .social-login {
      display: flex;
      gap: 10px;
    }
    
    .btn-social {
      flex: 1;
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 5px;
      background: #fff;
      cursor: pointer;
      transition: all 0.3s;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      font-size: 14px;
    }
    
    .btn-social:hover {
      border-color: #5fcf80;
      color: #5fcf80;
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
  </style>
</head>

<body class="starter-page-page">

<?php include('components/header.php'); ?>

  <main class="main">

    <!-- Login Section -->
    <section class="login-section">
      <div class="container">
        <div class="row justify-content-center">
          <div class="col-lg-5 col-md-8">
            <div class="login-card" data-aos="fade-up">
              <h2>Welcome Back</h2>
              <p>Please login to your account</p>
              
			  <?php if ($success_message): ?>
  <div class="alert alert-success">
    <?php echo htmlspecialchars($success_message); ?>
  </div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger">
    <?php foreach ($errors as $error): ?>
      <p style="margin: 5px 0;"><?php echo htmlspecialchars($error); ?></p>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
			  
              <form action="process-login.php" method="post">
                <div class="form-group">
                  <label for="email">Email Address</label>
                  <input type="email" id="email" name="email" placeholder="Enter your email" value="<?php echo htmlspecialchars($saved_email); ?>" required>
                </div>
                
                <div class="form-group">
                  <label for="password">Password</label>
                  <input type="password" id="password" name="password" placeholder="Enter your password" required>
                </div>
                
                <div class="form-options">
                  <label class="remember-me">
                    <input type="checkbox" name="remember">
                    <span>Remember me</span>
                  </label>
                  <a href="forgot-password.php" class="forgot-password">Forgot Password?</a>
                </div>
                
                <button type="submit" class="btn-login">Login</button>
              </form>
              
              <div class="divider">
                <span>OR</span>
              </div>
              
              <div class="social-login">
                <button class="btn-social">
                  <i class="bi bi-google"></i> Google
                </button>
                <button class="btn-social">
                  <i class="bi bi-facebook"></i> Facebook
                </button>
              </div>
              
              <div class="signup-link">
                Don't have an account? <a href="register-page.php">Sign up here</a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

  </main>

<?php include('components/footer.php'); ?>

  <!-- Scroll Top -->
  <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <!-- Preloader -->
  <div id="preloader"></div>

  <!-- Vendor JS Files -->
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/php-email-form/validate.js"></script>
  <script src="assets/vendor/aos/aos.js"></script>
  <script src="assets/vendor/glightbox/js/glightbox.min.js"></script>
  <script src="assets/vendor/purecounter/purecounter_vanilla.js"></script>
  <script src="assets/vendor/swiper/swiper-bundle.min.js"></script>

  <!-- Main JS File -->
  <script src="assets/js/main.js"></script>

</body>
</html>