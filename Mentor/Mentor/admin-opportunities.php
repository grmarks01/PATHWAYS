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

// Include database configuration
require_once 'components/db-config.php';
$conn = getDBConnection();

// Handle actions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        // Verify password for edit and delete actions
        if (($action === 'edit' || $action === 'delete') && isset($_POST['confirm_password'])) {
            $user_id = $_SESSION['user_id'];
            $confirm_password = $_POST['confirm_password'];
            
            // Get user's hashed password
            $stmt = $conn->prepare("SELECT Hash FROM users WHERE User_Id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();
            
            if (!password_verify($confirm_password, $user['Hash'])) {
                $message = "Incorrect password. Action cancelled.";
                $message_type = "error";
                $action = null;
            }
        }
        
        // Add new opportunity
        if ($action === 'add') {
            $display_name = !empty($_POST['display_name']) ? $_POST['display_name'] : null;
            $state = $_POST['state'];
            $program_name = $_POST['program_name'];
            $grade_levels = !empty($_POST['grade_levels']) ? $_POST['grade_levels'] : null;
            $eligibility = !empty($_POST['eligibility']) ? $_POST['eligibility'] : null;
            $cost_funding = !empty($_POST['cost_funding']) ? $_POST['cost_funding'] : null;
            $deadlines = !empty($_POST['deadlines']) ? $_POST['deadlines'] : null;
            $website_link = !empty($_POST['website_link']) ? $_POST['website_link'] : null;
            $category = !empty($_POST['category']) ? $_POST['category'] : null;
            $field = !empty($_POST['field']) ? $_POST['field'] : null;
            $delivery_context = !empty($_POST['delivery_context']) ? $_POST['delivery_context'] : null;
            $notes = !empty($_POST['notes']) ? $_POST['notes'] : null;
            
            $stmt = $conn->prepare("INSERT INTO pathways_opportunities (display_name, state, program_name, grade_levels, eligibility, cost_funding, deadlines, website_link, category, field, delivery_context, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssdssssss", $display_name, $state, $program_name, $grade_levels, $eligibility, $cost_funding, $deadlines, $website_link, $category, $field, $delivery_context, $notes);
            
            if ($stmt->execute()) {
                $message = "Opportunity added successfully!";
                $message_type = "success";
            } else {
                $message = "Error adding opportunity: " . $stmt->error;
                $message_type = "error";
            }
            $stmt->close();
        }
        
        // Edit existing opportunity
        if ($action === 'edit' && $message_type !== 'error') {
            $id = intval($_POST['opportunity_id']);
            $display_name = !empty($_POST['display_name']) ? $_POST['display_name'] : null;
            $state = $_POST['state'];
            $program_name = $_POST['program_name'];
            $grade_levels = !empty($_POST['grade_levels']) ? $_POST['grade_levels'] : null;
            $eligibility = !empty($_POST['eligibility']) ? $_POST['eligibility'] : null;
            $cost_funding = !empty($_POST['cost_funding']) ? $_POST['cost_funding'] : null;
            $deadlines = !empty($_POST['deadlines']) ? $_POST['deadlines'] : null;
            $website_link = !empty($_POST['website_link']) ? $_POST['website_link'] : null;
            $category = !empty($_POST['category']) ? $_POST['category'] : null;
            $field = !empty($_POST['field']) ? $_POST['field'] : null;
            $delivery_context = !empty($_POST['delivery_context']) ? $_POST['delivery_context'] : null;
            $notes = !empty($_POST['notes']) ? $_POST['notes'] : null;
            
            $stmt = $conn->prepare("UPDATE pathways_opportunities SET display_name = ?, state = ?, program_name = ?, grade_levels = ?, eligibility = ?, cost_funding = ?, deadlines = ?, website_link = ?, category = ?, field = ?, delivery_context = ?, notes = ? WHERE id = ?");
            $stmt->bind_param("sssssdssssssi", $display_name, $state, $program_name, $grade_levels, $eligibility, $cost_funding, $deadlines, $website_link, $category, $field, $delivery_context, $notes, $id);
            
            if ($stmt->execute()) {
                $message = "Opportunity updated successfully!";
                $message_type = "success";
            } else {
                $message = "Error updating opportunity: " . $stmt->error;
                $message_type = "error";
            }
            $stmt->close();
        }
        
        // Delete opportunity
        if ($action === 'delete' && $message_type !== 'error') {
            $id = intval($_POST['opportunity_id']);
            
            $stmt = $conn->prepare("DELETE FROM pathways_opportunities WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $message = "Opportunity deleted successfully!";
                $message_type = "success";
            } else {
                $message = "Error deleting opportunity: " . $stmt->error;
                $message_type = "error";
            }
            $stmt->close();
        }
    }
}

// Get filter parameters
$search_keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$search_state = isset($_GET['state']) ? trim($_GET['state']) : '';
$search_category = isset($_GET['category']) ? trim($_GET['category']) : '';
$search_deadline = isset($_GET['deadline']) ? trim($_GET['deadline']) : '';
$search_grade = isset($_GET['grade']) ? trim($_GET['grade']) : '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Function to check if a grade matches the grade_levels string
function grade_matches($grade_levels, $target_grade) {
    if (empty($grade_levels)) {
        return false;
    }
    
    // Remove common unicode dashes and replace with standard dash
    $grade_levels = str_replace(array('-', '–', '—'), '-', $grade_levels);
    
    // Extract all numbers from the string
    preg_match_all('/\d+/', $grade_levels, $matches);
    $numbers = array_map('intval', $matches[0]);
    
    if (empty($numbers)) {
        return false;
    }
    
    // If only one number, check if it matches
    if (count($numbers) === 1) {
        return $numbers[0] == $target_grade;
    }
    
    // If multiple numbers, assume it's a range (first to last)
    $min_grade = min($numbers);
    $max_grade = max($numbers);
    
    return $target_grade >= $min_grade && $target_grade <= $max_grade;
}

// Build query with filters
$sql = "SELECT * FROM pathways_opportunities WHERE 1=1";
$params = [];
$types = "";

if (!empty($search_keyword)) {
    $sql .= " AND (program_name LIKE ? OR notes LIKE ? OR field LIKE ? OR eligibility LIKE ?)";
    $keyword_param = "%$search_keyword%";
    $params[] = $keyword_param;
    $params[] = $keyword_param;
    $params[] = $keyword_param;
    $params[] = $keyword_param;
    $types .= "ssss";
}

if (!empty($search_state)) {
    $sql .= " AND state = ?";
    $params[] = $search_state;
    $types .= "s";
}

if (!empty($search_category)) {
    $sql .= " AND category = ?";
    $params[] = $search_category;
    $types .= "s";
}

if (!empty($search_deadline)) {
    if ($search_deadline === 'upcoming') {
        $sql .= " AND deadlines IS NOT NULL AND deadlines >= CURDATE()";
    } elseif ($search_deadline === 'this_month') {
        $sql .= " AND deadlines IS NOT NULL AND MONTH(deadlines) = MONTH(CURDATE()) AND YEAR(deadlines) = YEAR(CURDATE())";
    } elseif ($search_deadline === 'next_3_months') {
        $sql .= " AND deadlines IS NOT NULL AND deadlines BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 MONTH)";
    } elseif ($search_deadline === 'rolling') {
        $sql .= " AND (deadlines IS NULL OR deadlines = '')";
    } elseif ($search_deadline === 'spring') {
        $sql .= " AND deadlines IS NOT NULL AND MONTH(deadlines) IN (3, 4, 5)";
    } elseif ($search_deadline === 'summer') {
        $sql .= " AND deadlines IS NOT NULL AND MONTH(deadlines) IN (6, 7, 8)";
    } elseif ($search_deadline === 'fall') {
        $sql .= " AND deadlines IS NOT NULL AND MONTH(deadlines) IN (9, 10, 11)";
    } elseif ($search_deadline === 'winter') {
        $sql .= " AND deadlines IS NOT NULL AND MONTH(deadlines) IN (12, 1, 2)";
    }
}

$sql .= " ORDER BY state, program_name LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// If grade filter is applied, we need to post-process to handle ranges properly
$opportunities_array = [];
if (!empty($search_grade)) {
    $grade_num = (int)$search_grade;
    while ($row = $result->fetch_assoc()) {
        if (grade_matches($row['grade_levels'], $grade_num)) {
            $opportunities_array[] = $row;
        }
    }
} else {
    while ($row = $result->fetch_assoc()) {
        $opportunities_array[] = $row;
    }
}

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total FROM pathways_opportunities WHERE 1=1";
$count_params = [];
$count_types = "";

if (!empty($search_keyword)) {
    $count_sql .= " AND (program_name LIKE ? OR notes LIKE ? OR field LIKE ? OR eligibility LIKE ?)";
    $keyword_param = "%$search_keyword%";
    $count_params[] = $keyword_param;
    $count_params[] = $keyword_param;
    $count_params[] = $keyword_param;
    $count_params[] = $keyword_param;
    $count_types .= "ssss";
}

if (!empty($search_state)) {
    $count_sql .= " AND state = ?";
    $count_params[] = $search_state;
    $count_types .= "s";
}

if (!empty($search_category)) {
    $count_sql .= " AND category = ?";
    $count_params[] = $search_category;
    $count_types .= "s";
}

if (!empty($search_deadline)) {
    if ($search_deadline === 'upcoming') {
        $count_sql .= " AND deadlines IS NOT NULL AND deadlines >= CURDATE()";
    } elseif ($search_deadline === 'this_month') {
        $count_sql .= " AND deadlines IS NOT NULL AND MONTH(deadlines) = MONTH(CURDATE()) AND YEAR(deadlines) = YEAR(CURDATE())";
    } elseif ($search_deadline === 'next_3_months') {
        $count_sql .= " AND deadlines IS NOT NULL AND deadlines BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 MONTH)";
    } elseif ($search_deadline === 'rolling') {
        $count_sql .= " AND (deadlines IS NULL OR deadlines = '')";
    } elseif ($search_deadline === 'spring') {
        $count_sql .= " AND deadlines IS NOT NULL AND MONTH(deadlines) IN (3, 4, 5)";
    } elseif ($search_deadline === 'summer') {
        $count_sql .= " AND deadlines IS NOT NULL AND MONTH(deadlines) IN (6, 7, 8)";
    } elseif ($search_deadline === 'fall') {
        $count_sql .= " AND deadlines IS NOT NULL AND MONTH(deadlines) IN (9, 10, 11)";
    } elseif ($search_deadline === 'winter') {
        $count_sql .= " AND deadlines IS NOT NULL AND MONTH(deadlines) IN (12, 1, 2)";
    }
}

$count_stmt = $conn->prepare($count_sql);
if (!empty($count_params)) {
    $count_stmt->bind_param($count_types, ...$count_params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();

// If grade filter, count by checking each row
if (!empty($search_grade)) {
    $temp_count = 0;
    $grade_num = (int)$search_grade;
    
    // Get all matching records to filter by grade
    $temp_sql = str_replace("COUNT(*) as total", "*", $count_sql);
    $temp_stmt = $conn->prepare($temp_sql);
    if (!empty($count_params)) {
        $temp_stmt->bind_param($count_types, ...$count_params);
    }
    $temp_stmt->execute();
    $temp_result = $temp_stmt->get_result();
    
    while ($row = $temp_result->fetch_assoc()) {
        if (grade_matches($row['grade_levels'], $grade_num)) {
            $temp_count++;
        }
    }
    $total_results = $temp_count;
    $temp_stmt->close();
} else {
    $total_results = $count_result->fetch_assoc()['total'];
}

$total_pages = ceil($total_results / $per_page);
$count_stmt->close();

// Define all US states
$all_states = array();
$all_states[] = 'Alabama';
$all_states[] = 'Alaska';
$all_states[] = 'Arizona';
$all_states[] = 'Arkansas';
$all_states[] = 'California';
$all_states[] = 'Colorado';
$all_states[] = 'Connecticut';
$all_states[] = 'Delaware';
$all_states[] = 'Florida';
$all_states[] = 'Georgia';
$all_states[] = 'Hawaii';
$all_states[] = 'Idaho';
$all_states[] = 'Illinois';
$all_states[] = 'Indiana';
$all_states[] = 'Iowa';
$all_states[] = 'Kansas';
$all_states[] = 'Kentucky';
$all_states[] = 'Louisiana';
$all_states[] = 'Maine';
$all_states[] = 'Maryland';
$all_states[] = 'Massachusetts';
$all_states[] = 'Michigan';
$all_states[] = 'Minnesota';
$all_states[] = 'Mississippi';
$all_states[] = 'Missouri';
$all_states[] = 'Montana';
$all_states[] = 'Nebraska';
$all_states[] = 'Nevada';
$all_states[] = 'New Hampshire';
$all_states[] = 'New Jersey';
$all_states[] = 'New Mexico';
$all_states[] = 'New York';
$all_states[] = 'North Carolina';
$all_states[] = 'North Dakota';
$all_states[] = 'Ohio';
$all_states[] = 'Oklahoma';
$all_states[] = 'Oregon';
$all_states[] = 'Pennsylvania';
$all_states[] = 'Rhode Island';
$all_states[] = 'South Carolina';
$all_states[] = 'South Dakota';
$all_states[] = 'Tennessee';
$all_states[] = 'Texas';
$all_states[] = 'Utah';
$all_states[] = 'Vermont';
$all_states[] = 'Virginia';
$all_states[] = 'Washington';
$all_states[] = 'West Virginia';
$all_states[] = 'Wisconsin';
$all_states[] = 'Wyoming';

$categories_result = $conn->query("SELECT DISTINCT category FROM pathways_opportunities WHERE category IS NOT NULL ORDER BY category");

$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Opportunities Management - Pathways</title>

  <link href="assets/img/favicon.png" rel="icon">
  <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&family=Raleway:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&display=swap" rel="stylesheet">
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="assets/css/main.css" rel="stylesheet">

  <style>
    .admin-section {
      padding: 80px 0 60px;
      min-height: 100vh;
    }
    
    .admin-header {
      background: linear-gradient(135deg, #5fcf80 0%, #4ab86a 100%);
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
    
    .action-bar {
      background: white;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 0 20px rgba(0, 0, 0, 0.08);
      margin-bottom: 30px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    
    .btn-add {
      padding: 12px 25px;
      background: #5fcf80;
      color: white;
      border: none;
      border-radius: 5px;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.3s;
    }
    
    .btn-add:hover {
      background: #4ab86a;
    }
    
    .btn-clear {
      padding: 12px 25px;
      background: #fff;
      color: #666;
      border: 2px solid #ddd;
      border-radius: 5px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
      text-decoration: none;
      display: inline-block;
    }
    
    .btn-clear:hover {
      border-color: #999;
      color: #333;
    }
    
    .search-filters {
      background: white;
      padding: 25px;
      border-radius: 10px;
      box-shadow: 0 0 20px rgba(0, 0, 0, 0.08);
      margin-bottom: 30px;
    }
    
    .filter-grid {
      display: grid;
      grid-template-columns: 2fr 1fr 1fr 1fr 1fr auto;
      gap: 15px;
      align-items: end;
    }
    
    @media (max-width: 992px) {
      .filter-grid {
        grid-template-columns: 1fr;
      }
    }
    
    .form-group {
      margin-bottom: 0;
    }
    
    .form-group label {
      display: block;
      margin-bottom: 8px;
      color: #333;
      font-weight: 600;
      font-size: 14px;
    }
    
    .form-group input,
    .form-group select,
    .form-group textarea {
      width: 100%;
      padding: 12px 15px;
      border: 1px solid #ddd;
      border-radius: 5px;
      font-size: 15px;
    }
    
    .form-group textarea {
      min-height: 100px;
      resize: vertical;
    }
    
    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
      outline: none;
      border-color: #5fcf80;
    }
    
    .opportunities-table-container {
      background: white;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 0 20px rgba(0, 0, 0, 0.08);
      overflow-x: auto;
    }
    
    .opportunities-table {
      width: 100%;
      border-collapse: collapse;
    }
    
    .opportunities-table th,
    .opportunities-table td {
      padding: 15px;
      text-align: left;
      border-bottom: 1px solid #f0f0f0;
    }
    
    .opportunities-table th {
      background: #f8f9fa;
      font-weight: 600;
      color: #333;
      position: sticky;
      top: 0;
    }
    
    .opportunities-table tr:hover {
      background: #f8f9fa;
    }
    
    .badge {
      padding: 5px 12px;
      border-radius: 20px;
      font-size: 11px;
      font-weight: 600;
    }
    
    .badge-club { background: #5fcf80; color: white; }
    .badge-scholarship { background: #ffd700; color: #333; }
    .badge-competition { background: #667eea; color: white; }
    .badge-academic-program { background: #f093fb; color: white; }
    .badge-program { background: #764ba2; color: white; }
    .badge-default { background: #6c757d; color: white; }
    
    .btn-action {
      padding: 6px 12px;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      font-size: 12px;
      font-weight: 600;
      transition: all 0.3s;
      margin: 2px;
    }
    
    .btn-edit {
      background: #17a2b8;
      color: white;
    }
    
    .btn-edit:hover {
      background: #138496;
    }
    
    .btn-delete {
      background: #dc3545;
      color: white;
    }
    
    .btn-delete:hover {
      background: #c82333;
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
      overflow-y: auto;
    }
    
    .modal-content {
      background: white;
      margin: 50px auto;
      padding: 30px;
      border-radius: 10px;
      width: 90%;
      max-width: 800px;
      box-shadow: 0 5px 30px rgba(0, 0, 0, 0.3);
    }
    
    .modal-header {
      margin-bottom: 25px;
      padding-bottom: 15px;
      border-bottom: 2px solid #f0f0f0;
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
    
    .form-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
      margin-bottom: 20px;
    }
    
    .form-grid .form-group-full {
      grid-column: 1 / -1;
    }
    
    @media (max-width: 768px) {
      .form-grid {
        grid-template-columns: 1fr;
      }
    }
	
	/* Responsive table for mobile */

@media (max-width: 768px) {
  .opportunities-table-container {
    padding: 15px;
    overflow-x: visible;
  }
  
  .opportunities-table {
    font-size: 13px;
  }
  
  .opportunities-table th,
  .opportunities-table td {
    padding: 10px 8px;
  }
  
  /* Hide less important columns on mobile */
  .opportunities-table th:nth-child(1),
  .opportunities-table td:nth-child(1) {
    display: none; /* Hide ID */
  }
  
  .opportunities-table th:nth-child(5),
  .opportunities-table td:nth-child(5) {
    display: none; /* Hide Field */
  }
  
  .opportunities-table th:nth-child(6),
  .opportunities-table td:nth-child(6) {
    display: none; /* Hide Grade Levels */
  }
}

@media (max-width: 480px) {
  .opportunities-table-container {
    padding: 10px;
  }
  
  .opportunities-table {
    font-size: 12px;
  }
  
  .opportunities-table th,
  .opportunities-table td {
    padding: 8px 6px;
  }
  
  /* Show minimal columns on very small phones */
  .opportunities-table th:nth-child(1),
  .opportunities-table td:nth-child(1) {
    display: none; /* Hide ID */
  }
  
  .opportunities-table th:nth-child(5),
  .opportunities-table td:nth-child(5) {
    display: none; /* Hide Field */
  }
  
  .opportunities-table th:nth-child(6),
  .opportunities-table td:nth-child(6) {
    display: none; /* Hide Grade Levels */
  }
  
  .opportunities-table th:nth-child(7),
  .opportunities-table td:nth-child(7) {
    display: none; /* Hide Deadline */
  }
  
  /* Stack action buttons */
  .btn-action {
    padding: 5px 8px;
    font-size: 11px;
    display: block;
    width: 100%;
    margin: 3px 0;
  }
}
    
    .btn-submit {
      width: 100%;
      padding: 12px;
      background: #5fcf80;
      color: white;
      border: none;
      border-radius: 5px;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.3s;
      margin-top: 10px;
    }
    
    .btn-submit:hover {
      background: #4ab86a;
    }
    
    .password-confirm {
      background: #fff3cd;
      padding: 15px;
      border-radius: 5px;
      border-left: 4px solid #ffc107;
      margin-top: 20px;
    }
    
    .password-confirm label {
      font-weight: 600;
      color: #856404;
    }
    
    .pagination {
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 10px;
      margin-top: 30px;
      padding: 20px;
      background: white;
      border-radius: 10px;
      box-shadow: 0 0 20px rgba(0, 0, 0, 0.08);
    }
    
    .pagination a,
    .pagination span {
      padding: 10px 15px;
      border: 1px solid #ddd;
      border-radius: 5px;
      text-decoration: none;
      color: #333;
      transition: all 0.3s;
    }
    
    .pagination a:hover {
      background: #5fcf80;
      color: white;
      border-color: #5fcf80;
    }
    
    .pagination .current {
      background: #5fcf80;
      color: white;
      border-color: #5fcf80;
      font-weight: 600;
    }
    
    .pagination .disabled {
      opacity: 0.5;
      cursor: not-allowed;
      pointer-events: none;
    }
  </style>
</head>

<body>

<?php include('components/header.php'); ?>

<main class="main">
  <section class="admin-section">
    <div class="container">
      
      <div class="admin-header" data-aos="fade-down">
        <h1><i class="bi bi-briefcase"></i> Opportunities Management</h1>
        <p>Add, edit, and manage educational opportunities</p>
      </div>
      
      <?php if ($message): ?>
      <div class="alert alert-<?php echo $message_type; ?>" data-aos="fade-up">
        <i class="bi bi-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
        <?php echo htmlspecialchars($message); ?>
      </div>
      <?php endif; ?>
      
      <div class="action-bar" data-aos="fade-up">
        <div>
          <strong><?php echo $total_results; ?></strong> opportunities total
          <?php if ($total_pages > 1): ?>
            (Page <?php echo $page; ?> of <?php echo $total_pages; ?>)
          <?php endif; ?>
        </div>
        <button class="btn-add" onclick="openAddModal()">
          <i class="bi bi-plus-circle"></i> Add New Opportunity
        </button>
      </div>
      
      <div class="search-filters" data-aos="fade-up">
        <form method="GET" action="">
          <div class="filter-grid">
            <div class="form-group">
              <label>Keyword Search</label>
              <input type="text" name="keyword" placeholder="Search by program name, field, or notes..." value="<?php echo htmlspecialchars($search_keyword); ?>">
            </div>
            
            <div class="form-group">
              <label>State</label>
              <select name="state">
                <option value="">All States</option>
                <?php foreach ($all_states as $state): ?>
                  <option value="<?php echo htmlspecialchars($state); ?>" <?php echo ($search_state === $state) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($state); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            
            <div class="form-group">
              <label>Category</label>
              <select name="category">
                <option value="">All Categories</option>
                <?php while ($cat = $categories_result->fetch_assoc()): ?>
                  <option value="<?php echo htmlspecialchars($cat['category']); ?>" <?php echo ($search_category === $cat['category']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($cat['category']); ?>
                  </option>
                <?php endwhile; ?>
              </select>
            </div>
            
            <div class="form-group">
              <label>Grade Level</label>
              <select name="grade">
                <option value="">All Grades</option>
                <option value="6" <?php echo ($search_grade === '6') ? 'selected' : ''; ?>>6th Grade</option>
                <option value="7" <?php echo ($search_grade === '7') ? 'selected' : ''; ?>>7th Grade</option>
                <option value="8" <?php echo ($search_grade === '8') ? 'selected' : ''; ?>>8th Grade</option>
                <option value="9" <?php echo ($search_grade === '9') ? 'selected' : ''; ?>>9th Grade</option>
                <option value="10" <?php echo ($search_grade === '10') ? 'selected' : ''; ?>>10th Grade</option>
                <option value="11" <?php echo ($search_grade === '11') ? 'selected' : ''; ?>>11th Grade</option>
                <option value="12" <?php echo ($search_grade === '12') ? 'selected' : ''; ?>>12th Grade</option>
              </select>
            </div>
            
            <div class="form-group">
              <label>Deadline</label>
              <select name="deadline">
                <option value="">All Deadlines</option>
                <option value="upcoming" <?php echo ($search_deadline === 'upcoming') ? 'selected' : ''; ?>>Upcoming</option>
                <option value="this_month" <?php echo ($search_deadline === 'this_month') ? 'selected' : ''; ?>>This Month</option>
                <option value="next_3_months" <?php echo ($search_deadline === 'next_3_months') ? 'selected' : ''; ?>>Next 3 Months</option>
                <option value="rolling" <?php echo ($search_deadline === 'rolling') ? 'selected' : ''; ?>>Rolling Deadlines</option>
                <option value="" disabled>──────────</option>
                <option value="spring" <?php echo ($search_deadline === 'spring') ? 'selected' : ''; ?>>Spring (Mar-May)</option>
                <option value="summer" <?php echo ($search_deadline === 'summer') ? 'selected' : ''; ?>>Summer (Jun-Aug)</option>
                <option value="fall" <?php echo ($search_deadline === 'fall') ? 'selected' : ''; ?>>Fall (Sep-Nov)</option>
                <option value="winter" <?php echo ($search_deadline === 'winter') ? 'selected' : ''; ?>>Winter (Dec-Feb)</option>
              </select>
            </div>
            
            <div class="form-group">
              <label>&nbsp;</label>
              <button type="submit" class="btn-add" style="width: 100%;">
                <i class="bi bi-search"></i> Filter
              </button>
            </div>
          </div>
          
          <?php if (!empty($search_keyword) || !empty($search_state) || !empty($search_category) || !empty($search_deadline) || !empty($search_grade)): ?>
          <div style="margin-top: 15px;">
            <a href="admin-opportunities.php" class="btn-clear">
              <i class="bi bi-x-circle"></i> Clear Filters
            </a>
          </div>
          <?php endif; ?>
        </form>
      </div>
      
      <div class="opportunities-table-container" data-aos="fade-up">
        <table class="opportunities-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Program Name</th>
              <th>State</th>
              <th>Category</th>
              <th>Field</th>
              <th>Grade Levels</th>
              <th>Deadline</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($opportunities_array as $opp): ?>
            <tr>
              <td><?php echo $opp['id']; ?></td>
              <td><strong><?php echo htmlspecialchars($opp['program_name']); ?></strong></td>
              <td><?php echo htmlspecialchars($opp['state']); ?></td>
              <td>
                <?php if ($opp['category']): 
                  $category_class = 'badge-default';
                  $category = $opp['category'];
                  if ($category === 'Club') $category_class = 'badge-club';
                  elseif ($category === 'Scholarship') $category_class = 'badge-scholarship';
                  elseif ($category === 'Competition') $category_class = 'badge-competition';
                  elseif ($category === 'Academic Program') $category_class = 'badge-academic-program';
                  elseif ($category === 'Program') $category_class = 'badge-program';
                ?>
                  <span class="badge <?php echo $category_class; ?>"><?php echo htmlspecialchars($opp['category']); ?></span>
                <?php else: ?>
                  <span style="color: #999;">N/A</span>
                <?php endif; ?>
              </td>
              <td><?php echo htmlspecialchars($opp['field'] ?: 'N/A'); ?></td>
              <td><?php echo htmlspecialchars($opp['grade_levels'] ?: 'N/A'); ?></td>
              <td><?php echo $opp['deadlines'] ? date('M d, Y', strtotime($opp['deadlines'])) : '<span style="color: #999;">Rolling</span>'; ?></td>
              <td>
                <button class="btn-action btn-edit" onclick='openEditModal(<?php echo htmlspecialchars(json_encode($opp), ENT_QUOTES, 'UTF-8'); ?>)'>
                  <i class="bi bi-pencil"></i> Edit
                </button>
                <button class="btn-action btn-delete" onclick="openDeleteModal(<?php echo $opp['id']; ?>, <?php echo htmlspecialchars(json_encode($opp['program_name']), ENT_QUOTES, 'UTF-8'); ?>)">
                  <i class="bi bi-trash"></i> Delete
                </button>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      
      <?php if ($total_pages > 1): ?>
        <div class="pagination">
          <?php if ($page > 1): ?>
            <a href="?keyword=<?php echo urlencode($search_keyword); ?>&state=<?php echo urlencode($search_state); ?>&category=<?php echo urlencode($search_category); ?>&grade=<?php echo urlencode($search_grade); ?>&deadline=<?php echo urlencode($search_deadline); ?>&page=<?php echo ($page - 1); ?>">
              <i class="bi bi-chevron-left"></i> Previous
            </a>
          <?php else: ?>
            <span class="disabled"><i class="bi bi-chevron-left"></i> Previous</span>
          <?php endif; ?>
          
          <?php
          $start_page = max(1, $page - 2);
          $end_page = min($total_pages, $page + 2);
          
          if ($start_page > 1): ?>
            <a href="?keyword=<?php echo urlencode($search_keyword); ?>&state=<?php echo urlencode($search_state); ?>&category=<?php echo urlencode($search_category); ?>&grade=<?php echo urlencode($search_grade); ?>&deadline=<?php echo urlencode($search_deadline); ?>&page=1">1</a>
            <?php if ($start_page > 2): ?><span>...</span><?php endif; ?>
          <?php endif; ?>
          
          <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
            <?php if ($i == $page): ?>
              <span class="current"><?php echo $i; ?></span>
            <?php else: ?>
              <a href="?keyword=<?php echo urlencode($search_keyword); ?>&state=<?php echo urlencode($search_state); ?>&category=<?php echo urlencode($search_category); ?>&grade=<?php echo urlencode($search_grade); ?>&deadline=<?php echo urlencode($search_deadline); ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
            <?php endif; ?>
          <?php endfor; ?>
          
          <?php if ($end_page < $total_pages): ?>
            <?php if ($end_page < $total_pages - 1): ?><span>...</span><?php endif; ?>
            <a href="?keyword=<?php echo urlencode($search_keyword); ?>&state=<?php echo urlencode($search_state); ?>&category=<?php echo urlencode($search_category); ?>&grade=<?php echo urlencode($search_grade); ?>&deadline=<?php echo urlencode($search_deadline); ?>&page=<?php echo $total_pages; ?>"><?php echo $total_pages; ?></a>
          <?php endif; ?>
          
          <?php if ($page < $total_pages): ?>
            <a href="?keyword=<?php echo urlencode($search_keyword); ?>&state=<?php echo urlencode($search_state); ?>&category=<?php echo urlencode($search_category); ?>&grade=<?php echo urlencode($search_grade); ?>&deadline=<?php echo urlencode($search_deadline); ?>&page=<?php echo ($page + 1); ?>">
              Next <i class="bi bi-chevron-right"></i>
            </a>
          <?php else: ?>
            <span class="disabled">Next <i class="bi bi-chevron-right"></i></span>
          <?php endif; ?>
        </div>
      <?php endif; ?>
      
    </div>
  </section>
</main>

<!-- Add Modal -->
<div id="addModal" class="modal">
  <div class="modal-content">
    <div class="modal-header">
      <span class="close" onclick="closeAddModal()">&times;</span>
      <h3><i class="bi bi-plus-circle"></i> Add New Opportunity</h3>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="add">
      
      <div class="form-grid">
        <div class="form-group form-group-full">
          <label>Program Name *</label>
          <input type="text" name="program_name" required>
        </div>
        
        <div class="form-group">
          <label>Display Name</label>
          <input type="text" name="display_name">
        </div>
        
        <div class="form-group">
          <label>State *</label>
          <select name="state" required>
            <option value="">Select State</option>
            <?php foreach ($all_states as $state): ?>
              <option value="<?php echo htmlspecialchars($state); ?>"><?php echo htmlspecialchars($state); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        
        <div class="form-group">
          <label>Category</label>
          <select name="category">
            <option value="">Select Category</option>
            <option value="Club">Club</option>
            <option value="Scholarship">Scholarship</option>
            <option value="Competition">Competition</option>
            <option value="Academic Program">Academic Program</option>
            <option value="Program">Program</option>
            <option value="Athletics/Sports">Athletics/Sports</option>
          </select>
        </div>
        
        <div class="form-group">
          <label>Field</label>
          <select name="field">
            <option value="">Select Field</option>
            <option value="Academic">Academic</option>
            <option value="Arts">Arts</option>
            <option value="Athletics/Sports">Athletics/Sports</option>
            <option value="Business & Entrepreneurship">Business & Entrepreneurship</option>
            <option value="General Academic">General Academic</option>
            <option value="Leadership & Community Service">Leadership & Community Service</option>
            <option value="Math">Math</option>
            <option value="Science">Science</option>
            <option value="Social Studies & Humanities">Social Studies & Humanities</option>
            <option value="Speech & Debate">Speech & Debate</option>
            <option value="STEM">STEM</option>
          </select>
        </div>
        
        <div class="form-group">
          <label>Grade Levels</label>
          <input type="text" name="grade_levels" placeholder="e.g., 9-12">
        </div>
        
        <div class="form-group">
          <label>Cost/Funding</label>
          <input type="number" name="cost_funding" step="0.01" placeholder="0.00">
        </div>
        
        <div class="form-group">
          <label>Deadline</label>
          <input type="date" name="deadlines" id="add_deadlines">
          <div style="margin-top: 8px;">
            <label style="font-weight: normal; display: flex; align-items: center; cursor: pointer;">
              <input type="checkbox" id="add_rolling_deadline" style="width: auto; margin-right: 8px;" onchange="toggleDeadlineField('add')">
              Rolling Deadline (No specific date)
            </label>
          </div>
        </div>
        
        <div class="form-group">
          <label>Delivery Context</label>
          <select name="delivery_context">
            <option value="">Select Context</option>
            <option value="At School">At School</option>
            <option value="Through School">Through School</option>
            <option value="On Campus">On Campus</option>
            <option value="Outside School">Outside School</option>
            <option value="Online">Online</option>
            <option value="Virtual">Virtual</option>
          </select>
        </div>
        
        <div class="form-group form-group-full">
          <label>Website Link</label>
          <input type="url" name="website_link" placeholder="https://example.com">
        </div>
        
        <div class="form-group form-group-full">
          <label>Eligibility</label>
          <textarea name="eligibility" placeholder="Who can participate?"></textarea>
        </div>
        
        <div class="form-group form-group-full">
          <label>Notes</label>
          <textarea name="notes" placeholder="Additional information about the opportunity"></textarea>
        </div>
      </div>
      
      <button type="submit" class="btn-submit">
        <i class="bi bi-plus-circle"></i> Add Opportunity
      </button>
    </form>
  </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal">
  <div class="modal-content">
    <div class="modal-header">
      <span class="close" onclick="closeEditModal()">&times;</span>
      <h3><i class="bi bi-pencil"></i> Edit Opportunity</h3>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="opportunity_id" id="edit_id">
      
      <div class="form-grid">
        <div class="form-group form-group-full">
          <label>Program Name *</label>
          <input type="text" name="program_name" id="edit_program_name" required>
        </div>
        
        <div class="form-group">
          <label>Display Name</label>
          <input type="text" name="display_name" id="edit_display_name">
        </div>
        
        <div class="form-group">
          <label>State *</label>
          <select name="state" id="edit_state" required>
            <option value="">Select State</option>
            <?php foreach ($all_states as $state): ?>
              <option value="<?php echo htmlspecialchars($state); ?>"><?php echo htmlspecialchars($state); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        
        <div class="form-group">
          <label>Category</label>
          <select name="category" id="edit_category">
            <option value="">Select Category</option>
            <option value="Club">Club</option>
            <option value="Scholarship">Scholarship</option>
            <option value="Competition">Competition</option>
            <option value="Academic Program">Academic Program</option>
            <option value="Program">Program</option>
            <option value="Athletics/Sports">Athletics/Sports</option>
          </select>
        </div>
        
        <div class="form-group">
          <label>Field</label>
          <select name="field" id="edit_field">
            <option value="">Select Field</option>
            <option value="Academic">Academic</option>
            <option value="Arts">Arts</option>
            <option value="Athletics/Sports">Athletics/Sports</option>
            <option value="Business & Entrepreneurship">Business & Entrepreneurship</option>
            <option value="General Academic">General Academic</option>
            <option value="Leadership & Community Service">Leadership & Community Service</option>
            <option value="Math">Math</option>
            <option value="Science">Science</option>
            <option value="Social Studies & Humanities">Social Studies & Humanities</option>
            <option value="Speech & Debate">Speech & Debate</option>
            <option value="STEM">STEM</option>
          </select>
        </div>
        
        <div class="form-group">
          <label>Grade Levels</label>
          <input type="text" name="grade_levels" id="edit_grade_levels">
        </div>
        
        <div class="form-group">
          <label>Cost/Funding</label>
          <input type="number" name="cost_funding" id="edit_cost_funding" step="0.01">
        </div>
        
        <div class="form-group">
          <label>Deadline</label>
          <input type="date" name="deadlines" id="edit_deadlines">
          <div style="margin-top: 8px;">
            <label style="font-weight: normal; display: flex; align-items: center; cursor: pointer;">
              <input type="checkbox" id="edit_rolling_deadline" style="width: auto; margin-right: 8px;" onchange="toggleDeadlineField('edit')">
              Rolling Deadline (No specific date)
            </label>
          </div>
        </div>
        
        <div class="form-group">
          <label>Delivery Context</label>
          <select name="delivery_context" id="edit_delivery_context">
            <option value="">Select Context</option>
            <option value="At School">At School</option>
            <option value="Through School">Through School</option>
            <option value="On Campus">On Campus</option>
            <option value="Outside School">Outside School</option>
            <option value="Online">Online</option>
            <option value="Virtual">Virtual</option>
          </select>
        </div>
        
        <div class="form-group form-group-full">
          <label>Website Link</label>
          <input type="url" name="website_link" id="edit_website_link">
        </div>
        
        <div class="form-group form-group-full">
          <label>Eligibility</label>
          <textarea name="eligibility" id="edit_eligibility"></textarea>
        </div>
        
        <div class="form-group form-group-full">
          <label>Notes</label>
          <textarea name="notes" id="edit_notes"></textarea>
        </div>
      </div>
      
      <div class="password-confirm">
        <div class="form-group">
          <label><i class="bi bi-shield-lock"></i> Confirm your password to save changes *</label>
          <input type="password" name="confirm_password" placeholder="Enter your password" required>
        </div>
      </div>
      
      <button type="submit" class="btn-submit">
        <i class="bi bi-save"></i> Save Changes
      </button>
    </form>
  </div>
</div>

<!-- Delete Modal -->
<div id="deleteModal" class="modal">
  <div class="modal-content">
    <div class="modal-header">
      <span class="close" onclick="closeDeleteModal()">&times;</span>
      <h3><i class="bi bi-trash"></i> Delete Opportunity</h3>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="delete">
      <input type="hidden" name="opportunity_id" id="delete_id">
      
      <div style="background: #f8d7da; padding: 20px; border-radius: 5px; border-left: 4px solid #dc3545; margin-bottom: 20px;">
        <p style="margin: 0; color: #721c24;">
          <i class="bi bi-exclamation-triangle"></i> <strong>Warning:</strong> You are about to permanently delete this opportunity:
        </p>
        <p style="margin: 10px 0 0 0; font-weight: 600; color: #721c24;" id="delete_program_name"></p>
      </div>
      
      <p style="color: #666; margin-bottom: 20px;">This action cannot be undone. All saved references to this opportunity will be removed.</p>
      
      <div class="password-confirm">
        <div class="form-group">
          <label><i class="bi bi-shield-lock"></i> Confirm your password to delete *</label>
          <input type="password" name="confirm_password" placeholder="Enter your password" required>
        </div>
      </div>
      
      <button type="submit" class="btn-submit" style="background: #dc3545;">
        <i class="bi bi-trash"></i> Delete Permanently
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
// Add Modal
function openAddModal() {
  document.getElementById('addModal').style.display = 'block';
}

function closeAddModal() {
  document.getElementById('addModal').style.display = 'none';
  document.getElementById('add_rolling_deadline').checked = false;
  document.getElementById('add_deadlines').disabled = false;
}

// Edit Modal
function openEditModal(opportunity) {
  document.getElementById('edit_id').value = opportunity.id;
  document.getElementById('edit_program_name').value = opportunity.program_name || '';
  document.getElementById('edit_display_name').value = opportunity.display_name || '';
  document.getElementById('edit_state').value = opportunity.state || '';
  document.getElementById('edit_category').value = opportunity.category || '';
  document.getElementById('edit_field').value = opportunity.field || '';
  document.getElementById('edit_grade_levels').value = opportunity.grade_levels || '';
  document.getElementById('edit_cost_funding').value = opportunity.cost_funding || '';
  document.getElementById('edit_deadlines').value = opportunity.deadlines || '';
  document.getElementById('edit_delivery_context').value = opportunity.delivery_context || '';
  document.getElementById('edit_website_link').value = opportunity.website_link || '';
  document.getElementById('edit_eligibility').value = opportunity.eligibility || '';
  document.getElementById('edit_notes').value = opportunity.notes || '';
  
  // Handle rolling deadline checkbox
  if (!opportunity.deadlines || opportunity.deadlines === '') {
    document.getElementById('edit_rolling_deadline').checked = true;
    document.getElementById('edit_deadlines').disabled = true;
  } else {
    document.getElementById('edit_rolling_deadline').checked = false;
    document.getElementById('edit_deadlines').disabled = false;
  }
  
  document.getElementById('editModal').style.display = 'block';
}

function closeEditModal() {
  document.getElementById('editModal').style.display = 'none';
  document.getElementById('edit_rolling_deadline').checked = false;
  document.getElementById('edit_deadlines').disabled = false;
}

// Delete Modal
function openDeleteModal(id, programName) {
  document.getElementById('delete_id').value = id;
  document.getElementById('delete_program_name').textContent = programName;
  document.getElementById('deleteModal').style.display = 'block';
}

function closeDeleteModal() {
  document.getElementById('deleteModal').style.display = 'none';
}

// Toggle deadline field based on rolling checkbox
function toggleDeadlineField(modalType) {
  const checkbox = document.getElementById(modalType + '_rolling_deadline');
  const dateInput = document.getElementById(modalType + '_deadlines');
  
  if (checkbox.checked) {
    dateInput.value = '';
    dateInput.disabled = true;
  } else {
    dateInput.disabled = false;
  }
}

// Close modal when clicking outside
window.onclick = function(event) {
  const addModal = document.getElementById('addModal');
  const editModal = document.getElementById('editModal');
  const deleteModal = document.getElementById('deleteModal');
  
  if (event.target == addModal) {
    closeAddModal();
  }
  if (event.target == editModal) {
    closeEditModal();
  }
  if (event.target == deleteModal) {
    closeDeleteModal();
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