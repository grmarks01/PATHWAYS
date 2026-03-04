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

// Get user data
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT First_Name, Last_Name, Email, Phone, secondary_contact_name, secondary_contact_email, secondary_contact_phone, secondary_contact_active FROM users WHERE User_Id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Handle form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    
    if ($action === 'update_info') {
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $phone = trim($_POST['phone']);
        $has_secondary = isset($_POST['has_secondary_contact']) ? 1 : 0;
        $secondary_name = trim($_POST['secondary_name'] ?? '');
        $secondary_email = trim($_POST['secondary_email'] ?? '');
        $secondary_phone = trim($_POST['secondary_phone'] ?? '');
        
        // Validate
        if (empty($first_name) || empty($last_name)) {
            $error_message = "First name and last name are required.";
        } else {
            // Update user info
            $stmt = $conn->prepare("UPDATE users SET First_Name = ?, Last_Name = ?, Phone = ?, secondary_contact_name = ?, secondary_contact_email = ?, secondary_contact_phone = ?, secondary_contact_active = ? WHERE User_Id = ?");
            $stmt->bind_param("ssssssii", $first_name, $last_name, $phone, $secondary_name, $secondary_email, $secondary_phone, $has_secondary, $user_id);
            
            if ($stmt->execute()) {
                $success_message = "Profile updated successfully!";
                // Update session data
                $_SESSION['user_first_name'] = $first_name;
                $_SESSION['user_last_name'] = $last_name;
                $_SESSION['user_phone'] = $phone;
                // Refresh user data
                $user_data['First_Name'] = $first_name;
                $user_data['Last_Name'] = $last_name;
                $user_data['Phone'] = $phone;
                $user_data['secondary_contact_name'] = $secondary_name;
                $user_data['secondary_contact_email'] = $secondary_email;
                $user_data['secondary_contact_phone'] = $secondary_phone;
                $user_data['secondary_contact_active'] = $has_secondary;
            } else {
                $error_message = "Failed to update profile. Please try again.";
            }
            $stmt->close();
        }
    } elseif ($action === 'change_password') {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validate
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error_message = "All password fields are required.";
        } elseif (strlen($new_password) < 8) {
            $error_message = "New password must be at least 8 characters long.";
        } elseif ($new_password !== $confirm_password) {
            $error_message = "New passwords do not match.";
        } else {
            // Verify current password
            $stmt = $conn->prepare("SELECT Hash, password_interval_days FROM users WHERE User_Id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();
            
            if (password_verify($current_password, $user['Hash'])) {
                // Hash new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                // Calculate new expiration if user has password interval
                $password_expires_at = null;
                if ($user['password_interval_days']) {
                    $interval_days = intval($user['password_interval_days']);
                    $password_expires_at = date('Y-m-d H:i:s', strtotime("+$interval_days days"));
                }
                
                // Update password
                if ($password_expires_at) {
                    $stmt = $conn->prepare("UPDATE users SET Hash = ?, password_expires_at = ?, password_changed_at = NOW() WHERE User_Id = ?");
                    $stmt->bind_param("ssi", $hashed_password, $password_expires_at, $user_id);
                } else {
                    $stmt = $conn->prepare("UPDATE users SET Hash = ?, password_changed_at = NOW() WHERE User_Id = ?");
                    $stmt->bind_param("si", $hashed_password, $user_id);
                }
                
                if ($stmt->execute()) {
                    $success_message = "Password changed successfully!";
                } else {
                    $error_message = "Failed to change password. Please try again.";
                }
                $stmt->close();
            } else {
                $error_message = "Current password is incorrect.";
            }
        }
    }
}

closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Edit Profile - Pathways</title>

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
    .edit-profile-section {
      padding: 80px 0 60px;
      min-height: calc(100vh - 200px);
    }
    
    .page-header {
      background: linear-gradient(135deg, #5fcf80 0%, #4ab86a 100%);
      color: white;
      padding: 40px;
      border-radius: 10px;
      margin-bottom: 40px;
    }
    
    .page-header h1 {
      margin: 0 0 10px 0;
      font-size: 32px;
      font-weight: 700;
    }
    
    .page-header p {
      margin: 0;
      opacity: 0.9;
    }
    
    .back-link {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      color: white;
      text-decoration: none;
      margin-bottom: 15px;
      opacity: 0.9;
      transition: opacity 0.3s;
    }
    
    .back-link:hover {
      opacity: 1;
      color: white;
    }
    
    .edit-card {
      background: #fff;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 0 20px rgba(0, 0, 0, 0.08);
      margin-bottom: 30px;
    }
    
    .edit-card h3 {
      color: #333;
      margin-bottom: 25px;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 10px;
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
    
    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
      margin-bottom: 20px;
    }
    
    .form-group {
      margin-bottom: 20px;
    }
    
    .form-group.full-width {
      grid-column: 1 / -1;
    }
    
    .form-group label {
      display: block;
      margin-bottom: 8px;
      color: #333;
      font-weight: 600;
      font-size: 14px;
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
    
    .form-group input:disabled {
      background: #f8f9fa;
      cursor: not-allowed;
    }
    
    .secondary-contact-section {
      background: #f8f9fa;
      padding: 20px;
      border-radius: 8px;
      margin-top: 20px;
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
    }
    
    .secondary-contact-fields.active {
      display: block;
    }
    
    .info-note {
      background: #e3f2fd;
      padding: 12px 15px;
      border-radius: 5px;
      border-left: 4px solid #2196f3;
      margin-bottom: 20px;
      font-size: 14px;
      color: #0d47a1;
    }
    
    .btn-save {
      padding: 12px 24px;
      background: #5fcf80;
      color: white;
      border: none;
      border-radius: 5px;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.3s;
    }
    
    .btn-save:hover {
      background: #4ab86a;
    }
    
    .btn-cancel {
      padding: 12px 24px;
      background: #fff;
      color: #666;
      border: 2px solid #ddd;
      border-radius: 5px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
      text-decoration: none;
      display: inline-block;
      margin-left: 10px;
    }
    
    .btn-cancel:hover {
      border-color: #999;
      color: #333;
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
    
    @media (max-width: 768px) {
      .form-row {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>

<body>

<?php include('components/header.php'); ?>

<main class="main">
  <section class="edit-profile-section">
    <div class="container">
      
      <div class="page-header" data-aos="fade-down">
        <a href="my-profile.php" class="back-link">
          <i class="bi bi-arrow-left"></i> Back to Profile
        </a>
        <h1><i class="bi bi-pencil-square"></i> Edit Profile</h1>
        <p>Update your personal information and security settings</p>
      </div>
      
      <?php if ($success_message): ?>
      <div class="alert alert-success" data-aos="fade-up">
        <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
      </div>
      <?php endif; ?>
      
      <?php if ($error_message): ?>
      <div class="alert alert-danger" data-aos="fade-up">
        <i class="bi bi-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
      </div>
      <?php endif; ?>
      
      <!-- Personal Information -->
      <div class="edit-card" data-aos="fade-up">
        <h3><i class="bi bi-person-circle"></i> Personal Information</h3>
        
        <form method="POST">
          <input type="hidden" name="action" value="update_info">
          
          <div class="form-row">
            <div class="form-group">
              <label for="first_name">First Name</label>
              <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user_data['First_Name']); ?>" required>
            </div>
            
            <div class="form-group">
              <label for="last_name">Last Name</label>
              <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user_data['Last_Name']); ?>" required>
            </div>
          </div>
          
          <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" value="<?php echo htmlspecialchars($user_data['Email']); ?>" disabled>
            <small style="color: #666; font-size: 13px;">Email cannot be changed. Contact support if you need to update your email.</small>
          </div>
          
          <div class="form-group">
            <label for="phone">Phone Number</label>
            <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user_data['Phone'] ?? ''); ?>" placeholder="+1 (555) 123-4567">
          </div>
          
          <!-- Secondary Contact Section -->
          <div class="secondary-contact-section">
            <h4 style="font-size: 16px; color: #333; margin-bottom: 10px; font-weight: 600;">
              <i class="bi bi-people"></i> Secondary Contact
            </h4>
            <p style="font-size: 13px; color: #666; margin-bottom: 15px;">Add a secondary contact who can be reached regarding your account</p>
            
            <label class="secondary-contact-toggle">
              <input type="checkbox" id="has_secondary_contact" name="has_secondary_contact" <?php echo $user_data['secondary_contact_active'] ? 'checked' : ''; ?>>
              <span>I have a secondary contact</span>
            </label>
            
            <div class="secondary-contact-fields <?php echo $user_data['secondary_contact_active'] ? 'active' : ''; ?>" id="secondary_fields">
              <div class="form-group">
                <label for="secondary_name">Secondary Contact Name</label>
                <input type="text" id="secondary_name" name="secondary_name" value="<?php echo htmlspecialchars($user_data['secondary_contact_name'] ?? ''); ?>" placeholder="Full name">
              </div>
              
              <div class="form-group">
                <label for="secondary_email">Secondary Contact Email</label>
                <input type="email" id="secondary_email" name="secondary_email" value="<?php echo htmlspecialchars($user_data['secondary_contact_email'] ?? ''); ?>" placeholder="email@example.com">
              </div>
              
              <div class="form-group">
                <label for="secondary_phone">Secondary Contact Phone</label>
                <input type="tel" id="secondary_phone" name="secondary_phone" value="<?php echo htmlspecialchars($user_data['secondary_contact_phone'] ?? ''); ?>" placeholder="+1 (555) 123-4567">
              </div>
            </div>
          </div>
          
          <div style="margin-top: 25px;">
            <button type="submit" class="btn-save">
              <i class="bi bi-save"></i> Save Changes
            </button>
            <a href="my-profile.php" class="btn-cancel">Cancel</a>
          </div>
        </form>
      </div>
      
      <!-- Change Password -->
      <div class="edit-card" data-aos="fade-up">
        <h3><i class="bi bi-key"></i> Change Password</h3>
        
        <div class="info-note">
          <i class="bi bi-info-circle"></i> <strong>Password Requirements:</strong> Minimum 8 characters. Use a mix of letters, numbers, and special characters for stronger security.
        </div>
        
        <form method="POST" id="passwordForm">
          <input type="hidden" name="action" value="change_password">
          
          <div class="form-group">
            <label for="current_password">Current Password</label>
            <input type="password" id="current_password" name="current_password" placeholder="Enter your current password" required>
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
            <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter new password" required>
            <div class="password-match-indicator" id="match-indicator"></div>
          </div>
          
          <div style="margin-top: 25px;">
            <button type="submit" class="btn-save" id="submit-btn">
              <i class="bi bi-shield-check"></i> Change Password
            </button>
            <a href="my-profile.php" class="btn-cancel">Cancel</a>
          </div>
        </form>
      </div>
      
    </div>
  </section>
</main>

<?php include('components/footer.php'); ?>

<a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

<script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/vendor/aos/aos.js"></script>
<script src="assets/js/main.js"></script>

<script>
// Toggle secondary contact fields
const hasSecondaryContact = document.getElementById('has_secondary_contact');
const secondaryFields = document.getElementById('secondary_fields');

hasSecondaryContact.addEventListener('change', function() {
  if (this.checked) {
    secondaryFields.classList.add('active');
  } else {
    secondaryFields.classList.remove('active');
  }
});

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