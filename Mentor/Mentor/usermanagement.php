<?php
session_start();

// Check if user is logged in and is an approved teacher (admin)
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login-page.php");
    exit();
}

$is_admin = ($_SESSION['user_role'] === 'teacher' && isset($_SESSION['user_active']) && $_SESSION['user_active'] === '1');

if (!$is_admin) {
    header("Location: index.php");
    exit();
}

//create database connection
require_once 'components/db-config.php';
   $conn = getDBConnection();


// Handle actions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        
        if ($action === 'approve' && $user_id > 0) {
            $stmt = $conn->prepare("UPDATE users SET Active = '1' WHERE User_Id = ? AND Role = 'teacher'");
            $stmt->bind_param("i", $user_id);
            if ($stmt->execute()) {
                $message = "Teacher approved successfully! They now have admin access.";
                $message_type = "success";
            }
            $stmt->close();
        } elseif ($action === 'unapprove' && $user_id > 0) {
            if ($user_id != $_SESSION['user_id']) {
                $stmt = $conn->prepare("UPDATE users SET Active = NULL WHERE User_Id = ? AND Role = 'teacher'");
                $stmt->bind_param("i", $user_id);
                if ($stmt->execute()) {
                    $message = "Admin access revoked successfully!";
                    $message_type = "success";
                }
                $stmt->close();
            } else {
                $message = "You cannot revoke your own admin access!";
                $message_type = "error";
            }
        } elseif ($action === 'delete' && $user_id > 0) {
            if ($user_id != $_SESSION['user_id']) {
                $stmt = $conn->prepare("DELETE FROM users WHERE User_Id = ?");
                $stmt->bind_param("i", $user_id);
                if ($stmt->execute()) {
                    $message = "User deleted successfully!";
                    $message_type = "success";
                }
                $stmt->close();
            } else {
                $message = "You cannot delete your own account!";
                $message_type = "error";
            }
        } elseif ($action === 'reset_password' && $user_id > 0) {
            $new_password = $_POST['new_password'];
            if (strlen($new_password) >= 8) {
                $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET Hash = ? WHERE User_Id = ?");
                $stmt->bind_param("si", $hashed, $user_id);
                if ($stmt->execute()) {
                    $message = "Password reset successfully!";
                    $message_type = "success";
                }
                $stmt->close();
            } else {
                $message = "Password must be at least 8 characters!";
                $message_type = "error";
            }
        } elseif ($action === 'set_password_interval' && $user_id > 0) {
            $interval_days = intval($_POST['interval_days']);
            
            if ($interval_days > 0) {
                $expiration_date = date('Y-m-d H:i:s', strtotime("+$interval_days days"));
                
                $stmt = $conn->prepare("UPDATE users SET password_interval_days = ?, password_expires_at = ? WHERE User_Id = ?");
                $stmt->bind_param("isi", $interval_days, $expiration_date, $user_id);
                
                if ($stmt->execute()) {
                    $message = "Password reset interval set to $interval_days days. User must reset password by " . date('M d, Y', strtotime($expiration_date));
                    $message_type = "success";
                }
                $stmt->close();
            } else {
                $message = "Interval must be greater than 0 days!";
                $message_type = "error";
            }
        } elseif ($action === 'clear_password_interval' && $user_id > 0) {
            $stmt = $conn->prepare("UPDATE users SET password_interval_days = NULL, password_expires_at = NULL WHERE User_Id = ?");
            $stmt->bind_param("i", $user_id);
            
            if ($stmt->execute()) {
                $message = "Password reset interval cleared successfully!";
                $message_type = "success";
            }
            $stmt->close();
        } elseif ($action === 'set_global_interval') {
            $global_interval = intval($_POST['global_interval']);
            
            if ($global_interval > 0) {
                $expiration_date = date('Y-m-d H:i:s', strtotime("+$global_interval days"));
                
                // Update all users with the new interval - no prepared statement needed for global update
                $update_query = "UPDATE users SET password_interval_days = $global_interval, password_expires_at = '$expiration_date'";
                
                if ($conn->query($update_query)) {
                    $affected = $conn->affected_rows;
                    $message = "Global password reset interval set to $global_interval days for $affected users. All passwords will expire on " . date('M d, Y', strtotime($expiration_date));
                    $message_type = "success";
                } else {
                    $message = "Failed to set global interval: " . $conn->error;
                    $message_type = "error";
                }
            } else {
                $message = "Global interval must be greater than 0 days!";
                $message_type = "error";
            }
        } elseif ($action === 'change_role' && $user_id > 0) {
            $new_role = $_POST['new_role'];
            
            if ($new_role === 'teacher') {
                $stmt = $conn->prepare("UPDATE users SET Role = ?, Active = NULL WHERE User_Id = ?");
                $stmt->bind_param("si", $new_role, $user_id);
            } else {
                $stmt = $conn->prepare("UPDATE users SET Role = ?, Active = '1' WHERE User_Id = ?");
                $stmt->bind_param("si", $new_role, $user_id);
            }
            
            if ($stmt->execute()) {
                if ($new_role === 'teacher') {
                    $message = "User role updated to teacher. They will need admin approval to access admin features.";
                } else {
                    $message = "User role updated successfully!";
                }
                $message_type = "success";
            }
            $stmt->close();
        }
    }
}

// Handle search and filters
$search_query = '';
$where_conditions = [];
$params = [];
$param_types = '';

if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search_query = trim($_GET['search']);
    
    // Search in multiple fields
    $where_conditions[] = "(
        First_Name LIKE ? OR 
        Last_Name LIKE ? OR 
        Email LIKE ? OR 
        Phone LIKE ? OR
        Role LIKE ? OR
        CONCAT(First_Name, ' ', Last_Name) LIKE ?
    )";
    
    $search_param = "%{$search_query}%";
    $params = array_fill(0, 6, $search_param);
    $param_types = str_repeat('s', 6);
}

// Handle role filter
if (isset($_GET['role']) && !empty($_GET['role']) && $_GET['role'] !== 'all') {
    $where_conditions[] = "Role = ?";
    $params[] = $_GET['role'];
    $param_types .= 's';
}

// Handle status filter
if (isset($_GET['status']) && !empty($_GET['status'])) {
    if ($_GET['status'] === 'approved_admin') {
        $where_conditions[] = "Role = 'teacher' AND Active = '1'";
    } elseif ($_GET['status'] === 'pending_admin') {
        $where_conditions[] = "Role = 'teacher' AND Active IS NULL";
    } elseif ($_GET['status'] === 'password_expired') {
        $where_conditions[] = "password_expires_at IS NOT NULL AND password_expires_at < NOW()";
    }
}

// Build the query
$users_query = "SELECT User_Id, Email, Role, First_Name, Last_Name, Phone, Active, Created_Time, password_interval_days, password_expires_at FROM users";

if (!empty($where_conditions)) {
    $users_query .= " WHERE " . implode(" AND ", $where_conditions);
}

$users_query .= " ORDER BY Created_Time DESC";

// Execute query with parameters if needed
if (!empty($params)) {
    $stmt = $conn->prepare($users_query);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $users_result = $stmt->get_result();
} else {
    $users_result = $conn->query($users_query);
}

// Get pending teacher approvals count
$pending_query = "SELECT COUNT(*) as count FROM users WHERE Role = 'teacher' AND Active IS NULL";
$pending_result = $conn->query($pending_query);
$pending_count = $pending_result->fetch_assoc()['count'];

// Get total teachers (approved admins)
$admin_query = "SELECT COUNT(*) as count FROM users WHERE Role = 'teacher' AND Active = '1'";
$admin_result = $conn->query($admin_query);
$admin_count = $admin_result->fetch_assoc()['count'];

// Get users with expired passwords
$expired_query = "SELECT COUNT(*) as count FROM users WHERE password_expires_at IS NOT NULL AND password_expires_at < NOW()";
$expired_result = $conn->query($expired_query);
$expired_count = $expired_result->fetch_assoc()['count'];

// Get total users count (without filters for stats)
$total_users_query = "SELECT COUNT(*) as count FROM users";
$total_users_result = $conn->query($total_users_query);
$total_users_count = $total_users_result->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>User Management - Pathways</title>

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
    .admin-section {
      padding: 80px 0 60px;
      min-height: 100vh;
    }
    
    .admin-header {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      padding: 40px;
      border-radius: 10px;
      margin-bottom: 40px;
    }
    
    .admin-header h1 {
      margin: 0 0 10px 0;
      font-size: 32px;
      font-weight: 700;
    }
    
    .stats-container {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }
    
    .stat-card {
      background: white;
      padding: 25px;
      border-radius: 10px;
      box-shadow: 0 0 20px rgba(0, 0, 0, 0.08);
    }
    
    .stat-card h3 {
      font-size: 14px;
      color: #666;
      margin: 0 0 10px 0;
      text-transform: uppercase;
      font-weight: 600;
    }
    
    .stat-card .value {
      font-size: 32px;
      font-weight: 700;
      color: #333;
    }
    
    .stat-card .icon {
      font-size: 40px;
      opacity: 0.2;
      float: right;
    }
    
    .stat-card.warning {
      border-left: 4px solid #dc3545;
    }
    
    .alert {
      padding: 15px 20px;
      border-radius: 5px;
      margin-bottom: 20px;
    }
    
    .alert-success {
      background: #d4edda;
      border-left: 4px solid #28a745;
      color: #155724;
    }
    
    .alert-error {
      background: #f8d7da;
      border-left: 4px solid #dc3545;
      color: #721c24;
    }
    
    .global-actions {
      background: white;
      padding: 25px;
      border-radius: 10px;
      box-shadow: 0 0 20px rgba(0, 0, 0, 0.08);
      margin-bottom: 30px;
    }
    
    .global-actions h3 {
      margin: 0 0 20px 0;
      color: #333;
    }
    
    .global-form {
      display: flex;
      gap: 15px;
      align-items: end;
    }
    
    .global-form .form-group {
      flex: 1;
      margin: 0;
    }
    
    .search-filter-container {
      background: white;
      padding: 25px;
      border-radius: 10px;
      box-shadow: 0 0 20px rgba(0, 0, 0, 0.08);
      margin-bottom: 30px;
    }

    .search-form {
      width: 100%;
    }

    .search-row {
      display: flex;
      gap: 15px;
      align-items: center;
      flex-wrap: wrap;
    }

    .search-input-group {
      flex: 1;
      min-width: 300px;
      position: relative;
      display: flex;
      align-items: center;
    }

    .search-input-group i {
      position: absolute;
      left: 15px;
      color: #999;
      font-size: 18px;
    }

    .search-input {
      width: 100%;
      padding: 12px 15px 12px 45px;
      border: 2px solid #e0e0e0;
      border-radius: 8px;
      font-size: 14px;
      transition: all 0.3s;
    }

    .search-input:focus {
      outline: none;
      border-color: #667eea;
      box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .filter-group {
      min-width: 150px;
    }

    .filter-select {
      width: 100%;
      padding: 12px 15px;
      border: 2px solid #e0e0e0;
      border-radius: 8px;
      font-size: 14px;
      background: white;
      cursor: pointer;
      transition: all 0.3s;
    }

    .filter-select:focus {
      outline: none;
      border-color: #667eea;
      box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .btn-search {
      padding: 12px 24px;
      background: #667eea;
      color: white;
      border: none;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
      white-space: nowrap;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .btn-search:hover {
      background: #5568d3;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }

    .btn-clear {
      padding: 12px 24px;
      background: #6c757d;
      color: white;
      border: none;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
      text-decoration: none;
      white-space: nowrap;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .btn-clear:hover {
      background: #5a6268;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(108, 117, 125, 0.3);
    }

    .search-results-info {
      margin-top: 20px;
      padding: 12px 15px;
      background: #e3f2fd;
      border-left: 4px solid #2196f3;
      border-radius: 5px;
      color: #0d47a1;
      font-size: 14px;
    }

    .search-results-info strong {
      color: #0d47a1;
      font-weight: 700;
    }
    
    .badge {
      padding: 5px 12px;
      border-radius: 20px;
      font-size: 11px;
      font-weight: 600;
      text-transform: uppercase;
    }
    
    .badge-teacher-admin {
      background: #667eea;
      color: white;
    }
    
    .badge-teacher-pending {
      background: #ffc107;
      color: #333;
    }
    
    .badge-student {
      background: #17a2b8;
      color: white;
    }
    
    .badge-parent {
      background: #28a745;
      color: white;
    }
    
    .badge-other {
      background: #6c757d;
      color: white;
    }
    
    .badge-approved {
      background: #28a745;
      color: white;
    }
    
    .badge-pending {
      background: #ffc107;
      color: #333;
    }
    
    .badge-expired {
      background: #dc3545;
      color: white;
    }
    
    .badge-expiring {
      background: #ff9800;
      color: white;
    }
    
    .badge-active {
      background: #28a745;
      color: white;
    }
    
    .btn-action {
      padding: 8px 12px;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      font-size: 13px;
      font-weight: 600;
      transition: all 0.3s;
      margin: 2px;
    }
    
    .btn-approve {
      background: #28a745;
      color: white;
    }
    
    .btn-approve:hover {
      background: #218838;
    }
    
    .btn-unapprove {
      background: #ffc107;
      color: #333;
    }
    
    .btn-unapprove:hover {
      background: #e0a800;
    }
    
    .btn-delete {
      background: #dc3545;
      color: white;
    }
    
    .btn-delete:hover {
      background: #c82333;
    }
    
    .btn-reset {
      background: #17a2b8;
      color: white;
    }
    
    .btn-reset:hover {
      background: #138496;
    }
    
    .btn-role {
      background: #667eea;
      color: white;
    }
    
    .btn-role:hover {
      background: #5568d3;
    }
    
    .btn-interval {
      background: #ff9800;
      color: white;
    }
    
    .btn-interval:hover {
      background: #e68900;
    }
    
    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5);
    }
    
    .modal-content {
      background: white;
      margin: 10% auto;
      padding: 30px;
      border-radius: 10px;
      width: 90%;
      max-width: 500px;
      box-shadow: 0 5px 30px rgba(0, 0, 0, 0.3);
    }
    
    .modal-header {
      margin-bottom: 20px;
    }
    
    .modal-header h3 {
      margin: 0;
      color: #333;
    }
    
    .close {
      float: right;
      font-size: 28px;
      font-weight: bold;
      color: #999;
      cursor: pointer;
    }
    
    .close:hover {
      color: #333;
    }
    
    .form-group {
      margin-bottom: 20px;
    }
    
    .form-group label {
      display: block;
      margin-bottom: 8px;
      color: #333;
      font-weight: 600;
    }
    
    .form-group input,
    .form-group select {
      width: 100%;
      padding: 12px;
      border: 1px solid #ddd;
      border-radius: 5px;
    }
    
    .btn-submit {
      width: 100%;
      padding: 12px;
      background: #667eea;
      color: white;
      border: none;
      border-radius: 5px;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.3s;
    }
    
    .btn-submit:hover {
      background: #5568d3;
    }
    
    .info-note {
      background: #e3f2fd;
      padding: 15px;
      border-radius: 5px;
      border-left: 4px solid #2196f3;
      margin-bottom: 20px;
      font-size: 14px;
    }
    
    .expiration-info {
      font-size: 12px;
      color: #666;
    }
    
    .expiration-expired {
      color: #dc3545;
      font-weight: 600;
    }
    
    .expiration-warning {
      color: #ff9800;
      font-weight: 600;
    }

   .users-table-container {
  background: white;
  padding: 30px;
  border-radius: 10px;
  box-shadow: 0 0 20px rgba(0, 0, 0, 0.08);
  overflow-x: auto;
}

.users-table {
  width: 100%;
  border-collapse: collapse;
}

.users-table th,
.users-table td {
  padding: 15px;
  text-align: left;
  border-bottom: 1px solid #f0f0f0;
}

.users-table th {
  background: #f8f9fa;
  font-weight: 600;
  color: #333;
  position: sticky;
  top: 0;
}

.users-table tr:hover {
  background: #f8f9fa;
}

.btn-action {
  padding: 8px 12px;
  border: none;
  border-radius: 5px;
  cursor: pointer;
  font-size: 13px;
  font-weight: 600;
  transition: all 0.3s;
  margin: 2px;
}

/* TABLET - Hide non-essential columns and compress layout */
@media (max-width: 1024px) {
  .users-table {
    font-size: 12px;
  }
  
  .users-table th,
  .users-table td {
    padding: 10px 6px;
  }
  
  /* Hide ID column */
  .users-table th:nth-child(1),
  .users-table td:nth-child(1) {
    display: none;
  }
  
  /* Hide Phone column */
  .users-table th:nth-child(4),
  .users-table td:nth-child(4) {
    display: none;
  }
  
  /* Hide Interval column */
  .users-table th:nth-child(7),
  .users-table td:nth-child(7) {
    display: none;
  }
  
  /* Hide Password Expires column */
  .users-table th:nth-child(8),
  .users-table td:nth-child(8) {
    display: none;
  }
  
  /* Hide Created column */
  .users-table th:nth-child(9),
  .users-table td:nth-child(9) {
    display: none;
  }
  
  /* Compress Role and Status badges */
  .users-table td:nth-child(5) .badge,
  .users-table td:nth-child(6) .badge {
    padding: 4px 8px;
    font-size: 10px;
  }
  
  /* Make buttons smaller and hide icons */
  .btn-action {
    padding: 6px 8px;
    font-size: 10px;
    margin: 2px 0;
  }
  
  .btn-action i {
    display: none; /* Hide icons to save space */
  }
  
  /* Stack buttons in actions column */
  td:last-child {
    min-width: 100px;
  }
  
  td:last-child form {
    display: block;
    margin: 2px 0;
  }
  
  td:last-child .btn-action {
    display: block;
    width: 100%;
  }
}

/* MEDIUM TABLET/SMALL LAPTOP */
@media (max-width: 768px) {
  .users-table-container {
    padding: 15px;
  }
  
  .users-table {
    font-size: 11px;
  }
  
  .users-table th,
  .users-table td {
    padding: 8px 4px;
  }
  
  /* Hide ID column */
  .users-table th:nth-child(1),
  .users-table td:nth-child(1) {
    display: none;
  }
  
  /* Hide Email column */
  .users-table th:nth-child(3),
  .users-table td:nth-child(3) {
    display: none;
  }
  
  /* Hide Phone column */
  .users-table th:nth-child(4),
  .users-table td:nth-child(4) {
    display: none;
  }
  
  /* Hide Interval column */
  .users-table th:nth-child(7),
  .users-table td:nth-child(7) {
    display: none;
  }
  
  /* Hide Password Expires column */
  .users-table th:nth-child(8),
  .users-table td:nth-child(8) {
    display: none;
  }
  
  /* Hide Created column */
  .users-table th:nth-child(9),
  .users-table td:nth-child(9) {
    display: none;
  }
  
  /* Further compress badges */
  .users-table td:nth-child(5) .badge,
  .users-table td:nth-child(6) .badge {
    padding: 3px 6px;
    font-size: 9px;
  }
  
  .btn-action {
    padding: 6px 8px;
    font-size: 10px;
    margin: 2px 0;
  }
}

/* MOBILE - Minimal columns only */
@media (max-width: 480px) {
  .users-table-container {
    padding: 10px;
  }
  
  .users-table {
    font-size: 11px;
  }
  
  .users-table th,
  .users-table td {
    padding: 8px 4px;
  }
  
  /* Hide ID */
  .users-table th:nth-child(1),
  .users-table td:nth-child(1) {
    display: none;
  }
  
  /* Hide Email */
  .users-table th:nth-child(3),
  .users-table td:nth-child(3) {
    display: none;
  }
  
  /* Hide Phone */
  .users-table th:nth-child(4),
  .users-table td:nth-child(4) {
    display: none;
  }
  
  /* Compress Role column (5th) */
  .users-table th:nth-child(5),
  .users-table td:nth-child(5) {
    padding: 6px 2px;
  }
  
  .users-table td:nth-child(5) .badge {
    padding: 3px 6px;
    font-size: 9px;
    white-space: nowrap;
  }
  
  /* Compress Status column (6th) */
  .users-table th:nth-child(6),
  .users-table td:nth-child(6) {
    padding: 6px 2px;
  }
  
  .users-table td:nth-child(6) .badge {
    padding: 3px 6px;
    font-size: 9px;
    white-space: nowrap;
  }
  
  /* Hide Interval */
  .users-table th:nth-child(7),
  .users-table td:nth-child(7) {
    display: none;
  }
  
  /* Hide Password Expires */
  .users-table th:nth-child(8),
  .users-table td:nth-child(8) {
    display: none;
  }
  
  /* Hide Created */
  .users-table th:nth-child(9),
  .users-table td:nth-child(9) {
    display: none;
  }
  
  /* Stack buttons vertically */
  .btn-action {
    display: block;
    width: 100%;
    padding: 6px 8px;
    font-size: 10px;
    margin: 3px 0 !important;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  
  /* Stack actions column */
  td:last-child {
    display: flex;
    flex-direction: column;
    gap: 4px;
    padding: 6px 4px;
  }
  
  td:last-child form {
    width: 100%;
  }
  
  td:last-child form .btn-action {
    margin: 0 !important;
  }
}

/* Adjust search bar and other elements on tablets/mobile */
@media (max-width: 768px) {
  .search-row {
    flex-direction: column;
    gap: 10px;
  }
  
  .search-input-group,
  .filter-group,
  .btn-search,
  .btn-clear {
    width: 100%;
    min-width: 100%;
  }
  
  .btn-search,
  .btn-clear {
    justify-content: center;
  }
  
  .stats-container {
    grid-template-columns: 1fr 1fr;
  }
  
  .admin-header h1 {
    font-size: 24px;
  }
  
  .admin-header p {
    font-size: 14px;
  }
}

@media (max-width: 480px) {
  .stats-container {
    grid-template-columns: 1fr;
  }
  
  .global-form {
    flex-direction: column;
  }
  
  .global-form .form-group {
    flex: 1 1 100%;
  }
  
  .global-form .btn-submit {
    width: 100% !important;
  }
}
  </style>
</head>

<body>

<?php include('components/header.php'); ?>

<main class="main">
  <section class="admin-section">
    <div class="container">
      
      <div class="admin-header" data-aos="fade-down">
        <h1><i class="bi bi-people"></i> User Management</h1>
        <p>Manage user accounts, teacher approvals, and password policies</p>
      </div>
      
      <div class="info-note" data-aos="fade-up">
        <i class="bi bi-info-circle"></i> <strong>How it works:</strong> Approved teachers have full admin access. New teachers must be approved by an existing admin before gaining admin capabilities. You can set password reset intervals to force users to update their passwords periodically.
      </div>
      
      <?php if ($message): ?>
      <div class="alert alert-<?php echo $message_type; ?>" data-aos="fade-up">
        <?php echo htmlspecialchars($message); ?>
      </div>
      <?php endif; ?>
      
      <div class="stats-container" data-aos="fade-up">
        <div class="stat-card">
          <i class="bi bi-people icon"></i>
          <h3>Total Users</h3>
          <div class="value"><?php echo $total_users_count; ?></div>
        </div>
        
        <div class="stat-card">
          <i class="bi bi-shield-check icon"></i>
          <h3>Active Admins</h3>
          <div class="value"><?php echo $admin_count; ?></div>
        </div>
        
        <div class="stat-card">
          <i class="bi bi-clock-history icon"></i>
          <h3>Pending Approvals</h3>
          <div class="value"><?php echo $pending_count; ?></div>
        </div>
        
        <div class="stat-card <?php echo ($expired_count > 0) ? 'warning' : ''; ?>">
          <i class="bi bi-key icon"></i>
          <h3>Expired Passwords</h3>
          <div class="value" style="<?php echo ($expired_count > 0) ? 'color: #dc3545;' : ''; ?>"><?php echo $expired_count; ?></div>
        </div>
      </div>
      
      <div class="global-actions" data-aos="fade-up">
        <h3><i class="bi bi-globe"></i> Global Password Policy</h3>
        <form method="POST" class="global-form">
          <input type="hidden" name="action" value="set_global_interval">
          <div class="form-group">
            <label>Set Password Reset Interval for All Users</label>
            <input type="number" name="global_interval" placeholder="Days until password reset required" min="1" max="365" required>
          </div>
          <button type="submit" class="btn-submit" style="width: auto; white-space: nowrap;" onclick="return confirm('This will set the password reset interval for ALL users. Continue?')">
            <i class="bi bi-arrow-repeat"></i> Apply to All Users
          </button>
        </form>
      </div>
      
      <div class="search-filter-container" data-aos="fade-up">
        <form method="GET" action="" class="search-form">
          <div class="search-row">
            <div class="search-input-group">
              <i class="bi bi-search"></i>
              <input 
                type="text" 
                name="search" 
                placeholder="Search by name, email, phone, or role..." 
                value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>"
                class="search-input"
              >
            </div>
            
            <div class="filter-group">
              <select name="role" class="filter-select">
                <option value="all">All Roles</option>
                <option value="teacher" <?php echo (isset($_GET['role']) && $_GET['role'] === 'teacher') ? 'selected' : ''; ?>>Teacher</option>
                <option value="student" <?php echo (isset($_GET['role']) && $_GET['role'] === 'student') ? 'selected' : ''; ?>>Student</option>
                <option value="parent" <?php echo (isset($_GET['role']) && $_GET['role'] === 'parent') ? 'selected' : ''; ?>>Parent</option>
                <option value="other" <?php echo (isset($_GET['role']) && $_GET['role'] === 'other') ? 'selected' : ''; ?>>Other</option>
              </select>
            </div>
            
            <div class="filter-group">
              <select name="status" class="filter-select">
                <option value="">All Status</option>
                <option value="approved_admin" <?php echo (isset($_GET['status']) && $_GET['status'] === 'approved_admin') ? 'selected' : ''; ?>>Approved Admins</option>
                <option value="pending_admin" <?php echo (isset($_GET['status']) && $_GET['status'] === 'pending_admin') ? 'selected' : ''; ?>>Pending Approval</option>
                <option value="password_expired" <?php echo (isset($_GET['status']) && $_GET['status'] === 'password_expired') ? 'selected' : ''; ?>>Expired Passwords</option>
              </select>
            </div>
            
            <button type="submit" class="btn-search">
              <i class="bi bi-search"></i> Search
            </button>
            
            <?php if (!empty($_GET['search']) || !empty($_GET['role']) || !empty($_GET['status'])): ?>
            <a href="usermanagement.php" class="btn-clear">
              <i class="bi bi-x-circle"></i> Clear
            </a>
            <?php endif; ?>
          </div>
        </form>
        
        <?php if (!empty($_GET['search']) || !empty($_GET['role']) || !empty($_GET['status'])): ?>
        <div class="search-results-info">
          <i class="bi bi-info-circle"></i> 
          Found <strong><?php echo $users_result->num_rows; ?></strong> user(s)
          <?php if (!empty($_GET['search'])): ?>
            matching "<strong><?php echo htmlspecialchars($_GET['search']); ?></strong>"
          <?php endif; ?>
        </div>
        <?php endif; ?>
      </div>
      
      <div class="users-table-container" data-aos="fade-up">
        <h3 style="margin-bottom: 20px;"><i class="bi bi-table"></i> All Users</h3>
        
        <table class="users-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Name</th>
              <th>Email</th>
              <th>Phone</th>
              <th>Role</th>
              <th>Status</th>
              <th>Interval (Days)</th>
              <th>Password Expires</th>
              <th>Created</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            if ($users_result->num_rows > 0):
              while ($user = $users_result->fetch_assoc()): 
            ?>
            <tr>
              <td><?php echo $user['User_Id']; ?></td>
              <td><?php echo htmlspecialchars($user['First_Name'] . ' ' . $user['Last_Name']); ?></td>
              <td><?php echo htmlspecialchars($user['Email']); ?></td>
              <td><?php echo htmlspecialchars($user['Phone'] ?: 'N/A'); ?></td>
              <td>
                <?php 
                $role_class = 'badge-other';
                $role_display = ucfirst($user['Role']);
                
                if ($user['Role'] === 'teacher' && $user['Active'] === '1') {
                    $role_class = 'badge-teacher-admin';
                    $role_display = 'Teacher (Admin)';
                } elseif ($user['Role'] === 'teacher' && $user['Active'] === NULL) {
                    $role_class = 'badge-teacher-pending';
                    $role_display = 'Teacher (Pending)';
                } elseif ($user['Role'] === 'student') {
                    $role_class = 'badge-student';
                } elseif ($user['Role'] === 'parent') {
                    $role_class = 'badge-parent';
                }
                ?>
                <span class="badge <?php echo $role_class; ?>"><?php echo $role_display; ?></span>
              </td>
              <td>
                <?php if ($user['Role'] === 'teacher'): ?>
                  <?php if ($user['Active'] === '1'): ?>
                    <span class="badge badge-approved">✓ Admin Access</span>
                  <?php else: ?>
                    <span class="badge badge-pending">⏳ Awaiting Approval</span>
                  <?php endif; ?>
                <?php else: ?>
                  <span class="badge badge-approved">✓ Active</span>
                <?php endif; ?>
              </td>
              <td>
                <?php 
                if ($user['password_interval_days']) {
                    echo '<span style="font-weight: 600;">' . htmlspecialchars($user['password_interval_days']) . ' days</span>';
                } else {
                    echo '<span style="color: #999;">Not Set</span>';
                }
                ?>
              </td>
              <td>
                <?php 
                if ($user['password_expires_at']) {
                    $expires = strtotime($user['password_expires_at']);
                    $now = time();
                    $days_remaining = floor(($expires - $now) / 86400);
                    
                    if ($expires < $now) {
                        echo '<span class="expiration-expired"><i class="bi bi-exclamation-circle"></i> EXPIRED</span>';
                        echo '<div class="expiration-info">' . date('M d, Y', $expires) . '</div>';
                    } elseif ($days_remaining <= 7) {
                        echo '<span class="expiration-warning"><i class="bi bi-exclamation-triangle"></i> ' . $days_remaining . ' days</span>';
                        echo '<div class="expiration-info">' . date('M d, Y', $expires) . '</div>';
                    } else {
                        echo '<span style="color: #28a745; font-weight: 600;"><i class="bi bi-check-circle"></i> ' . $days_remaining . ' days</span>';
                        echo '<div class="expiration-info">' . date('M d, Y', $expires) . '</div>';
                    }
                } else {
                    echo '<span style="color: #999;">No Expiration</span>';
                }
                ?>
              </td>
              <td><?php echo date('M d, Y', strtotime($user['Created_Time'])); ?></td>
              <td>
                <?php if ($user['User_Id'] != $_SESSION['user_id']): ?>
                  
                  <?php if ($user['Role'] === 'teacher' && $user['Active'] === NULL): ?>
                  <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="approve">
                    <input type="hidden" name="user_id" value="<?php echo $user['User_Id']; ?>">
                    <button type="submit" class="btn-action btn-approve" onclick="return confirm('Approve this teacher for admin access?')">
                      <i class="bi bi-check-circle"></i> Approve
                    </button>
                  </form>
                  <?php elseif ($user['Role'] === 'teacher' && $user['Active'] === '1'): ?>
                  <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="unapprove">
                    <input type="hidden" name="user_id" value="<?php echo $user['User_Id']; ?>">
                    <button type="submit" class="btn-action btn-unapprove" onclick="return confirm('Revoke admin access for this teacher?')">
                      <i class="bi bi-x-circle"></i> Revoke Admin
                    </button>
                  </form>
                  <?php endif; ?>
                  
                  <button class="btn-action btn-reset" onclick="openResetPasswordModal(<?php echo $user['User_Id']; ?>, '<?php echo htmlspecialchars(addslashes($user['First_Name'] . ' ' . $user['Last_Name'])); ?>')">
                    <i class="bi bi-key"></i> Reset Password
                  </button>
                  
                  <button class="btn-action btn-interval" onclick="openPasswordIntervalModal(<?php echo $user['User_Id']; ?>, '<?php echo htmlspecialchars(addslashes($user['First_Name'] . ' ' . $user['Last_Name'])); ?>', '<?php echo $user['password_interval_days']; ?>')">
                    <i class="bi bi-clock-history"></i> Set Interval
                  </button>
                  
                  <?php if ($user['password_interval_days']): ?>
                  <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="clear_password_interval">
                    <input type="hidden" name="user_id" value="<?php echo $user['User_Id']; ?>">
                    <button type="submit" class="btn-action" style="background: #6c757d; color: white;" onclick="return confirm('Clear password reset interval for this user?')">
                      <i class="bi bi-x-circle"></i> Clear Interval
                    </button>
                  </form>
                  <?php endif; ?>
                  
                  <button class="btn-action btn-role" onclick="openChangeRoleModal(<?php echo $user['User_Id']; ?>, '<?php echo htmlspecialchars(addslashes($user['First_Name'] . ' ' . $user['Last_Name'])); ?>', '<?php echo $user['Role']; ?>')">
                    <i class="bi bi-person-gear"></i> Change Role
                  </button>
                  
                  <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="user_id" value="<?php echo $user['User_Id']; ?>">
                    <button type="submit" class="btn-action btn-delete" onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone!')">
                      <i class="bi bi-trash"></i> Delete
                    </button>
                  </form>
                  
                <?php else: ?>
                  <span style="color: #999; font-size: 13px;"><i class="bi bi-person-check"></i> You (Current Admin)</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php 
              endwhile;
            else:
            ?>
            <tr>
              <td colspan="10" style="text-align: center; padding: 40px; color: #999;">
                <i class="bi bi-search" style="font-size: 48px; display: block; margin-bottom: 15px;"></i>
                <strong>No users found</strong>
                <br>
                <small>Try adjusting your search or filters</small>
              </td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      
    </div>
  </section>
</main>

<div id="resetPasswordModal" class="modal">
  <div class="modal-content">
    <div class="modal-header">
      <span class="close" onclick="closeResetPasswordModal()">&times;</span>
      <h3><i class="bi bi-key"></i> Reset Password</h3>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="reset_password">
      <input type="hidden" name="user_id" id="reset_user_id">
      
      <div class="form-group">
        <label>User</label>
        <input type="text" id="reset_user_name" readonly style="background: #f8f9fa;">
      </div>
      
      <div class="form-group">
        <label>New Password</label>
        <input type="password" name="new_password" id="new_password" placeholder="Enter new password (min 8 characters)" required minlength="8">
      </div>
      
      <div class="form-group">
        <label>Confirm Password</label>
        <input type="password" id="confirm_password" placeholder="Confirm new password" required>
        <small id="password_match" style="color: red; display: none;">Passwords do not match</small>
      </div>
      
      <button type="submit" class="btn-submit" id="submit_reset">
        <i class="bi bi-key"></i> Reset Password
      </button>
    </form>
  </div>
</div>

<div id="passwordIntervalModal" class="modal">
  <div class="modal-content">
    <div class="modal-header">
      <span class="close" onclick="closePasswordIntervalModal()">&times;</span>
      <h3><i class="bi bi-clock-history"></i> Set Password Reset Interval</h3>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="set_password_interval">
      <input type="hidden" name="user_id" id="interval_user_id">
      
      <div class="form-group">
        <label>User</label>
        <input type="text" id="interval_user_name" readonly style="background: #f8f9fa;">
      </div>
      
      <div class="form-group">
        <label>Password Reset Interval (Days)</label>
        <input type="number" name="interval_days" id="interval_days" placeholder="Enter number of days" required min="1" max="365">
        <small style="color: #666; font-size: 12px;">User will be required to reset their password after this many days</small>
      </div>
      
      <div style="background: #fff3cd; padding: 12px; border-radius: 5px; margin-bottom: 20px; font-size: 13px;">
        <i class="bi bi-info-circle"></i> <strong>Note:</strong> The expiration date will be calculated from today. The user will be prompted to reset their password when the interval expires.
      </div>
      
      <button type="submit" class="btn-submit">
        <i class="bi bi-clock-history"></i> Set Interval
      </button>
    </form>
  </div>
</div>

<div id="changeRoleModal" class="modal">
  <div class="modal-content">
    <div class="modal-header">
      <span class="close" onclick="closeChangeRoleModal()">&times;</span>
      <h3><i class="bi bi-person-gear"></i> Change User Role</h3>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="change_role">
      <input type="hidden" name="user_id" id="role_user_id">
      
      <div class="form-group">
        <label>User</label>
        <input type="text" id="role_user_name" readonly style="background: #f8f9fa;">
      </div>
      
      <div class="form-group">
        <label>New Role</label>
        <select name="new_role" id="new_role" required>
          <option value="">Select Role</option>
          <option value="teacher">Teacher (requires approval for admin access)</option>
          <option value="student">Student</option>
          <option value="parent">Parent</option>
          <option value="other">Other</option>
        </select>
      </div>
      
      <p style="background: #fff3cd; padding: 10px; border-radius: 5px; font-size: 13px; color: #856404;">
        <i class="bi bi-exclamation-triangle"></i> <strong>Note:</strong> Changing role to "teacher" will require admin approval before they can access admin features.
      </p>
      
      <button type="submit" class="btn-submit" onclick="return confirm('Are you sure you want to change this user\'s role?')">
        <i class="bi bi-person-gear"></i> Update Role
      </button>
    </form>
  </div>
</div>

<?php 
closeDBConnection($conn);
include('components/footer.php'); 
?>

<a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

<script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/vendor/aos/aos.js"></script>
<script src="assets/js/main.js"></script>

<script>
function openResetPasswordModal(userId, userName) {
  document.getElementById('reset_user_id').value = userId;
  document.getElementById('reset_user_name').value = userName;
  document.getElementById('new_password').value = '';
  document.getElementById('confirm_password').value = '';
  document.getElementById('password_match').style.display = 'none';
  document.getElementById('resetPasswordModal').style.display = 'block';
}

function closeResetPasswordModal() {
  document.getElementById('resetPasswordModal').style.display = 'none';
}

document.getElementById('confirm_password').addEventListener('input', function() {
  const newPass = document.getElementById('new_password').value;
  const confirmPass = this.value;
  const matchMsg = document.getElementById('password_match');
  const submitBtn = document.getElementById('submit_reset');
  
  if (newPass !== confirmPass) {
    matchMsg.style.display = 'block';
    submitBtn.disabled = true;
    submitBtn.style.opacity = '0.5';
  } else {
    matchMsg.style.display = 'none';
    submitBtn.disabled = false;
    submitBtn.style.opacity = '1';
  }
});

function openPasswordIntervalModal(userId, userName, currentInterval) {
  document.getElementById('interval_user_id').value = userId;
  document.getElementById('interval_user_name').value = userName;
  document.getElementById('interval_days').value = currentInterval || '';
  document.getElementById('passwordIntervalModal').style.display = 'block';
}

function closePasswordIntervalModal() {
  document.getElementById('passwordIntervalModal').style.display = 'none';
}

function openChangeRoleModal(userId, userName, currentRole) {
  document.getElementById('role_user_id').value = userId;
  document.getElementById('role_user_name').value = userName;
  document.getElementById('new_role').value = currentRole;
  document.getElementById('changeRoleModal').style.display = 'block';
}

function closeChangeRoleModal() {
  document.getElementById('changeRoleModal').style.display = 'none';
}

window.onclick = function(event) {
  const resetModal = document.getElementById('resetPasswordModal');
  const intervalModal = document.getElementById('passwordIntervalModal');
  const roleModal = document.getElementById('changeRoleModal');
  
  if (event.target == resetModal) {
    closeResetPasswordModal();
  }
  if (event.target == intervalModal) {
    closePasswordIntervalModal();
  }
  if (event.target == roleModal) {
    closeChangeRoleModal();
  }
}

AOS.init({
  duration: 800,
  easing: 'ease-in-out',
  once: true
});
</script>

</body>
</html>