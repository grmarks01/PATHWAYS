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

// Function to normalize delivery context
function normalizeDeliveryContext($context) {
    if (empty($context)) {
        return 'Other';
    }
    
    $context_lower = strtolower(trim($context));
    
    // At School (on site)
    if (strpos($context_lower, 'at school') !== false || 
        strpos($context_lower, 'on campus') !== false ||
        strpos($context_lower, 'in-person') !== false ||
        strpos($context_lower, 'school-based') !== false) {
        return 'At School (on site)';
    }
    
    // Through School (facilitated by school)
    if (strpos($context_lower, 'through school') !== false) {
        return 'Through School (facilitated by school)';
    }
    
    // Outside School (off site)
    if (strpos($context_lower, 'outside school') !== false || 
        strpos($context_lower, 'off site') !== false ||
        strpos($context_lower, 'online') !== false ||
        strpos($context_lower, 'virtual') !== false ||
        strpos($context_lower, 'hybrid') !== false) {
        return 'Outside School (off site)';
    }
    
    return 'Other';
}

// Function to check if a grade matches the grade_levels string
function grade_matches($grade_levels, $target_grade) {
    if (empty($grade_levels)) {
        return false;
    }
    
    $grade_levels = str_replace(array('–', '—', 'â€"', 'â€"'), '-', $grade_levels);
    preg_match_all('/\d+/', $grade_levels, $matches);
    $numbers = array_map('intval', $matches[0]);
    
    if (empty($numbers)) {
        return false;
    }
    
    if (count($numbers) === 1) {
        return $numbers[0] == $target_grade;
    }
    
    $min_grade = min($numbers);
    $max_grade = max($numbers);
    
    return $target_grade >= $min_grade && $target_grade <= $max_grade;
}

// Get filter parameters - NO FIELD FILTER
$filter_state = isset($_GET['filter_state']) ? trim($_GET['filter_state']) : '';
$filter_delivery = isset($_GET['filter_delivery']) ? trim($_GET['filter_delivery']) : '';
$filter_grade = isset($_GET['filter_grade']) ? trim($_GET['filter_grade']) : '';
$filter_deadline = isset($_GET['filter_deadline']) ? trim($_GET['filter_deadline']) : '';
$sort_state = isset($_GET['sort_state']) ? trim($_GET['sort_state']) : 'count';

// Build WHERE clause for filters
$where_conditions = [];
$params = [];
$types = "";

if (!empty($filter_state)) {
    $where_conditions[] = "state = ?";
    $params[] = $filter_state;
    $types .= "s";
}

// Deadline filter
if (!empty($filter_deadline)) {
    if ($filter_deadline === 'upcoming') {
        $where_conditions[] = "deadlines IS NOT NULL AND deadlines >= CURDATE()";
    } elseif ($filter_deadline === 'this_month') {
        $where_conditions[] = "deadlines IS NOT NULL AND MONTH(deadlines) = MONTH(CURDATE()) AND YEAR(deadlines) = YEAR(CURDATE())";
    } elseif ($filter_deadline === 'next_3_months') {
        $where_conditions[] = "deadlines IS NOT NULL AND deadlines BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 MONTH)";
    } elseif ($filter_deadline === 'rolling') {
        $where_conditions[] = "(deadlines IS NULL OR deadlines = '')";
    } elseif ($filter_deadline === 'spring') {
        $where_conditions[] = "deadlines IS NOT NULL AND MONTH(deadlines) IN (3, 4, 5)";
    } elseif ($filter_deadline === 'summer') {
        $where_conditions[] = "deadlines IS NOT NULL AND MONTH(deadlines) IN (6, 7, 8)";
    } elseif ($filter_deadline === 'fall') {
        $where_conditions[] = "deadlines IS NOT NULL AND MONTH(deadlines) IN (9, 10, 11)";
    } elseif ($filter_deadline === 'winter') {
        $where_conditions[] = "deadlines IS NOT NULL AND MONTH(deadlines) IN (12, 1, 2)";
    }
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get all opportunities that match basic filters
$all_sql = "SELECT * FROM pathways_opportunities $where_clause";
$stmt = $conn->prepare($all_sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Filter by grade and delivery context in PHP
$filtered_opportunities = [];
while ($row = $result->fetch_assoc()) {
    // Apply grade filter
    if (!empty($filter_grade)) {
        if (!grade_matches($row['grade_levels'], intval($filter_grade))) {
            continue;
        }
    }
    
    // Normalize delivery context
    $row['normalized_delivery'] = normalizeDeliveryContext($row['delivery_context']);
    
    // Apply delivery filter
    if (!empty($filter_delivery)) {
        if ($row['normalized_delivery'] !== $filter_delivery) {
            continue;
        }
    }
    
    $filtered_opportunities[] = $row;
}
$stmt->close();

$total_count = count($filtered_opportunities);

// === BY STATE ===
$by_state = [];
foreach ($filtered_opportunities as $opp) {
    $state = $opp['state'];
    if (!isset($by_state[$state])) {
        $by_state[$state] = 0;
    }
    $by_state[$state]++;
}

// Sort by name or count
if ($sort_state === 'name') {
    ksort($by_state);
} else {
    arsort($by_state);
}

$by_state = array_map(function($state, $count) {
    return ['state' => $state, 'count' => $count];
}, array_keys($by_state), $by_state);

// === BY DELIVERY CONTEXT (NORMALIZED) ===
$by_delivery = [];
foreach ($filtered_opportunities as $opp) {
    $delivery = $opp['normalized_delivery'];
    if (!isset($by_delivery[$delivery])) {
        $by_delivery[$delivery] = 0;
    }
    $by_delivery[$delivery]++;
}
arsort($by_delivery);
$by_delivery = array_map(function($delivery, $count) {
    return ['delivery_context' => $delivery, 'count' => $count];
}, array_keys($by_delivery), $by_delivery);

// === BY GRADE LEVEL ===
$by_grade = array_fill_keys([6, 7, 8, 9, 10, 11, 12], 0);
foreach ($filtered_opportunities as $opp) {
    foreach ($by_grade as $grade => $count) {
        if (grade_matches($opp['grade_levels'], $grade)) {
            $by_grade[$grade]++;
        }
    }
}
$by_grade = array_map(function($grade, $count) {
    return ['grade' => $grade, 'count' => $count];
}, array_keys($by_grade), $by_grade);

// === COST ANALYSIS ===
$free_count = 0;
$paid_count = 0;
foreach ($filtered_opportunities as $opp) {
    if (empty($opp['cost_funding']) || $opp['cost_funding'] == 0) {
        $free_count++;
    } else {
        $paid_count++;
    }
}

// === DEADLINE ANALYSIS ===
$upcoming_count = 0;
$rolling_count = 0;
$past_count = 0;
$current_date = date('Y-m-d');

foreach ($filtered_opportunities as $opp) {
    if (empty($opp['deadlines'])) {
        $rolling_count++;
    } elseif ($opp['deadlines'] >= $current_date) {
        $upcoming_count++;
    } else {
        $past_count++;
    }
}

// === DEADLINE DISTRIBUTION ===
$deadline_seasons = [
    'Spring (Mar-May)' => 0,
    'Summer (Jun-Aug)' => 0,
    'Fall (Sep-Nov)' => 0,
    'Winter (Dec-Feb)' => 0,
    'Rolling' => 0
];

foreach ($filtered_opportunities as $opp) {
    if (empty($opp['deadlines'])) {
        $deadline_seasons['Rolling']++;
    } else {
        $month = intval(date('n', strtotime($opp['deadlines'])));
        if (in_array($month, [3, 4, 5])) {
            $deadline_seasons['Spring (Mar-May)']++;
        } elseif (in_array($month, [6, 7, 8])) {
            $deadline_seasons['Summer (Jun-Aug)']++;
        } elseif (in_array($month, [9, 10, 11])) {
            $deadline_seasons['Fall (Sep-Nov)']++;
        } elseif (in_array($month, [12, 1, 2])) {
            $deadline_seasons['Winter (Dec-Feb)']++;
        }
    }
}

$by_deadline = array_map(function($season, $count) {
    return ['season' => $season, 'count' => $count];
}, array_keys($deadline_seasons), $deadline_seasons);

// Get filter options
$all_states = array(
    'Alabama', 'Alaska', 'Arizona', 'Arkansas', 'California', 'Colorado', 'Connecticut', 
    'Delaware', 'Florida', 'Georgia', 'Hawaii', 'Idaho', 'Illinois', 'Indiana', 'Iowa', 
    'Kansas', 'Kentucky', 'Louisiana', 'Maine', 'Maryland', 'Massachusetts', 'Michigan', 
    'Minnesota', 'Mississippi', 'Missouri', 'Montana', 'Nebraska', 'Nevada', 'New Hampshire', 
    'New Jersey', 'New Mexico', 'New York', 'North Carolina', 'North Dakota', 'Ohio', 
    'Oklahoma', 'Oregon', 'Pennsylvania', 'Rhode Island', 'South Carolina', 'South Dakota', 
    'Tennessee', 'Texas', 'Utah', 'Vermont', 'Virginia', 'Washington', 'West Virginia', 
    'Wisconsin', 'Wyoming'
);

closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Reports & Summaries - Pathways</title>

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
    .reports-section {
      padding: 80px 0 60px;
      min-height: 100vh;
      background: #f8f9fa;
    }
    
    .reports-header {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      padding: 40px;
      border-radius: 10px;
      margin-bottom: 40px;
    }
    
    .reports-header h1 {
      margin: 0 0 10px 0;
      font-size: 32px;
      font-weight: 700;
    }
    
    .filter-box {
      background: white;
      padding: 25px;
      border-radius: 10px;
      box-shadow: 0 0 20px rgba(0, 0, 0, 0.08);
      margin-bottom: 30px;
    }
    
    .filter-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 15px;
      align-items: end;
    }
    
    .form-group label {
      display: block;
      margin-bottom: 8px;
      color: #333;
      font-weight: 600;
      font-size: 14px;
    }
    
    .form-group select {
      width: 100%;
      padding: 12px 15px;
      border: 1px solid #ddd;
      border-radius: 5px;
      font-size: 15px;
    }
    
    .stats-grid {
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
      transition: transform 0.3s, box-shadow 0.3s;
    }
    
    .stat-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 5px 30px rgba(0, 0, 0, 0.12);
    }
    
    .stat-card .icon {
      width: 50px;
      height: 50px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 24px;
      margin-bottom: 15px;
    }
    
    .stat-card.primary .icon {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
    }
    
    .stat-card.success .icon {
      background: linear-gradient(135deg, #5fcf80 0%, #4ab86a 100%);
      color: white;
    }
    
    .stat-card.warning .icon {
      background: linear-gradient(135deg, #ffd700 0%, #ffa500 100%);
      color: white;
    }
    
    .stat-card.info .icon {
      background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
      color: white;
    }
    
    .stat-card .value {
      font-size: 36px;
      font-weight: 700;
      color: #333;
      margin-bottom: 5px;
    }
    
    .stat-card .label {
      color: #666;
      font-size: 14px;
      font-weight: 600;
      text-transform: uppercase;
    }
    
    .report-card {
      background: white;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 0 20px rgba(0, 0, 0, 0.08);
      margin-bottom: 30px;
    }
    
    .report-card h3 {
      margin: 0 0 20px 0;
      color: #333;
      font-size: 20px;
      font-weight: 700;
      padding-bottom: 15px;
      border-bottom: 2px solid #f0f0f0;
    }
    
    .data-table {
      width: 100%;
      border-collapse: collapse;
    }
    
    .data-table th,
    .data-table td {
      padding: 12px;
      text-align: left;
      border-bottom: 1px solid #f0f0f0;
    }
    
    .data-table th {
      background: #f8f9fa;
      font-weight: 600;
      color: #333;
    }
    
    .data-table tr:hover {
      background: #f8f9fa;
    }
    
    .progress-bar-container {
      width: 100%;
      height: 8px;
      background: #f0f0f0;
      border-radius: 4px;
      overflow: hidden;
      margin-top: 5px;
    }
    
    .progress-bar {
      height: 100%;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      transition: width 0.3s;
    }
    
    .chart-container {
      position: relative;
      height: 400px;
      margin-top: 20px;
    }
    
    .btn-filter {
      padding: 12px 25px;
      background: #667eea;
      color: white;
      border: none;
      border-radius: 5px;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.3s;
      width: 100%;
    }
    
    .btn-filter:hover {
      background: #764ba2;
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
      width: 100%;
      text-align: center;
    }
    
    .btn-clear:hover {
      border-color: #999;
      color: #333;
    }
    
    .sort-links {
      margin-bottom: 15px;
    }
    
    .sort-links span {
      color: #666;
      font-size: 14px;
      margin-right: 10px;
    }
    
    .sort-links a {
      margin-right: 15px;
      text-decoration: none;
      font-weight: normal;
      transition: all 0.3s;
    }
    
    .sort-links a.active {
      color: #667eea;
      font-weight: 600;
    }
    
    .sort-links a:not(.active) {
      color: #666;
    }
    
    .sort-links a:hover {
      color: #667eea;
    }
    
    @media (max-width: 768px) {
      .filter-grid {
        grid-template-columns: 1fr;
      }
    }
	
	@media (max-width: 768px) {
  .reports-header {
    padding: 30px 20px;
  }
  
  .reports-header h1 {
    font-size: 24px;
  }
  
  .reports-header div[style*="display: flex"] {
    display: flex !important;
    flex-direction: column !important;
    gap: 20px !important;
  }
  
  .btn-export {
    width: 100% !important;
    justify-content: center !important;
  }
  
  .filter-grid {
    grid-template-columns: 1fr;
  }
  
  .stats-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 480px) {
  .reports-header {
    padding: 20px 15px;
  }
  
  .reports-header h1 {
    font-size: 20px;
    margin-bottom: 15px;
  }
  
  .reports-header p {
    font-size: 14px;
  }
  
  .data-table {
    font-size: 12px;
  }
  
  .data-table th,
  .data-table td {
    padding: 8px;
  }
}
  </style>
</head>

<body>

<?php include('components/header.php'); ?>

<main class="main">
  <section class="reports-section">
    <div class="container">
      
      <div class="reports-header" data-aos="fade-down">
        <div style="display: flex; justify-content: space-between; align-items: center;">
          <div>
            <h1><i class="bi bi-graph-up"></i> Reports & Summaries</h1>
            <p>Comprehensive statistics and analysis of opportunities</p>
          </div>
          <button onclick="exportReport()" class="btn-export" style="background: white; color: #667eea; padding: 12px 25px; border: none; border-radius: 5px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px;">
            <i class="bi bi-download"></i> Export Report
          </button>
        </div>
      </div>
      
      <!-- Filters - NO FIELD FILTER -->
      <div class="filter-box" data-aos="fade-up">
        <form method="GET" action="">
          <input type="hidden" name="sort_state" value="<?php echo htmlspecialchars($sort_state); ?>">
          <div class="filter-grid">
            <div class="form-group">
              <label>State</label>
              <select name="filter_state">
                <option value="">All States</option>
                <?php foreach ($all_states as $state): ?>
                  <option value="<?php echo htmlspecialchars($state); ?>" <?php echo ($filter_state === $state) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($state); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            
            <div class="form-group">
              <label>Delivery Context</label>
              <select name="filter_delivery">
                <option value="">All Contexts</option>
                <option value="At School (on site)" <?php echo ($filter_delivery === 'At School (on site)') ? 'selected' : ''; ?>>At School (on site)</option>
                <option value="Through School (facilitated by school)" <?php echo ($filter_delivery === 'Through School (facilitated by school)') ? 'selected' : ''; ?>>Through School (facilitated by school)</option>
                <option value="Outside School (off site)" <?php echo ($filter_delivery === 'Outside School (off site)') ? 'selected' : ''; ?>>Outside School (off site)</option>
                <option value="Other" <?php echo ($filter_delivery === 'Other') ? 'selected' : ''; ?>>Other</option>
              </select>
            </div>
            
            <div class="form-group">
              <label>Grade Level</label>
              <select name="filter_grade">
                <option value="">All Grades</option>
                <option value="6" <?php echo ($filter_grade === '6') ? 'selected' : ''; ?>>6th Grade</option>
                <option value="7" <?php echo ($filter_grade === '7') ? 'selected' : ''; ?>>7th Grade</option>
                <option value="8" <?php echo ($filter_grade === '8') ? 'selected' : ''; ?>>8th Grade</option>
                <option value="9" <?php echo ($filter_grade === '9') ? 'selected' : ''; ?>>9th Grade</option>
                <option value="10" <?php echo ($filter_grade === '10') ? 'selected' : ''; ?>>10th Grade</option>
                <option value="11" <?php echo ($filter_grade === '11') ? 'selected' : ''; ?>>11th Grade</option>
                <option value="12" <?php echo ($filter_grade === '12') ? 'selected' : ''; ?>>12th Grade</option>
              </select>
            </div>
            
            <div class="form-group">
              <label>Deadline</label>
              <select name="filter_deadline">
                <option value="">All Deadlines</option>
                <option value="upcoming" <?php echo ($filter_deadline === 'upcoming') ? 'selected' : ''; ?>>Upcoming</option>
                <option value="this_month" <?php echo ($filter_deadline === 'this_month') ? 'selected' : ''; ?>>This Month</option>
                <option value="next_3_months" <?php echo ($filter_deadline === 'next_3_months') ? 'selected' : ''; ?>>Next 3 Months</option>
                <option value="rolling" <?php echo ($filter_deadline === 'rolling') ? 'selected' : ''; ?>>Rolling</option>
                <option value="spring" <?php echo ($filter_deadline === 'spring') ? 'selected' : ''; ?>>Spring (Mar-May)</option>
                <option value="summer" <?php echo ($filter_deadline === 'summer') ? 'selected' : ''; ?>>Summer (Jun-Aug)</option>
                <option value="fall" <?php echo ($filter_deadline === 'fall') ? 'selected' : ''; ?>>Fall (Sep-Nov)</option>
                <option value="winter" <?php echo ($filter_deadline === 'winter') ? 'selected' : ''; ?>>Winter (Dec-Feb)</option>
              </select>
            </div>
            
            <div class="form-group">
              <label>&nbsp;</label>
              <button type="submit" class="btn-filter">
                <i class="bi bi-funnel"></i> Apply
              </button>
            </div>
            
            <?php if (!empty($filter_state) || !empty($filter_delivery) || !empty($filter_grade) || !empty($filter_deadline)): ?>
            <div class="form-group">
              <label>&nbsp;</label>
              <a href="reports-summaries.php" class="btn-clear">
                <i class="bi bi-x-circle"></i> Clear
              </a>
            </div>
            <?php endif; ?>
          </div>
        </form>
      </div>
      
      <!-- Key Statistics -->
      <div class="stats-grid" data-aos="fade-up">
        <div class="stat-card primary">
          <div class="icon"><i class="bi bi-briefcase"></i></div>
          <div class="value"><?php echo number_format($total_count); ?></div>
          <div class="label">Total Opportunities</div>
        </div>
        
        <div class="stat-card success">
          <div class="icon"><i class="bi bi-gift"></i></div>
          <div class="value"><?php echo number_format($free_count); ?></div>
          <div class="label">Free Opportunities</div>
        </div>
        
        <div class="stat-card warning">
          <div class="icon"><i class="bi bi-calendar-event"></i></div>
          <div class="value"><?php echo number_format($upcoming_count); ?></div>
          <div class="label">Upcoming Deadlines</div>
        </div>
        
        <div class="stat-card info">
          <div class="icon"><i class="bi bi-infinity"></i></div>
          <div class="value"><?php echo number_format($rolling_count); ?></div>
          <div class="label">Rolling Deadlines</div>
        </div>
      </div>
      
      <!-- By State -->
      <div class="report-card" data-aos="fade-up">
        <h3><i class="bi bi-geo-alt"></i> Opportunities by State</h3>
        <div class="sort-links">
          <span>Sort by:</span>
          <?php
          // Build query string for sort links
          $query_params = [];
          if (!empty($filter_state)) $query_params[] = 'filter_state=' . urlencode($filter_state);
          if (!empty($filter_delivery)) $query_params[] = 'filter_delivery=' . urlencode($filter_delivery);
          if (!empty($filter_grade)) $query_params[] = 'filter_grade=' . urlencode($filter_grade);
          if (!empty($filter_deadline)) $query_params[] = 'filter_deadline=' . urlencode($filter_deadline);
          $query_string = !empty($query_params) ? '&' . implode('&', $query_params) : '';
          ?>
          <a href="?sort_state=name<?php echo $query_string; ?>" class="<?php echo $sort_state === 'name' ? 'active' : ''; ?>">
            <i class="bi bi-sort-alpha-down"></i> Name
          </a>
          <a href="?sort_state=count<?php echo $query_string; ?>" class="<?php echo $sort_state === 'count' || empty($sort_state) ? 'active' : ''; ?>">
            <i class="bi bi-sort-numeric-down"></i> Count
          </a>
        </div>
        <table class="data-table">
          <thead>
            <tr>
              <th>State</th>
              <th>Count</th>
              <th style="width: 40%;">Distribution</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($by_state as $row): ?>
            <tr>
              <td><strong><?php echo htmlspecialchars($row['state']); ?></strong></td>
              <td><?php echo number_format($row['count']); ?></td>
              <td>
                <div class="progress-bar-container">
                  <div class="progress-bar" style="width: <?php echo ($total_count > 0 ? ($row['count'] / $total_count * 100) : 0); ?>%"></div>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <div class="chart-container">
          <canvas id="stateChart"></canvas>
        </div>
      </div>
      
      <!-- By Delivery Context -->
      <div class="report-card" data-aos="fade-up">
        <h3><i class="bi bi-building"></i> Opportunities by Delivery Context</h3>
        <table class="data-table">
          <thead>
            <tr>
              <th>Delivery Context</th>
              <th>Count</th>
              <th>Percentage</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($by_delivery as $row): ?>
            <tr>
              <td><strong><?php echo htmlspecialchars($row['delivery_context']); ?></strong></td>
              <td><?php echo number_format($row['count']); ?></td>
              <td><?php echo $total_count > 0 ? number_format(($row['count'] / $total_count * 100), 1) : 0; ?>%</td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <div class="chart-container">
          <canvas id="deliveryChart"></canvas>
        </div>
      </div>
      
      <!-- By Grade Level -->
      <div class="report-card" data-aos="fade-up">
        <h3><i class="bi bi-mortarboard"></i> Opportunities by Grade Level</h3>
        <table class="data-table">
          <thead>
            <tr>
              <th>Grade Level</th>
              <th>Count</th>
              <th>Percentage</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($by_grade as $row): ?>
            <tr>
              <td><strong><?php echo htmlspecialchars($row['grade']); ?>th Grade</strong></td>
              <td><?php echo number_format($row['count']); ?></td>
              <td><?php echo $total_count > 0 ? number_format(($row['count'] / $total_count * 100), 1) : 0; ?>%</td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <div class="chart-container">
          <canvas id="gradeChart"></canvas>
        </div>
      </div>
      
      <!-- By Deadline Season -->
      <div class="report-card" data-aos="fade-up">
        <h3><i class="bi bi-calendar-range"></i> Deadlines by Season</h3>
        <table class="data-table">
          <thead>
            <tr>
              <th>Season</th>
              <th>Count</th>
              <th>Percentage</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($by_deadline as $row): ?>
            <tr>
              <td><strong><?php echo htmlspecialchars($row['season']); ?></strong></td>
              <td><?php echo number_format($row['count']); ?></td>
              <td><?php echo $total_count > 0 ? number_format(($row['count'] / $total_count * 100), 1) : 0; ?>%</td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <div class="chart-container">
          <canvas id="deadlineChart"></canvas>
        </div>
      </div>
      
    </div>
  </section>
</main>

<?php include('components/footer.php'); ?>

<a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

<script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/vendor/aos/aos.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="assets/js/main.js"></script>

<script>
// Initialize AOS
AOS.init({
  duration: 800,
  easing: 'ease-in-out',
  once: true
});

// Chart colors
const chartColors = {
  primary: '#667eea',
  success: '#5fcf80',
  warning: '#ffd700',
  info: '#17a2b8',
  danger: '#dc3545',
  purple: '#764ba2',
  teal: '#20c997',
  orange: '#fd7e14',
  pink: '#e83e8c',
  indigo: '#6610f2'
};

// State Chart
const stateData = <?php echo json_encode(array_values($by_state)); ?>;
const stateCtx = document.getElementById('stateChart').getContext('2d');
new Chart(stateCtx, {
  type: 'bar',
  data: {
    labels: stateData.map(row => row.state),
    datasets: [{
      label: 'Opportunities',
      data: stateData.map(row => row.count),
      backgroundColor: chartColors.primary,
      borderColor: chartColors.purple,
      borderWidth: 1
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    scales: {
      y: {
        beginAtZero: true,
        ticks: {
          precision: 0
        }
      }
    },
    plugins: {
      legend: {
        display: false
      }
    }
  }
});

// Delivery Chart
const deliveryData = <?php echo json_encode(array_values($by_delivery)); ?>;
const deliveryCtx = document.getElementById('deliveryChart').getContext('2d');
new Chart(deliveryCtx, {
  type: 'bar',
  data: {
    labels: deliveryData.map(row => row.delivery_context),
    datasets: [{
      label: 'Opportunities',
      data: deliveryData.map(row => row.count),
      backgroundColor: chartColors.success,
      borderColor: '#4ab86a',
      borderWidth: 1
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    indexAxis: 'y',
    scales: {
      x: {
        beginAtZero: true,
        ticks: {
          precision: 0
        }
      }
    },
    plugins: {
      legend: {
        display: false
      }
    }
  }
});

// Grade Level Chart
const gradeData = <?php echo json_encode(array_values($by_grade)); ?>;
const gradeCtx = document.getElementById('gradeChart').getContext('2d');
new Chart(gradeCtx, {
  type: 'bar',
  data: {
    labels: gradeData.map(row => row.grade + 'th'),
    datasets: [{
      label: 'Opportunities',
      data: gradeData.map(row => row.count),
      backgroundColor: chartColors.info,
      borderColor: '#138496',
      borderWidth: 1
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    scales: {
      y: {
        beginAtZero: true,
        ticks: {
          precision: 0
        }
      }
    },
    plugins: {
      legend: {
        display: false
      }
    }
  }
});

// Deadline Season Chart
const deadlineData = <?php echo json_encode(array_values($by_deadline)); ?>;
const deadlineCtx = document.getElementById('deadlineChart').getContext('2d');
new Chart(deadlineCtx, {
  type: 'doughnut',
  data: {
    labels: deadlineData.map(row => row.season),
    datasets: [{
      data: deadlineData.map(row => row.count),
      backgroundColor: [
        chartColors.success,  // Spring
        chartColors.warning,  // Summer
        chartColors.orange,   // Fall
        chartColors.info,     // Winter
        chartColors.primary   // Rolling
      ]
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        position: 'bottom'
      }
    }
  }
});
</script>

<script>
// Export Report Function
function exportReport() {
  const reportData = {
    generatedDate: new Date().toLocaleString(),
    filters: {
      state: <?php echo json_encode($filter_state ?: 'All States'); ?>,
      deliveryContext: <?php echo json_encode($filter_delivery ?: 'All Contexts'); ?>,
      gradeLevel: <?php echo json_encode($filter_grade ? $filter_grade . "th Grade" : "All Grades"); ?>,
      deadline: <?php echo json_encode($filter_deadline ?: 'All Deadlines'); ?>
    },
    summary: {
      totalOpportunities: <?php echo $total_count; ?>,
      freeOpportunities: <?php echo $free_count; ?>,
      upcomingDeadlines: <?php echo $upcoming_count; ?>,
      rollingDeadlines: <?php echo $rolling_count; ?>
    },
    byState: <?php echo json_encode($by_state); ?>,
    byDelivery: <?php echo json_encode($by_delivery); ?>,
    byGrade: <?php echo json_encode($by_grade); ?>,
    byDeadline: <?php echo json_encode($by_deadline); ?>
  };
  
  // Build CSV content
  let csv = 'Pathways Opportunities Report\n';
  csv += 'Generated: ' + reportData.generatedDate + '\n\n';
  
  // Filters Applied
  csv += 'FILTERS APPLIED\n';
  csv += 'State,' + reportData.filters.state + '\n';
  csv += 'Delivery Context,' + reportData.filters.deliveryContext + '\n';
  csv += 'Grade Level,' + reportData.filters.gradeLevel + '\n';
  csv += 'Deadline,' + reportData.filters.deadline + '\n\n';
  
  // Summary Statistics
  csv += 'SUMMARY STATISTICS\n';
  csv += 'Total Opportunities,' + reportData.summary.totalOpportunities + '\n';
  csv += 'Free Opportunities,' + reportData.summary.freeOpportunities + '\n';
  csv += 'Upcoming Deadlines,' + reportData.summary.upcomingDeadlines + '\n';
  csv += 'Rolling Deadlines,' + reportData.summary.rollingDeadlines + '\n\n';
  
  // By State
  csv += 'OPPORTUNITIES BY STATE\n';
  csv += 'State,Count,Percentage\n';
  reportData.byState.forEach(row => {
    const percentage = reportData.summary.totalOpportunities > 0 
      ? ((row.count / reportData.summary.totalOpportunities) * 100).toFixed(1) 
      : 0;
    csv += '"' + row.state + '",' + row.count + ',' + percentage + '%\n';
  });
  csv += '\n';
  
  // By Delivery Context
  csv += 'OPPORTUNITIES BY DELIVERY CONTEXT\n';
  csv += 'Delivery Context,Count,Percentage\n';
  reportData.byDelivery.forEach(row => {
    const percentage = reportData.summary.totalOpportunities > 0 
      ? ((row.count / reportData.summary.totalOpportunities) * 100).toFixed(1) 
      : 0;
    csv += '"' + row.delivery_context + '",' + row.count + ',' + percentage + '%\n';
  });
  csv += '\n';
  
  // By Grade Level
  csv += 'OPPORTUNITIES BY GRADE LEVEL\n';
  csv += 'Grade Level,Count,Percentage\n';
  reportData.byGrade.forEach(row => {
    const percentage = reportData.summary.totalOpportunities > 0 
      ? ((row.count / reportData.summary.totalOpportunities) * 100).toFixed(1) 
      : 0;
    csv += row.grade + 'th Grade,' + row.count + ',' + percentage + '%\n';
  });
  csv += '\n';
  
  // By Deadline Season
  csv += 'DEADLINES BY SEASON\n';
  csv += 'Season,Count,Percentage\n';
  reportData.byDeadline.forEach(row => {
    const percentage = reportData.summary.totalOpportunities > 0 
      ? ((row.count / reportData.summary.totalOpportunities) * 100).toFixed(1) 
      : 0;
    csv += '"' + row.season + '",' + row.count + ',' + percentage + '%\n';
  });
  
  // Create download
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  const url = URL.createObjectURL(blob);
  
  const filename = 'Pathways_Report_' + new Date().toISOString().slice(0, 10) + '.csv';
  
  link.setAttribute('href', url);
  link.setAttribute('download', filename);
  link.style.visibility = 'hidden';
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
}
</script>

</body>
</html>