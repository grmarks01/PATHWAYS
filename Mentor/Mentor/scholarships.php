<?php
session_start();

// Check if user is logged in
$is_logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$user_id = $is_logged_in ? $_SESSION['user_id'] : null;

// Include database configuration
require_once 'components/db-config.php';
$conn = getDBConnection();

// Get search parameters
$search_keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$search_state = isset($_GET['state']) ? trim($_GET['state']) : '';
$search_deadline = isset($_GET['deadline']) ? trim($_GET['deadline']) : '';
$search_grade = isset($_GET['grade']) ? trim($_GET['grade']) : '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build SQL query - ONLY Scholarship category
$sql = "SELECT po.*, ";
if ($is_logged_in) {
    $sql .= "(SELECT COUNT(*) FROM saved_opportunities WHERE user_id = ? AND opportunity_id = po.id) as is_saved ";
} else {
    $sql .= "0 as is_saved ";
}
$sql .= "FROM pathways_opportunities po WHERE po.category = 'Scholarship'";

$params = [];
$types = "";

if ($is_logged_in) {
    $params[] = $user_id;
    $types .= "i";
}

// Add search filters
if (!empty($search_keyword)) {
    $sql .= " AND (po.program_name LIKE ? OR po.notes LIKE ? OR po.eligibility LIKE ?)";
    $keyword_param = "%$search_keyword%";
    $params[] = $keyword_param;
    $params[] = $keyword_param;
    $params[] = $keyword_param;
    $types .= "sss";
}

if (!empty($search_state)) {
    $sql .= " AND po.state = ?";
    $params[] = $search_state;
    $types .= "s";
}

if (!empty($search_deadline)) {
    if ($search_deadline === 'upcoming') {
        $sql .= " AND po.deadlines IS NOT NULL AND po.deadlines >= CURDATE()";
    } elseif ($search_deadline === 'this_month') {
        $sql .= " AND po.deadlines IS NOT NULL AND MONTH(po.deadlines) = MONTH(CURDATE()) AND YEAR(po.deadlines) = YEAR(CURDATE())";
    } elseif ($search_deadline === 'next_3_months') {
        $sql .= " AND po.deadlines IS NOT NULL AND po.deadlines BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 MONTH)";
    } elseif ($search_deadline === 'rolling') {
        $sql .= " AND (po.deadlines IS NULL OR po.deadlines = '')";
    } elseif ($search_deadline === 'spring') {
        $sql .= " AND po.deadlines IS NOT NULL AND MONTH(po.deadlines) IN (3, 4, 5)";
    } elseif ($search_deadline === 'summer') {
        $sql .= " AND po.deadlines IS NOT NULL AND MONTH(po.deadlines) IN (6, 7, 8)";
    } elseif ($search_deadline === 'fall') {
        $sql .= " AND po.deadlines IS NOT NULL AND MONTH(po.deadlines) IN (9, 10, 11)";
    } elseif ($search_deadline === 'winter') {
        $sql .= " AND po.deadlines IS NOT NULL AND MONTH(po.deadlines) IN (12, 1, 2)";
    }
}

$sql .= " ORDER BY po.deadlines ASC, po.program_name LIMIT ? OFFSET ?";

// Prepare and execute
$stmt = $conn->prepare($sql);
$params[] = $per_page;
$params[] = $offset;
$types .= "ii";
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

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

// If grade filter is applied, we need to post-process to handle ranges properly
$opportunities = [];
if (!empty($search_grade)) {
    $grade_num = (int)$search_grade;
    while ($row = $result->fetch_assoc()) {
        if (grade_matches($row['grade_levels'], $grade_num)) {
            $opportunities[] = $row;
        }
    }
} else {
    while ($row = $result->fetch_assoc()) {
        $opportunities[] = $row;
    }
}

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total FROM pathways_opportunities po WHERE po.category = 'Scholarship'";
$count_params = [];
$count_types = "";

if (!empty($search_keyword)) {
    $count_sql .= " AND (po.program_name LIKE ? OR po.notes LIKE ? OR po.eligibility LIKE ?)";
    $keyword_param = "%$search_keyword%";
    $count_params[] = $keyword_param;
    $count_params[] = $keyword_param;
    $count_params[] = $keyword_param;
    $count_types .= "sss";
}

if (!empty($search_state)) {
    $count_sql .= " AND po.state = ?";
    $count_params[] = $search_state;
    $count_types .= "s";
}

if (!empty($search_deadline)) {
    if ($search_deadline === 'upcoming') {
        $count_sql .= " AND po.deadlines IS NOT NULL AND po.deadlines >= CURDATE()";
    } elseif ($search_deadline === 'this_month') {
        $count_sql .= " AND po.deadlines IS NOT NULL AND MONTH(po.deadlines) = MONTH(CURDATE()) AND YEAR(po.deadlines) = YEAR(CURDATE())";
    } elseif ($search_deadline === 'next_3_months') {
        $count_sql .= " AND po.deadlines IS NOT NULL AND po.deadlines BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 MONTH)";
    } elseif ($search_deadline === 'rolling') {
        $count_sql .= " AND (po.deadlines IS NULL OR po.deadlines = '')";
    } elseif ($search_deadline === 'spring') {
        $count_sql .= " AND po.deadlines IS NOT NULL AND MONTH(po.deadlines) IN (3, 4, 5)";
    } elseif ($search_deadline === 'summer') {
        $count_sql .= " AND po.deadlines IS NOT NULL AND MONTH(po.deadlines) IN (6, 7, 8)";
    } elseif ($search_deadline === 'fall') {
        $count_sql .= " AND po.deadlines IS NOT NULL AND MONTH(po.deadlines) IN (9, 10, 11)";
    } elseif ($search_deadline === 'winter') {
        $count_sql .= " AND po.deadlines IS NOT NULL AND MONTH(po.deadlines) IN (12, 1, 2)";
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

// Define all US states (always alphabetically sorted)
$all_states = [
    'Alabama', 'Alaska', 'Arizona', 'Arkansas', 'California', 'Colorado', 'Connecticut', 
    'Delaware', 'Florida', 'Georgia', 'Hawaii', 'Idaho', 'Illinois', 'Indiana', 'Iowa', 
    'Kansas', 'Kentucky', 'Louisiana', 'Maine', 'Maryland', 'Massachusetts', 'Michigan', 
    'Minnesota', 'Mississippi', 'Missouri', 'Montana', 'Nebraska', 'Nevada', 'New Hampshire', 
    'New Jersey', 'New Mexico', 'New York', 'North Carolina', 'North Dakota', 'Ohio', 
    'Oklahoma', 'Oregon', 'Pennsylvania', 'Rhode Island', 'South Carolina', 'South Dakota', 
    'Tennessee', 'Texas', 'Utah', 'Vermont', 'Virginia', 'Washington', 'West Virginia', 
    'Wisconsin', 'Wyoming'
];

$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Scholarships - Pathways</title>
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
    .search-section{padding:80px 0 60px;min-height:100vh}.search-header{background:linear-gradient(135deg,#ffd700 0%,#ffb700 100%);color:white;padding:40px;border-radius:10px;margin-bottom:40px}.search-header h1{margin:0 0 10px 0;font-size:32px;font-weight:700}.search-box{background:white;padding:30px;border-radius:10px;box-shadow:0 0 20px rgba(0,0,0,0.08);margin-bottom:30px}.filter-row{display:grid;grid-template-columns:2fr 1fr 1fr 1fr auto;gap:15px;align-items:end}@media (max-width:992px){.filter-row{grid-template-columns:1fr}}.form-group{margin-bottom:0}.form-group label{display:block;margin-bottom:8px;color:#333;font-weight:600;font-size:14px}.form-group input,.form-group select{width:100%;padding:12px 15px;border:1px solid #ddd;border-radius:5px;font-size:15px}.form-group input:focus,.form-group select:focus{outline:none;border-color:#ffd700}.btn-search{padding:12px 30px;background:#ffd700;color:#333;border:none;border-radius:5px;font-weight:600;cursor:pointer;transition:background 0.3s}.btn-search:hover{background:#ffb700}.btn-clear{padding:12px 30px;background:#fff;color:#666;border:2px solid #ddd;border-radius:5px;font-weight:600;cursor:pointer;transition:all 0.3s;margin-left:10px;text-decoration:none;display:inline-block}.btn-clear:hover{border-color:#999;color:#333}.results-info{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;padding:15px 20px;background:#fffbea;border-radius:5px}.opportunity-card{background:white;padding:25px;border-radius:10px;box-shadow:0 0 20px rgba(0,0,0,0.08);margin-bottom:20px;transition:transform 0.3s,box-shadow 0.3s;position:relative}.opportunity-card:hover{transform:translateY(-3px);box-shadow:0 5px 30px rgba(0,0,0,0.12)}.favorite-btn{position:absolute;top:20px;right:20px;background:white;border:2px solid #ddd;border-radius:50%;width:45px;height:45px;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:all 0.3s;font-size:20px;color:#999}.favorite-btn:hover{border-color:#ffd700;color:#ffd700;transform:scale(1.1)}.favorite-btn.saved{background:#ffd700;border-color:#ffd700;color:white}.favorite-btn.saved:hover{background:#ffb700}.opportunity-title{font-size:20px;font-weight:700;color:#333;margin:0 60px 10px 0}.opportunity-badges{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:15px}.badge{padding:5px 12px;border-radius:20px;font-size:12px;font-weight:600}.badge-category{background:#ffd700;color:#333}.badge-state{background:#6c757d;color:white}.badge-deadline{background:#dc3545;color:white}.pagination{display:flex;justify-content:center;align-items:center;gap:10px;margin-top:30px;padding:20px;background:white;border-radius:10px;box-shadow:0 0 20px rgba(0,0,0,0.08)}.pagination a,.pagination span{padding:10px 15px;border:1px solid #ddd;border-radius:5px;text-decoration:none;color:#333;transition:all 0.3s}.pagination a:hover{background:#ffd700;color:#333;border-color:#ffd700}.pagination .current{background:#ffd700;color:#333;border-color:#ffd700;font-weight:600}.pagination .disabled{opacity:0.5;cursor:not-allowed;pointer-events:none}.opportunity-details{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:15px;margin-top:15px}.detail-item{padding:12px;background:#f8f9fa;border-radius:5px}.detail-item label{display:block;font-size:11px;color:#666;font-weight:600;margin-bottom:5px;text-transform:uppercase}.detail-item .value{color:#333;font-size:14px}.opportunity-description{margin-top:15px;padding-top:15px;border-top:1px solid #eee;color:#666;font-size:14px}.opportunity-actions{margin-top:20px;display:flex;gap:10px}.btn-visit{flex:1;padding:10px 20px;background:#ffd700;color:#333;text-decoration:none;border-radius:5px;text-align:center;font-weight:600;transition:background 0.3s;display:inline-block}.btn-visit:hover{background:#ffb700;color:#333}.empty-state{text-align:center;padding:60px 20px;background:white;border-radius:10px;box-shadow:0 0 20px rgba(0,0,0,0.08)}.empty-state i{font-size:64px;color:#ddd;margin-bottom:20px}.empty-state h3{color:#333;margin-bottom:10px}.empty-state p{color:#999}
  </style>
</head>
<body>
<?php include('components/header.php'); ?>
<main class="main">
  <section class="search-section">
    <div class="container">
      <div class="search-header" data-aos="fade-down">
        <h1><i class="bi bi-trophy"></i> Scholarships & Awards</h1>
        <p>Find scholarship opportunities to fund your education</p>
      </div>
      <div class="search-box" data-aos="fade-up">
        <form method="GET" action="">
          <div class="filter-row">
            <div class="form-group">
              <label for="keyword">Search Scholarships</label>
              <input type="text" id="keyword" name="keyword" placeholder="Search by scholarship name or description..." value="<?php echo htmlspecialchars($search_keyword); ?>">
            </div>
            <div class="form-group">
              <label for="state">State</label>
              <select id="state" name="state">
                <option value="">All States</option>
                <?php foreach ($all_states as $state): ?>
                  <option value="<?php echo htmlspecialchars($state); ?>" <?php echo ($search_state === $state) ? 'selected' : ''; ?>><?php echo htmlspecialchars($state); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label for="grade">Grade Level</label>
              <select id="grade" name="grade">
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
              <label for="deadline">Deadline</label>
              <select id="deadline" name="deadline">
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
              <button type="submit" class="btn-search"><i class="bi bi-search"></i> Search</button>
            </div>
          </div>
          <?php if (!empty($search_keyword) || !empty($search_state) || !empty($search_deadline) || !empty($search_grade)): ?>
          <div style="margin-top:15px">
            <a href="scholarships.php" class="btn-clear"><i class="bi bi-x-circle"></i> Clear Filters</a>
          </div>
          <?php endif; ?>
        </form>
      </div>
      <div class="results-info">
        <div><strong><?php echo $total_results; ?></strong> scholarships found<?php if ($total_pages > 1): ?> (Page <?php echo $page; ?> of <?php echo $total_pages; ?>)<?php endif; ?></div>
        <?php if (!$is_logged_in): ?><div style="font-size:14px;color:#666"><i class="bi bi-info-circle"></i> <a href="login-page.php" style="color:#ffd700;text-decoration:none">Login</a> to save favorites</div><?php endif; ?>
      </div>
      <?php if (count($opportunities) > 0): ?>
        <?php foreach ($opportunities as $opp): ?>
          <div class="opportunity-card" data-aos="fade-up">
            <?php if ($is_logged_in): ?>
            <button class="favorite-btn <?php echo $opp['is_saved'] ? 'saved' : ''; ?>" onclick="toggleFavorite(<?php echo $opp['id']; ?>, this)">
              <i class="bi bi-heart<?php echo $opp['is_saved'] ? '-fill' : ''; ?>"></i>
            </button>
            <?php endif; ?>
            <h3 class="opportunity-title"><?php echo htmlspecialchars($opp['program_name']); ?></h3>
            <div class="opportunity-badges">
              <span class="badge badge-category">Scholarship</span>
              <span class="badge badge-state"><?php echo htmlspecialchars($opp['state']); ?></span>
              <?php if ($opp['deadlines']): ?><span class="badge badge-deadline">Deadline: <?php echo date('M d, Y', strtotime($opp['deadlines'])); ?></span><?php endif; ?>
            </div>
            <div class="opportunity-details">
              <div class="detail-item">
                <label>Grade Levels</label>
                <div class="value"><?php echo htmlspecialchars($opp['grade_levels'] ?: 'Not specified'); ?></div>
              </div>
              <div class="detail-item">
                <label>Award Amount</label>
                <div class="value"><?php echo $opp['cost_funding'] ? '$' . number_format($opp['cost_funding'], 2) : 'Varies'; ?></div>
              </div>
              <div class="detail-item">
                <label>Delivery</label>
                <div class="value"><?php echo htmlspecialchars($opp['delivery_context'] ?: 'Not specified'); ?></div>
              </div>
            </div>
            <?php if ($opp['eligibility'] || $opp['notes']): ?>
            <div class="opportunity-description">
              <?php if ($opp['eligibility']): ?><p><strong>Eligibility:</strong> <?php echo htmlspecialchars($opp['eligibility']); ?></p><?php endif; ?>
              <?php if ($opp['notes']): ?><p><strong>Notes:</strong> <?php echo htmlspecialchars($opp['notes']); ?></p><?php endif; ?>
            </div>
            <?php endif; ?>
            <div class="opportunity-actions">
              <?php if ($opp['website_link']): 
                $website_url = $opp['website_link'];
                if (!preg_match("~^(?:f|ht)tps?://~i", $website_url)) {
                    $website_url = "https://" . $website_url;
                }
              ?>
              <a href="<?php echo htmlspecialchars($website_url); ?>" target="_blank" class="btn-visit"><i class="bi bi-box-arrow-up-right"></i> Visit Website</a>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="empty-state">
          <i class="bi bi-search"></i>
          <h3>No Scholarships Found</h3>
          <p>Try adjusting your search filters to see more results</p>
        </div>
      <?php endif; ?>
      <?php if ($total_pages > 1): ?>
        <div class="pagination">
          <?php if ($page > 1): ?>
            <a href="?keyword=<?php echo urlencode($search_keyword); ?>&state=<?php echo urlencode($search_state); ?>&deadline=<?php echo urlencode($search_deadline); ?>&grade=<?php echo urlencode($search_grade); ?>&page=<?php echo ($page - 1); ?>"><i class="bi bi-chevron-left"></i> Previous</a>
          <?php else: ?>
            <span class="disabled"><i class="bi bi-chevron-left"></i> Previous</span>
          <?php endif; ?>
          <?php
          $start_page = max(1, $page - 2);
          $end_page = min($total_pages, $page + 2);
          if ($start_page > 1): ?>
            <a href="?keyword=<?php echo urlencode($search_keyword); ?>&state=<?php echo urlencode($search_state); ?>&deadline=<?php echo urlencode($search_deadline); ?>&grade=<?php echo urlencode($search_grade); ?>&page=1">1</a>
            <?php if ($start_page > 2): ?><span>...</span><?php endif; ?>
          <?php endif; ?>
          <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
            <?php if ($i == $page): ?>
              <span class="current"><?php echo $i; ?></span>
            <?php else: ?>
              <a href="?keyword=<?php echo urlencode($search_keyword); ?>&state=<?php echo urlencode($search_state); ?>&deadline=<?php echo urlencode($search_deadline); ?>&grade=<?php echo urlencode($search_grade); ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
            <?php endif; ?>
          <?php endfor; ?>
          <?php if ($end_page < $total_pages): ?>
            <?php if ($end_page < $total_pages - 1): ?><span>...</span><?php endif; ?>
            <a href="?keyword=<?php echo urlencode($search_keyword); ?>&state=<?php echo urlencode($search_state); ?>&deadline=<?php echo urlencode($search_deadline); ?>&grade=<?php echo urlencode($search_grade); ?>&page=<?php echo $total_pages; ?>"><?php echo $total_pages; ?></a>
          <?php endif; ?>
          <?php if ($page < $total_pages): ?>
            <a href="?keyword=<?php echo urlencode($search_keyword); ?>&state=<?php echo urlencode($search_state); ?>&deadline=<?php echo urlencode($search_deadline); ?>&grade=<?php echo urlencode($search_grade); ?>&page=<?php echo ($page + 1); ?>">Next <i class="bi bi-chevron-right"></i></a>
          <?php else: ?>
            <span class="disabled">Next <i class="bi bi-chevron-right"></i></span>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  </section>
</main>
<?php include('components/footer.php'); ?>
<a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>
<script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/vendor/aos/aos.js"></script>
<script src="assets/js/main.js"></script>
<script>
function toggleFavorite(opportunityId, button) {
  const isSaved = button.classList.contains('saved');
  const action = isSaved ? 'remove' : 'add';
  fetch('toggle-favorite.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'opportunity_id=' + opportunityId + '&action=' + action
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      if (action === 'add') {
        button.classList.add('saved');
        button.innerHTML = '<i class="bi bi-heart-fill"></i>';
      } else {
        button.classList.remove('saved');
        button.innerHTML = '<i class="bi bi-heart"></i>';
      }
    } else {
      alert(data.message || 'An error occurred');
    }
  })
  .catch(error => {
    console.error('Error:', error);
    alert('An error occurred');
  });
}
AOS.init({
  duration: 800,
  easing: 'ease-in-out',
  once: true
});
</script>
</body>
</html>
<?php
closeDBConnection($conn);
?>