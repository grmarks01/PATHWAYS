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

// Get user data including secondary contact and notes
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT First_Name, Last_Name, Email, Phone, Role, secondary_contact_name, secondary_contact_email, secondary_contact_phone, secondary_contact_active, notes, Created_Time FROM users WHERE User_Id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Handle note save/update
$note_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_notes') {
    $note_text = trim($_POST['note_text']);
    
    $stmt = $conn->prepare("UPDATE users SET notes = ? WHERE User_Id = ?");
    $stmt->bind_param("si", $note_text, $user_id);
    
    if ($stmt->execute()) {
        $note_message = "Notes saved successfully!";
        $user_data['notes'] = $note_text; // Update the display
    } else {
        $note_message = "Failed to save notes.";
    }
    $stmt->close();
}

// Fetch saved opportunities
$stmt = $conn->prepare("
    SELECT po.* 
    FROM saved_opportunities so
    INNER JOIN pathways_opportunities po ON so.opportunity_id = po.id
    WHERE so.user_id = ? 
    ORDER BY 
        CASE 
            WHEN po.deadlines IS NULL THEN 1
            WHEN po.deadlines < CURDATE() THEN 2
            ELSE 0
        END,
        po.deadlines ASC,
        po.program_name ASC
");

$stmt->bind_param("i", $user_id);
$stmt->execute();
$opportunities = $stmt->get_result();
$stmt->close();

closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>My Profile - Pathways</title>

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
    .profile-section {
      padding: 80px 0 60px;
      min-height: calc(100vh - 200px);
    }
    
    .welcome-banner {
      background: linear-gradient(135deg, #5fcf80 0%, #4ab86a 100%);
      color: white;
      padding: 40px;
      border-radius: 10px;
      margin-bottom: 40px;
    }
    
    .welcome-banner h1 {
      margin: 0 0 10px 0;
      font-size: 32px;
      font-weight: 700;
    }
    
    .welcome-banner p {
      margin: 0;
      opacity: 0.9;
    }
    
    .profile-container {
      display: grid;
      grid-template-columns: 1fr 2fr;
      gap: 30px;
      margin-bottom: 40px;
    }
    
    @media (max-width: 992px) {
      .profile-container {
        grid-template-columns: 1fr;
      }
    }
    
    .profile-card {
      background: #fff;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 0 20px rgba(0, 0, 0, 0.08);
    }
    
    .profile-card h3 {
      color: #333;
      margin-bottom: 25px;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .profile-info-item {
      margin-bottom: 20px;
      padding-bottom: 20px;
      border-bottom: 1px solid #f0f0f0;
    }
    
    .profile-info-item:last-child {
      border-bottom: none;
      margin-bottom: 0;
      padding-bottom: 0;
    }
    
    .profile-info-item label {
      display: block;
      font-weight: 600;
      color: #555;
      margin-bottom: 5px;
      font-size: 13px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    
    .profile-info-item .value {
      color: #333;
      font-size: 16px;
    }
    
    .secondary-contact-section {
      background: #f8f9fa;
      padding: 20px;
      border-radius: 8px;
      margin-top: 20px;
    }
    
    .secondary-contact-section h4 {
      font-size: 14px;
      color: #666;
      margin-bottom: 15px;
      font-weight: 600;
    }
    
    .notes-section {
      background: #fff9e6;
      padding: 20px;
      border-radius: 8px;
      border-left: 4px solid #ffaa00;
    }
    
    .notes-section h4 {
      font-size: 16px;
      color: #333;
      margin-bottom: 10px;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    
    .notes-section p {
      font-size: 13px;
      color: #666;
      margin-bottom: 15px;
    }
    
    .notes-section textarea {
      width: 100%;
      padding: 12px;
      border: 1px solid #ddd;
      border-radius: 5px;
      min-height: 150px;
      resize: vertical;
      font-family: inherit;
      font-size: 14px;
    }
    
    .notes-section textarea:focus {
      outline: none;
      border-color: #ffaa00;
    }
    
    .btn-save-notes {
      margin-top: 10px;
      padding: 10px 20px;
      background: #ffaa00;
      color: white;
      border: none;
      border-radius: 5px;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.3s;
    }
    
    .btn-save-notes:hover {
      background: #e69900;
    }
    
    .note-success {
      margin-top: 10px;
      padding: 10px;
      background: #d4edda;
      border-left: 4px solid #28a745;
      color: #155724;
      border-radius: 5px;
      font-size: 14px;
    }
    
    .btn-edit-profile {
      width: 100%;
      padding: 12px;
      background: #5fcf80;
      color: #fff;
      border: none;
      border-radius: 5px;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.3s;
      margin-top: 20px;
    }
    
    .btn-edit-profile:hover {
      background: #4ab86a;
    }
    
    .btn-logout {
      width: 100%;
      padding: 12px;
      background: #fff;
      color: #ff4444;
      border: 2px solid #ff4444;
      border-radius: 5px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
      margin-top: 10px;
    }
    
    .btn-logout:hover {
      background: #ff4444;
      color: #fff;
    }
    
    .opportunities-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }
    
    .opportunity-card {
      background: #fff;
      padding: 25px;
      border-radius: 10px;
      box-shadow: 0 0 20px rgba(0, 0, 0, 0.08);
      margin-bottom: 20px;
      transition: transform 0.3s, box-shadow 0.3s;
    }
    
    .opportunity-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 5px 30px rgba(0, 0, 0, 0.12);
    }
    
    .opportunity-header {
      display: flex;
      justify-content: space-between;
      align-items: start;
      margin-bottom: 15px;
    }
    
    .opportunity-title {
      font-size: 20px;
      font-weight: 700;
      color: #333;
      margin: 0 0 5px 0;
    }
    
    .opportunity-category {
      display: inline-block;
      color: white;
      padding: 5px 15px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
    }
    
    .category-club {
      background: #5fcf80;
    }
    
    .category-scholarship {
      background: #ffd700;
      color: #333;
    }
    
    .category-competition {
      background: #667eea;
    }
    
    .category-academic-program {
      background: #f093fb;
    }
    
    .category-program {
      background: #764ba2;
    }
    
    .category-default {
      background: #6c757d;
    }
    
    .opportunity-state {
      display: inline-block;
      background: #6c757d;
      color: white;
      padding: 5px 12px;
      border-radius: 20px;
      font-size: 11px;
      font-weight: 600;
      margin-left: 8px;
    }
    
    .opportunity-details {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 15px;
      margin-top: 20px;
    }
    
    .detail-item {
      padding: 12px;
      background: #f8f9fa;
      border-radius: 5px;
    }
    
    .detail-item label {
      display: block;
      font-size: 12px;
      color: #666;
      font-weight: 600;
      margin-bottom: 5px;
      text-transform: uppercase;
    }
    
    .detail-item .value {
      color: #333;
      font-size: 14px;
    }
    
    .opportunity-notes {
      margin-top: 20px;
      padding: 15px;
      background: #fff9e6;
      border-left: 4px solid #ffaa00;
      border-radius: 5px;
    }
    
    .opportunity-notes label {
      font-weight: 600;
      color: #666;
      font-size: 13px;
      display: block;
      margin-bottom: 5px;
    }
    
    .opportunity-actions {
      margin-top: 20px;
      display: flex;
      gap: 10px;
    }
    
    .btn-visit {
      flex: 1;
      padding: 10px 20px;
      background: #5fcf80;
      color: white;
      text-decoration: none;
      border-radius: 5px;
      text-align: center;
      font-weight: 600;
      transition: background 0.3s;
    }
    
    .btn-visit:hover {
      background: #4ab86a;
      color: white;
    }
    
    .btn-remove {
      padding: 10px 20px;
      background: #fff;
      color: #ff4444;
      border: 2px solid #ff4444;
      border-radius: 5px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
    }
    
    .btn-remove:hover {
      background: #ff4444;
      color: white;
    }
    
    .empty-state {
      text-align: center;
      padding: 60px 20px;
      color: #999;
    }
    
    .empty-state i {
      font-size: 64px;
      margin-bottom: 20px;
      opacity: 0.3;
    }
  </style>
</head>

<body>

<?php include('components/header.php'); ?>

<main class="main">
  <section class="profile-section">
    <div class="container">
      
      <!-- Welcome Banner -->
      <div class="welcome-banner" data-aos="fade-down">
        <h1>Welcome back, <?php echo htmlspecialchars($user_data['First_Name']); ?>! 👋</h1>
        <p>Manage your profile and track your saved opportunities</p>
      </div>
      
      <!-- Profile Information -->
      <div class="profile-container">
        
        <!-- Profile Details Card -->
        <div class="profile-card" data-aos="fade-right">
          <h3><i class="bi bi-person-circle"></i> Profile Information</h3>
          
          <div class="profile-info-item">
            <label>Full Name</label>
            <div class="value"><?php echo htmlspecialchars($user_data['First_Name'] . ' ' . $user_data['Last_Name']); ?></div>
          </div>
          
          <div class="profile-info-item">
            <label>Email Address</label>
            <div class="value"><?php echo htmlspecialchars($user_data['Email']); ?></div>
          </div>
          
          <div class="profile-info-item">
            <label>Phone Number</label>
            <div class="value"><?php echo htmlspecialchars($user_data['Phone'] ?: 'Not provided'); ?></div>
          </div>
          
          <div class="profile-info-item">
            <label>Account Type</label>
            <div class="value"><?php echo htmlspecialchars(ucfirst($user_data['Role'])); ?></div>
          </div>
          
          <?php if ($user_data['secondary_contact_active']): ?>
          <div class="secondary-contact-section">
            <h4><i class="bi bi-people"></i> Secondary Contact</h4>
            <?php if ($user_data['secondary_contact_name']): ?>
            <div class="profile-info-item">
              <label>Name</label>
              <div class="value"><?php echo htmlspecialchars($user_data['secondary_contact_name']); ?></div>
            </div>
            <?php endif; ?>
            <?php if ($user_data['secondary_contact_email']): ?>
            <div class="profile-info-item">
              <label>Email</label>
              <div class="value"><?php echo htmlspecialchars($user_data['secondary_contact_email']); ?></div>
            </div>
            <?php endif; ?>
            <?php if ($user_data['secondary_contact_phone']): ?>
            <div class="profile-info-item">
              <label>Phone</label>
              <div class="value"><?php echo htmlspecialchars($user_data['secondary_contact_phone']); ?></div>
            </div>
            <?php endif; ?>
          </div>
          <?php endif; ?>
          
          <button class="btn-edit-profile" onclick="location.href='edit-profile.php'">
            <i class="bi bi-pencil-square"></i> Edit Profile
          </button>
          
          <button class="btn-logout" onclick="location.href='logout.php'">
            <i class="bi bi-box-arrow-right"></i> Logout
          </button>
        </div>
        
        <!-- Quick Stats & Notes Card -->
        <div class="profile-card" data-aos="fade-left">
          <h3><i class="bi bi-bar-chart"></i> Your Stats</h3>
          
          <div class="profile-info-item">
            <label>Saved Opportunities</label>
            <div class="value"><?php echo $opportunities->num_rows; ?> programs</div>
          </div>
          
          <div class="profile-info-item">
            <label>Member Since</label>
            <div class="value"><?php echo date('M d, Y', strtotime($user_data['Created_Time'])); ?></div>
          </div>
          
          <div class="profile-info-item">
            <label>Account Status</label>
            <div class="value" style="color: #00cc66;">✓ Active</div>
          </div>
          
          <!-- Personal Notes Section -->
          <div class="notes-section">
            <h4><i class="bi bi-journal-text"></i> My Personal Notes</h4>
            <p>Keep track of your goals, reminders, or anything else you'd like to remember.</p>
            <form method="POST">
              <input type="hidden" name="action" value="save_notes">
              <textarea name="note_text" placeholder="Write your personal notes here..."><?php echo htmlspecialchars($user_data['notes'] ?? ''); ?></textarea>
              <button type="submit" class="btn-save-notes">
                <i class="bi bi-save"></i> Save Notes
              </button>
            </form>
            <?php if ($note_message): ?>
            <div class="note-success">
              <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($note_message); ?>
            </div>
            <?php endif; ?>
          </div>
        </div>
        
      </div>
      
      <!-- Saved Opportunities -->
      <div class="profile-card" data-aos="fade-up">
        <div class="opportunities-header">
          <h3><i class="bi bi-bookmark-star"></i> My Saved Opportunities</h3>
        </div>
        
        <?php if ($opportunities->num_rows > 0): ?>
          <?php while ($opp = $opportunities->fetch_assoc()): ?>
            <div class="opportunity-card">
              <div class="opportunity-header">
                <div>
                  <h4 class="opportunity-title"><?php echo htmlspecialchars($opp['program_name']); ?></h4>
                  <?php 
                  // Determine badge color based on category
                  $category_class = 'category-default';
                  $category = $opp['category'];
                  
                  if ($category === 'Club') {
                      $category_class = 'category-club';
                  } elseif ($category === 'Scholarship') {
                      $category_class = 'category-scholarship';
                  } elseif ($category === 'Competition') {
                      $category_class = 'category-competition';
                  } elseif ($category === 'Academic Program') {
                      $category_class = 'category-academic-program';
                  } elseif ($category === 'Program') {
                      $category_class = 'category-program';
                  }
                  ?>
                  <span class="opportunity-category <?php echo $category_class; ?>"><?php echo htmlspecialchars($opp['category']); ?></span>
                  <span class="opportunity-state"><?php echo htmlspecialchars($opp['state']); ?></span>
                </div>
              </div>
              
              <div class="opportunity-details">
                <div class="detail-item">
                  <label>Grade Levels</label>
                  <div class="value"><?php echo htmlspecialchars($opp['grade_levels'] ?: 'Not specified'); ?></div>
                </div>
                
                <div class="detail-item">
                  <label>Field</label>
                  <div class="value"><?php echo htmlspecialchars($opp['field'] ?: 'Not specified'); ?></div>
                </div>
                
                <div class="detail-item">
                  <label>Cost</label>
                  <div class="value"><?php echo $opp['cost_funding'] ? '$' . number_format($opp['cost_funding'], 2) : 'Free/Not specified'; ?></div>
                </div>
                
                <div class="detail-item">
                  <label>Deadline</label>
                  <div class="value"><?php echo $opp['deadlines'] ? date('M d, Y', strtotime($opp['deadlines'])) : 'Rolling/Not specified'; ?></div>
                </div>
                
                <div class="detail-item">
                  <label>Delivery</label>
                  <div class="value"><?php echo htmlspecialchars($opp['delivery_context'] ?: 'Not specified'); ?></div>
                </div>
                
                <div class="detail-item">
                  <label>Eligibility</label>
                  <div class="value"><?php echo htmlspecialchars($opp['eligibility'] ?: 'Not specified'); ?></div>
                </div>
              </div>
              
              <?php if (!empty($opp['notes'])): ?>
              <div class="opportunity-notes">
                <label><i class="bi bi-sticky"></i> Program Notes</label>
                <div><?php echo htmlspecialchars($opp['notes']); ?></div>
              </div>
              <?php endif; ?>
              
              <div class="opportunity-actions">
                <?php if (!empty($opp['website_link'])): 
                  $website_url = $opp['website_link'];
                  // Add https:// if no protocol is specified
                  if (!preg_match("~^(?:f|ht)tps?://~i", $website_url)) {
                      $website_url = "https://" . $website_url;
                  }
                ?>
                <a href="<?php echo htmlspecialchars($website_url); ?>" target="_blank" class="btn-visit">
                  <i class="bi bi-box-arrow-up-right"></i> Visit Website
                </a>
                <?php endif; ?>
                <button class="btn-remove" onclick="removeOpportunity(<?php echo $opp['id']; ?>)">
                  <i class="bi bi-trash"></i> Remove
                </button>
              </div>
            </div>
          <?php endwhile; ?>
        <?php else: ?>
          <div class="empty-state">
            <i class="bi bi-bookmark"></i>
            <h4>No Saved Opportunities Yet</h4>
            <p>Start exploring and save opportunities that interest you!</p>
          </div>
        <?php endif; ?>
      </div>
      
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
function removeOpportunity(id) {
  if (confirm('Are you sure you want to remove this opportunity?')) {
    window.location.href = 'remove-opportunity.php?id=' + id;
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