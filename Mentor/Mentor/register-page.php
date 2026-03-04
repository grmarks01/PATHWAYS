<?php
session_start();
if (isset($_SESSION['registration_errors'])) {
    $errors = $_SESSION['registration_errors'];
    unset($_SESSION['registration_errors']);
} else {
    $errors = [];
}

// Get saved form data if available
$form_data = isset($_SESSION['form_data']) ? $_SESSION['form_data'] : [];
unset($_SESSION['form_data']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Register-Pathways</title>
  <meta name="description" content="">
  <meta name="keywords" content="">

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
    .register-section {
      min-height: calc(100vh - 200px);
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 60px 0;
    }
    
    .register-card {
      background: #fff;
      padding: 40px;
      border-radius: 10px;
      box-shadow: 0 0 30px rgba(0, 0, 0, 0.1);
      max-width: 650px;
      width: 100%;
    }
    
    .register-card h2 {
      color: #5fcf80;
      margin-bottom: 10px;
      font-weight: 700;
    }
    
    .register-card p {
      color: #777;
      margin-bottom: 30px;
    }
    
    .form-row {
      display: flex;
      gap: 15px;
    }
    
    .form-group {
      margin-bottom: 20px;
      flex: 1;
    }
    
    .form-group.full-width {
      flex: 1 1 100%;
    }
    
    .form-group label {
      display: block;
      margin-bottom: 8px;
      color: #333;
      font-weight: 500;
    }
    
    .form-group input,
    .form-group select {
      width: 100%;
      padding: 12px 15px;
      border: 1px solid #ddd;
      border-radius: 5px;
      font-size: 15px;
      transition: border-color 0.3s;
    }
    
    .form-group input:focus,
    .form-group select:focus {
      outline: none;
      border-color: #5fcf80;
    }
    
    .secondary-contact-section {
      background: #f8f9fa;
      padding: 20px;
      border-radius: 8px;
      margin-bottom: 20px;
    }
    
    .secondary-contact-toggle {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 15px;
      cursor: pointer;
    }
    
    .secondary-contact-toggle input[type="checkbox"] {
      width: auto;
      cursor: pointer;
    }
    
    .secondary-contact-fields {
      display: none;
      margin-top: 15px;
    }
    
    .secondary-contact-fields.active {
      display: block;
    }
    
    .section-title {
      color: #5fcf80;
      font-weight: 600;
      margin-bottom: 15px;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    
    .password-strength {
      margin-top: 5px;
      font-size: 12px;
    }
    
    .strength-bar {
      height: 4px;
      background: #f0f0f0;
      border-radius: 2px;
      margin-top: 5px;
      overflow: hidden;
    }
    
    .strength-bar-fill {
      height: 100%;
      width: 0;
      transition: width 0.3s, background-color 0.3s;
    }
    
    .strength-weak {
      width: 33%;
      background: #ff4444;
    }
    
    .strength-medium {
      width: 66%;
      background: #ffaa00;
    }
    
    .strength-strong {
      width: 100%;
      background: #00cc66;
    }
    
    .terms-checkbox {
      display: flex;
      align-items: flex-start;
      gap: 8px;
      margin-bottom: 25px;
      font-size: 14px;
      color: #666;
    }
    
    .terms-checkbox input {
      margin-top: 3px;
      width: auto;
    }
    
    .terms-checkbox a {
      color: #5fcf80;
      text-decoration: none;
    }
    
    .terms-checkbox a:hover {
      text-decoration: underline;
    }
    
    .btn-register {
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
    
    .btn-register:hover {
      background: #4ab86a;
    }
    
    .btn-register:disabled {
      background: #ccc;
      cursor: not-allowed;
    }
    
    .login-link {
      text-align: center;
      margin-top: 20px;
      color: #777;
      font-size: 14px;
    }
    
    .login-link a {
      color: #5fcf80;
      text-decoration: none;
      font-weight: 600;
    }
    
    .login-link a:hover {
      text-decoration: underline;
    }
    
    .error-message {
      color: #ff4444;
      font-size: 12px;
      margin-top: 5px;
      display: none;
    }
    
    .error-message.show {
      display: block;
    }
    
    .alert {
      padding: 15px;
      margin-bottom: 20px;
      border-radius: 5px;
    }
    
    .alert-danger {
      background: #ffebee;
      border-left: 4px solid #f44336;
      color: #c62828;
    }
  </style>
</head>

<body class="starter-page-page">

<?php include('components/header.php'); ?>

  <main class="main">
    <section class="register-section">
      <div class="container">
        <div class="row justify-content-center">
          <div class="col-lg-8 col-md-10">
            <div class="register-card" data-aos="fade-up">
              <h2>Create Account</h2>
              <p>Join us today and start your learning journey</p>
			  
              <?php if (!empty($errors)): ?>
              <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                  <p style="margin: 5px 0;"><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
              </div>
              <?php endif; ?>

              <form action="process-register.php" method="post" id="registerForm">
                
                <div class="section-title">
                  <i class="bi bi-person"></i> Personal Information
                </div>
                
                <div class="form-row">
                  <div class="form-group">
                    <label for="firstName">First Name</label>
                    <input type="text" id="firstName" name="firstName" placeholder="John" value="<?php echo htmlspecialchars($form_data['firstName'] ?? ''); ?>" required>
                  </div>
                  
                  <div class="form-group">
                    <label for="lastName">Last Name</label>
                    <input type="text" id="lastName" name="lastName" placeholder="Doe" value="<?php echo htmlspecialchars($form_data['lastName'] ?? ''); ?>" required>
                  </div>
                </div>
                
                <div class="form-group full-width">
                  <label for="email">Email Address</label>
                  <input type="email" id="email" name="email" placeholder="your.email@example.com" value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>" required>
                  <div class="error-message" id="emailError">Please enter a valid email address</div>
                </div>
                
                <div class="form-group full-width">
                  <label for="phone">Phone Number</label>
                  <input type="tel" id="phone" name="phone" placeholder="+1 (555) 123-4567" value="<?php echo htmlspecialchars($form_data['phone'] ?? ''); ?>">
                </div>
                
                <div class="form-group full-width">
                  <label for="password">Password</label>
                  <input type="password" id="password" name="password" placeholder="Create a strong password" required>
                  <div class="strength-bar">
                    <div class="strength-bar-fill" id="strengthBar"></div>
                  </div>
                  <div class="password-strength" id="strengthText"></div>
                </div>
                
                <div class="form-group full-width">
                  <label for="confirmPassword">Confirm Password</label>
                  <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Re-enter your password" required>
                  <div class="error-message" id="passwordError">Passwords do not match</div>
                </div>
                
                <div class="form-group full-width">
                  <label for="userType">I am a:</label>
                  <select id="userType" name="userType" required>
                    <option value="">Select user type</option>
                    <option value="student" <?php echo (isset($form_data['userType']) && $form_data['userType'] == 'student') ? 'selected' : ''; ?>>Student</option>
                    <option value="teacher" <?php echo (isset($form_data['userType']) && $form_data['userType'] == 'teacher') ? 'selected' : ''; ?>>Teacher</option>
                    <option value="parent" <?php echo (isset($form_data['userType']) && $form_data['userType'] == 'parent') ? 'selected' : ''; ?>>Parent</option>
                    <option value="other" <?php echo (isset($form_data['userType']) && $form_data['userType'] == 'other') ? 'selected' : ''; ?>>Other</option>
                  </select>
                </div>
                
                <!-- Secondary Contact Section -->
                <div class="secondary-contact-section">
                  <div class="section-title">
                    <i class="bi bi-people"></i> Secondary Contact (Optional)
                  </div>
                  <p style="font-size: 13px; color: #666; margin-bottom: 15px;">Add a secondary contact who can be reached regarding your account</p>
                  
                  <label class="secondary-contact-toggle">
                    <input type="checkbox" id="hasSecondaryContact" name="hasSecondaryContact" <?php echo (isset($form_data['hasSecondaryContact'])) ? 'checked' : ''; ?>>
                    <span>I want to add a secondary contact</span>
                  </label>
                  
                  <div class="secondary-contact-fields" id="secondaryContactFields">
                    <div class="form-group full-width">
                      <label for="secondaryName">Secondary Contact Name</label>
                      <input type="text" id="secondaryName" name="secondaryName" placeholder="Full name" value="<?php echo htmlspecialchars($form_data['secondaryName'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group full-width">
                      <label for="secondaryEmail">Secondary Contact Email</label>
                      <input type="email" id="secondaryEmail" name="secondaryEmail" placeholder="email@example.com" value="<?php echo htmlspecialchars($form_data['secondaryEmail'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group full-width">
                      <label for="secondaryPhone">Secondary Contact Phone</label>
                      <input type="tel" id="secondaryPhone" name="secondaryPhone" placeholder="+1 (555) 123-4567" value="<?php echo htmlspecialchars($form_data['secondaryPhone'] ?? ''); ?>">
                    </div>
                  </div>
                </div>
                
                <label class="terms-checkbox">
                  <input type="checkbox" id="terms" name="terms" required>
                  <span>I agree to the <a href="#" onclick="window.open('terms-of-service.php', 'Terms of Service', 'width=900,height=700,scrollbars=yes'); return false;">Terms of Service</a> and <a href="#" onclick="window.open('privacy-policy.php', 'Privacy Policy', 'width=900,height=700,scrollbars=yes'); return false;">Privacy Policy</a></span>
                </label>
                
                <button type="submit" class="btn-register">Create Account</button>
              </form>
              
              <div class="login-link">
                Already have an account? <a href="login-page.php">Login here</a>
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
  <script src="assets/vendor/aos/aos.js"></script>

  <!-- Main JS File -->
  <script src="assets/js/main.js"></script>
  
  <script>
    // Toggle secondary contact fields
    const hasSecondaryContact = document.getElementById('hasSecondaryContact');
    const secondaryContactFields = document.getElementById('secondaryContactFields');
    
    hasSecondaryContact.addEventListener('change', function() {
      if (this.checked) {
        secondaryContactFields.classList.add('active');
      } else {
        secondaryContactFields.classList.remove('active');
      }
    });
    
    // Show fields if checkbox is already checked (from form data)
    if (hasSecondaryContact.checked) {
      secondaryContactFields.classList.add('active');
    }
    
    // Password strength checker
    const password = document.getElementById('password');
    const strengthBar = document.getElementById('strengthBar');
    const strengthText = document.getElementById('strengthText');
    
    password.addEventListener('input', function() {
      const value = this.value;
      let strength = 0;
      
      if (value.length >= 8) strength++;
      if (value.match(/[a-z]+/)) strength++;
      if (value.match(/[A-Z]+/)) strength++;
      if (value.match(/[0-9]+/)) strength++;
      if (value.match(/[$@#&!]+/)) strength++;
      
      strengthBar.className = 'strength-bar-fill';
      
      if (strength === 0) {
        strengthText.textContent = '';
      } else if (strength <= 2) {
        strengthBar.classList.add('strength-weak');
        strengthText.textContent = 'Weak password';
        strengthText.style.color = '#ff4444';
      } else if (strength <= 4) {
        strengthBar.classList.add('strength-medium');
        strengthText.textContent = 'Medium password';
        strengthText.style.color = '#ffaa00';
      } else {
        strengthBar.classList.add('strength-strong');
        strengthText.textContent = 'Strong password';
        strengthText.style.color = '#00cc66';
      }
    });
    
    // Password match validation
    const confirmPassword = document.getElementById('confirmPassword');
    const passwordError = document.getElementById('passwordError');
    const form = document.getElementById('registerForm');
    
    form.addEventListener('submit', function(e) {
      if (password.value !== confirmPassword.value) {
        e.preventDefault();
        passwordError.classList.add('show');
        confirmPassword.style.borderColor = '#ff4444';
      }
    });
    
    confirmPassword.addEventListener('input', function() {
      if (this.value === password.value) {
        passwordError.classList.remove('show');
        this.style.borderColor = '#00cc66';
      } else {
        passwordError.classList.add('show');
        this.style.borderColor = '#ff4444';
      }
    });
  </script>

</body>
</html>