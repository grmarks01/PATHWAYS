<?php
session_start();

// Check if user is logged in and is a teacher (or admin)
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login-page.php");
    exit();
}

// Only allow teachers to access this page
if ($_SESSION['user_role'] !== 'teacher') {
    header("Location: index.php");
    exit();
}

// Include database configuration
require_once 'components/db-config.php';
$conn = getDBConnection();

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $submission_id = (int)$_POST['submission_id'];
    $new_status = $_POST['status'];
    $admin_notes = trim($_POST['admin_notes']);
    
    $stmt = $conn->prepare("UPDATE contact_submissions SET status = ?, admin_notes = ? WHERE id = ?");
    $stmt->bind_param("ssi", $new_status, $admin_notes, $submission_id);
    $stmt->execute();
    $stmt->close();
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$inquiry_filter = isset($_GET['inquiry']) ? $_GET['inquiry'] : 'all';

// Build query
$sql = "SELECT * FROM contact_submissions WHERE 1=1";
$params = [];
$types = "";

if ($status_filter !== 'all') {
    $sql .= " AND status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if ($inquiry_filter !== 'all') {
    $sql .= " AND inquiry_type = ?";
    $params[] = $inquiry_filter;
    $types .= "s";
}

$sql .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Contact Submissions - Pathways Admin</title>

  <!-- Favicons -->
  <link href="assets/img/favicon.png" rel="icon">

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800&family=Poppins:wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&display=swap" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">

  <!-- Main CSS File -->
  <link href="assets/css/main.css" rel="stylesheet">

  <style>
    .admin-section {
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
    
    .filters-card {
      background: white;
      padding: 25px;
      border-radius: 10px;
      box-shadow: 0 0 20px rgba(0, 0, 0, 0.08);
      margin-bottom: 30px;
    }
    
    .filter-group {
      display: inline-block;
      margin-right: 20px;
    }
    
    .filter-group label {
      font-weight: 600;
      margin-right: 10px;
      color: #333;
    }
    
    .filter-group select {
      padding: 8px 15px;
      border: 1px solid #ddd;
      border-radius: 5px;
    }
    
    .submission-card {
      background: white;
      padding: 25px;
      border-radius: 10px;
      box-shadow: 0 0 20px rgba(0, 0, 0, 0.08);
      margin-bottom: 20px;
    }
    
    .submission-header {
      display: flex;
      justify-content: space-between;
      align-items: start;
      margin-bottom: 15px;
      padding-bottom: 15px;
      border-bottom: 2px solid #f0f0f0;
    }
    
    .submission-title {
      font-size: 18px;
      font-weight: 700;
      color: #333;
      margin-bottom: 5px;
    }
    
    .submission-meta {
      font-size: 13px;
      color: #666;
    }
    
    .status-badge {
      padding: 5px 15px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
    }
    
    .status-new { background: #fff3cd; color: #856404; }
    .status-in_progress { background: #cfe2ff; color: #084298; }
    .status-resolved { background: #d1e7dd; color: #0f5132; }
    .status-closed { background: #e2e3e5; color: #41464b; }
    
    .inquiry-badge {
      display: inline-block;
      padding: 4px 12px;
      border-radius: 15px;
      font-size: 11px;
      font-weight: 600;
      margin-left: 10px;
    }
    
    .inquiry-suggest { background: #5fcf80; color: white; }
    .inquiry-support { background: #667eea; color: white; }
    .inquiry-general { background: #6c757d; color: white; }
    .inquiry-partnership { background: #f093fb; color: white; }
    .inquiry-feedback { background: #17a2b8; color: white; }
    
    .submission-content {
      margin-bottom: 20px;
    }
    
    .content-row {
      display: grid;
      grid-template-columns: 150px 1fr;
      margin-bottom: 10px;
    }
    
    .content-label {
      font-weight: 600;
      color: #555;
    }
    
    .content-value {
      color: #333;
    }
    
    .opportunity-details {
      background: #f8f9fa;
      padding: 15px;
      border-radius: 5px;
      margin-top: 15px;
    }
    
    .opportunity-details h5 {
      font-size: 14px;
      font-weight: 600;
      color: #333;
      margin-bottom: 10px;
    }
    
    .admin-actions {
      background: #f8f9fa;
      padding: 20px;
      border-radius: 5px;
      margin-top: 15px;
    }
    
    .admin-actions h5 {
      font-size: 14px;
      font-weight: 600;
      color: #333;
      margin-bottom: 15px;
    }
    
    .action-form {
      display: grid;
      grid-template-columns: 200px 1fr auto;
      gap: 15px;
      align-items: start;
    }
    
    .action-form select,
    .action-form textarea {
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 5px;
    }
    
    .action-form textarea {
      min-height: 80px;
      resize: vertical;
    }
    
    .btn-update {
      padding: 10px 25px;
      background: #5fcf80;
      color: white;
      border: none;
      border-radius: 5px;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.3s;
    }
    
    .btn-update:hover {
      background: #4ab86a;
    }
    
    .stats-row {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }
    
    .stat-card {
      background: white;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 0 20px rgba(0, 0, 0, 0.08);
      text-align: center;
    }
    
    .stat-number {
      font-size: 32px;
      font-weight: 700;
      color: #5fcf80;
      margin-bottom: 5px;
    }
    
    .stat-label {
      font-size: 14px;
      color: #666;
    }
	
	
/* TABLET - Hide non-essential columns */
@media (max-width: 1024px) {
  .users-table {
    font-size: 13px;
  }
  
  .users-table th,
  .users-table td {
    padding: 12px 8px;
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
}

/* MEDIUM TABLET/SMALL LAPTOP */
@media (max-width: 768px) {
  .users-table-container {
    padding: 15px;
  }
  
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
  
  .btn-action {
    padding: 6px 10px;
    font-size: 11px;
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

/* Adjust search bar on mobile */
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
}

/* Contact Submissions Mobile Styles */
@media (max-width: 768px) {
  .filters-card {
    padding: 15px;
  }
  
  .filter-group {
    display: block;
    margin-right: 0;
    margin-bottom: 15px;
    width: 100%;
  }
  
  .filter-group label {
    display: block;
    margin-bottom: 8px;
  }
  
  .filter-group select {
    width: 100%;
    padding: 12px;
  }
  
  .stats-row {
    grid-template-columns: 1fr 1fr;
    gap: 15px;
  }
  
  .submission-card {
    padding: 15px;
  }
  
  .submission-header {
    flex-direction: column;
    align-items: start;
  }
  
  .submission-title {
    font-size: 16px;
    margin-bottom: 10px;
  }
  
  .status-badge {
    margin-top: 10px;
  }
  
  .content-row {
    grid-template-columns: 1fr;
    gap: 5px;
  }
  
  .content-label {
    font-size: 13px;
  }
  
  .action-form {
    grid-template-columns: 1fr;
    gap: 15px;
  }
  
  .action-form select,
  .action-form textarea,
  .btn-update {
    width: 100%;
  }
}

@media (max-width: 480px) {
  .stats-row {
    grid-template-columns: 1fr;
  }
  
  .submission-meta {
    font-size: 12px;
    word-break: break-word;
  }
  
  .inquiry-badge {
    display: block;
    margin-left: 0;
    margin-top: 8px;
    width: fit-content;
  }
  
  .opportunity-details {
    padding: 12px;
  }
  
  .admin-actions {
    padding: 15px;
  }
}
  </style>
</head>

<body>

<?php include('components/header.php'); ?>

<main class="main">
  <section class="admin-section">
    <div class="container">
      
      <!-- Page Header -->
      <div class="page-header" data-aos="fade-down">
        <h1><i class="bi bi-inbox"></i> Contact Submissions</h1>
        <p>Review and manage contact form submissions and opportunity suggestions</p>
      </div>
      
      <!-- Stats -->
      <?php
      $stats = $conn->query("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new_count,
        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_count,
        SUM(CASE WHEN inquiry_type = 'suggest' THEN 1 ELSE 0 END) as suggestions_count
        FROM contact_submissions")->fetch_assoc();
      ?>
      <div class="stats-row">
        <div class="stat-card" data-aos="fade-up" data-aos-delay="100">
          <div class="stat-number"><?php echo $stats['total']; ?></div>
          <div class="stat-label">Total Submissions</div>
        </div>
        <div class="stat-card" data-aos="fade-up" data-aos-delay="200">
          <div class="stat-number"><?php echo $stats['new_count']; ?></div>
          <div class="stat-label">New</div>
        </div>
        <div class="stat-card" data-aos="fade-up" data-aos-delay="300">
          <div class="stat-number"><?php echo $stats['in_progress_count']; ?></div>
          <div class="stat-label">In Progress</div>
        </div>
        <div class="stat-card" data-aos="fade-up" data-aos-delay="400">
          <div class="stat-number"><?php echo $stats['suggestions_count']; ?></div>
          <div class="stat-label">Opportunity Suggestions</div>
        </div>
      </div>
      
      <!-- Filters -->
      <div class="filters-card" data-aos="fade-up">
        <form method="GET" action="">
          <div class="filter-group">
            <label>Status:</label>
            <select name="status" onchange="this.form.submit()">
              <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All</option>
              <option value="new" <?php echo $status_filter === 'new' ? 'selected' : ''; ?>>New</option>
              <option value="in_progress" <?php echo $status_filter === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
              <option value="resolved" <?php echo $status_filter === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
              <option value="closed" <?php echo $status_filter === 'closed' ? 'selected' : ''; ?>>Closed</option>
            </select>
          </div>
          
          <div class="filter-group">
            <label>Inquiry Type:</label>
            <select name="inquiry" onchange="this.form.submit()">
              <option value="all" <?php echo $inquiry_filter === 'all' ? 'selected' : ''; ?>>All</option>
              <option value="general" <?php echo $inquiry_filter === 'general' ? 'selected' : ''; ?>>General</option>
              <option value="support" <?php echo $inquiry_filter === 'support' ? 'selected' : ''; ?>>Support</option>
              <option value="suggest" <?php echo $inquiry_filter === 'suggest' ? 'selected' : ''; ?>>Suggestions</option>
              <option value="partnership" <?php echo $inquiry_filter === 'partnership' ? 'selected' : ''; ?>>Partnership</option>
              <option value="feedback" <?php echo $inquiry_filter === 'feedback' ? 'selected' : ''; ?>>Feedback</option>
            </select>
          </div>
        </form>
      </div>
      
      <!-- Submissions List -->
      <?php if ($result->num_rows > 0): ?>
        <?php while ($submission = $result->fetch_assoc()): ?>
        <div class="submission-card" data-aos="fade-up">
          <div class="submission-header">
            <div>
              <div class="submission-title">
                <?php echo htmlspecialchars($submission['subject']); ?>
                <span class="inquiry-badge inquiry-<?php echo $submission['inquiry_type']; ?>">
                  <?php echo ucfirst(str_replace('_', ' ', $submission['inquiry_type'])); ?>
                </span>
              </div>
              <div class="submission-meta">
                <i class="bi bi-person"></i> <?php echo htmlspecialchars($submission['name']); ?> 
                (<a href="mailto:<?php echo htmlspecialchars($submission['email']); ?>"><?php echo htmlspecialchars($submission['email']); ?></a>) 
                | <i class="bi bi-calendar"></i> <?php echo date('M d, Y g:i A', strtotime($submission['created_at'])); ?>
              </div>
            </div>
            <span class="status-badge status-<?php echo $submission['status']; ?>">
              <?php echo ucfirst(str_replace('_', ' ', $submission['status'])); ?>
            </span>
          </div>
          
          <div class="submission-content">
            <div class="content-row">
              <div class="content-label">Message:</div>
              <div class="content-value"><?php echo nl2br(htmlspecialchars($submission['message'])); ?></div>
            </div>
            
            <?php if ($submission['inquiry_type'] === 'suggest' && !empty($submission['opportunity_name'])): ?>
            <div class="opportunity-details">
              <h5><i class="bi bi-lightbulb"></i> Suggested Opportunity Details</h5>
              <?php if (!empty($submission['opportunity_name'])): ?>
              <div class="content-row">
                <div class="content-label">Name:</div>
                <div class="content-value"><?php echo htmlspecialchars($submission['opportunity_name']); ?></div>
              </div>
              <?php endif; ?>
              <?php if (!empty($submission['opportunity_state'])): ?>
              <div class="content-row">
                <div class="content-label">State:</div>
                <div class="content-value"><?php echo htmlspecialchars($submission['opportunity_state']); ?></div>
              </div>
              <?php endif; ?>
              <?php if (!empty($submission['opportunity_category'])): ?>
              <div class="content-row">
                <div class="content-label">Category:</div>
                <div class="content-value"><?php echo htmlspecialchars($submission['opportunity_category']); ?></div>
              </div>
              <?php endif; ?>
              <?php if (!empty($submission['opportunity_website'])): ?>
              <div class="content-row">
                <div class="content-label">Website:</div>
                <div class="content-value">
                  <a href="<?php echo htmlspecialchars($submission['opportunity_website']); ?>" target="_blank">
                    <?php echo htmlspecialchars($submission['opportunity_website']); ?>
                  </a>
                </div>
              </div>
              <?php endif; ?>
            </div>
            <?php endif; ?>
          </div>
          
          <div class="admin-actions">
            <h5><i class="bi bi-gear"></i> Admin Actions</h5>
            <form method="POST" action="" class="action-form">
              <input type="hidden" name="submission_id" value="<?php echo $submission['id']; ?>">
              <input type="hidden" name="update_status" value="1">
              
              <select name="status">
                <option value="new" <?php echo $submission['status'] === 'new' ? 'selected' : ''; ?>>New</option>
                <option value="in_progress" <?php echo $submission['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                <option value="resolved" <?php echo $submission['status'] === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                <option value="closed" <?php echo $submission['status'] === 'closed' ? 'selected' : ''; ?>>Closed</option>
              </select>
              
              <textarea name="admin_notes" placeholder="Add admin notes..."><?php echo htmlspecialchars($submission['admin_notes']); ?></textarea>
              
              <button type="submit" class="btn-update">
                <i class="bi bi-save"></i> Update
              </button>
            </form>
          </div>
        </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="submission-card" style="text-align: center; padding: 60px;">
          <i class="bi bi-inbox" style="font-size: 64px; color: #ddd; margin-bottom: 20px;"></i>
          <h3 style="color: #666;">No submissions found</h3>
          <p style="color: #999;">Try adjusting your filters or check back later.</p>
        </div>
      <?php endif; ?>
      
    </div>
  </section>
</main>

<?php include('components/footer.php'); ?>

<!-- Scroll Top -->
<a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

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
<?php
$stmt->close();
$conn->close();
?>