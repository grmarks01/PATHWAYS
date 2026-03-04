<?php
session_start();

// Check if user is logged in
$is_logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$user_id = $is_logged_in ? $_SESSION['user_id'] : null;

// Include database configuration
require_once 'components/db-config.php';
$conn = getDBConnection();

// Get recently updated/added opportunities (last 30 days)
$sql = "SELECT po.*, ";
if ($is_logged_in) {
    $sql .= "(SELECT COUNT(*) FROM saved_opportunities WHERE user_id = ? AND opportunity_id = po.id) as is_saved ";
} else {
    $sql .= "0 as is_saved ";
}
$sql .= "FROM pathways_opportunities po 
         WHERE po.updated_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
         ORDER BY po.updated_at DESC 
         LIMIT 6";

$stmt = $conn->prepare($sql);
if ($is_logged_in) {
    $stmt->bind_param("i", $user_id);
}
$stmt->execute();
$result = $stmt->get_result();

$recent_opportunities = [];
while ($row = $result->fetch_assoc()) {
    $recent_opportunities[] = $row;
}
$stmt->close();

// Get some statistics
$stats_query = "SELECT 
    COUNT(*) as total_opportunities,
    COUNT(DISTINCT state) as total_states,
    COUNT(DISTINCT category) as total_categories
    FROM pathways_opportunities";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();

closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Home - Pathways</title>
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
    .hero.section {
      padding: 120px 0 80px;
    }
    
    .hero h2 {
      font-size: 48px;
      margin-bottom: 20px;
    }
    
    .hero p {
      font-size: 20px;
      margin-bottom: 30px;
    }
    
    .search-box-hero {
      background: white;
      padding: 30px;
      border-radius: 15px;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
      max-width: 800px;
      margin: 0 auto;
    }
    
    .search-form {
      display: flex;
      gap: 10px;
    }
    
    .search-form input {
      flex: 1;
      padding: 15px 20px;
      border: 2px solid #ddd;
      border-radius: 8px;
      font-size: 16px;
    }
    
    .search-form input:focus {
      outline: none;
      border-color: #5fcf80;
    }
    
    .search-form button {
      padding: 15px 35px;
      background: #5fcf80;
      color: white;
      border: none;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.3s;
      font-size: 16px;
    }
    
    .search-form button:hover {
      background: #4ab86a;
    }
    
    .quick-links {
      display: flex;
      gap: 15px;
      justify-content: center;
      margin-top: 20px;
      flex-wrap: wrap;
    }
    
    .quick-link {
      padding: 8px 20px;
      background: rgba(255, 255, 255, 0.2);
      color: white;
      text-decoration: none;
      border-radius: 20px;
      font-size: 14px;
      transition: all 0.3s;
    }
    
    .quick-link:hover {
      background: rgba(255, 255, 255, 0.3);
      color: white;
      transform: translateY(-2px);
    }
    
    .stats-section {
      background: #f8f9fa;
      padding: 60px 0;
    }
    
    .stat-card {
      text-align: center;
      padding: 30px;
      background: white;
      border-radius: 10px;
      box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
      transition: transform 0.3s;
    }
    
    .stat-card:hover {
      transform: translateY(-5px);
    }
    
    .stat-card i {
      font-size: 48px;
      color: #5fcf80;
      margin-bottom: 15px;
    }
    
    .stat-number {
      font-size: 36px;
      font-weight: 700;
      color: #333;
      margin-bottom: 10px;
    }
    
    .stat-label {
      font-size: 16px;
      color: #666;
    }
    
    .categories-section {
      padding: 80px 0;
    }
    
    .section-title h2 {
      font-size: 36px;
      font-weight: 700;
      color: #333;
      margin-bottom: 10px;
    }
    
    .section-title p {
      font-size: 18px;
      color: #666;
    }
    
    .category-card {
      text-align: center;
      padding: 40px 20px;
      background: white;
      border-radius: 10px;
      box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
      transition: all 0.3s;
      text-decoration: none;
      display: block;
      height: 100%;
    }
    
    .category-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12);
    }
    
    .category-card i {
      font-size: 48px;
      margin-bottom: 20px;
    }
    
    .category-card h4 {
      font-size: 20px;
      font-weight: 600;
      color: #333;
      margin-bottom: 10px;
    }
    
    .category-card p {
      font-size: 14px;
      color: #666;
      margin: 0;
    }
    
    .category-card.club i { color: #5fcf80; }
    .category-card.scholarship i { color: #ffd700; }
    .category-card.competition i { color: #667eea; }
    .category-card.program i { color: #f093fb; }
    
    .recent-section {
      padding: 80px 0;
      background: #f8f9fa;
    }
    
    .opportunity-card {
      background: white;
      padding: 25px;
      border-radius: 10px;
      box-shadow: 0 0 20px rgba(0, 0, 0, 0.08);
      transition: transform 0.3s, box-shadow 0.3s;
      position: relative;
      height: 420px;
      width: 100%;
      display: flex;
      flex-direction: column;
    }
    
    .recent-section .row {
      align-items: stretch;
    }
    
    .recent-section .col-lg-4 {
      display: flex;
      margin-bottom: 30px;
      flex: 0 0 33.333333%;
      max-width: 33.333333%;
    }
    
    @media (max-width: 991px) {
      .recent-section .col-lg-4 {
        flex: 0 0 50%;
        max-width: 50%;
      }
    }
    
    @media (max-width: 767px) {
      .recent-section .col-lg-4 {
        flex: 0 0 100%;
        max-width: 100%;
      }
    }
    
    .opportunity-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 5px 30px rgba(0, 0, 0, 0.12);
    }
    
    .favorite-btn {
      position: absolute;
      top: 20px;
      right: 20px;
      background: white;
      border: 2px solid #ddd;
      border-radius: 50%;
      width: 45px;
      height: 45px;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: all 0.3s;
      font-size: 20px;
      color: #999;
    }
    
    .favorite-btn:hover {
      border-color: #5fcf80;
      color: #5fcf80;
      transform: scale(1.1);
    }
    
    .favorite-btn.saved {
      background: #5fcf80;
      border-color: #5fcf80;
      color: white;
    }
    
    .favorite-btn.saved:hover {
      background: #4ab86a;
    }
    
    .opportunity-title {
      font-size: 18px;
      font-weight: 700;
      color: #333;
      margin: 0 60px 15px 0;
      height: 88px;
      display: -webkit-box;
      -webkit-line-clamp: 4;
      -webkit-box-orient: vertical;
      overflow: hidden;
      line-height: 22px;
    }
    
    .opportunity-badges {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
      margin-bottom: 20px;
      height: 56px;
      align-content: flex-start;
      overflow: hidden;
    }
    
    .badge {
      padding: 4px 10px;
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
    .badge-state { background: #6c757d; color: white; }
    .badge-field { background: #17a2b8; color: white; }
    .badge-updated { background: #ff6b6b; color: white; }
    
    .opportunity-details {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 10px;
      margin-top: auto;
      padding-top: 15px;
      border-top: 1px solid #f0f0f0;
    }
    
    .detail-item {
      padding: 10px;
      background: #f8f9fa;
      border-radius: 5px;
    }
    
    .detail-item label {
      display: block;
      font-size: 10px;
      color: #666;
      font-weight: 600;
      margin-bottom: 5px;
      text-transform: uppercase;
    }
    
    .detail-item .value {
      color: #333;
      font-size: 13px;
    }
    
    .opportunity-actions {
      margin-top: 15px;
      padding-top: 15px;
      border-top: 1px solid #f0f0f0;
    }
    
    .btn-visit {
      display: block;
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
    
    .btn-view-all {
      display: inline-block;
      padding: 15px 40px;
      background: #5fcf80;
      color: white;
      text-decoration: none;
      border-radius: 8px;
      font-weight: 600;
      transition: all 0.3s;
      margin-top: 30px;
    }
    
    .btn-view-all:hover {
      background: #4ab86a;
      color: white;
      transform: translateY(-2px);
    }
    
    .cta-section {
      padding: 80px 0;
      background: linear-gradient(135deg, #5fcf80 0%, #4ab86a 100%);
      color: white;
      text-align: center;
    }
    
    .cta-section h2 {
      font-size: 36px;
      margin-bottom: 20px;
    }
    
    .cta-section p {
      font-size: 18px;
      margin-bottom: 30px;
      opacity: 0.9;
    }
    
    .btn-cta {
      display: inline-block;
      padding: 15px 40px;
      background: white;
      color: #5fcf80;
      text-decoration: none;
      border-radius: 8px;
      font-weight: 600;
      transition: all 0.3s;
      margin: 0 10px;
    }
    
    .btn-cta:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
      color: #5fcf80;
    }
    
    .btn-cta.secondary {
      background: transparent;
      border: 2px solid white;
      color: white;
    }
    
    .btn-cta.secondary:hover {
      background: white;
      color: #5fcf80;
    }

    @media (max-width: 768px) {
      .search-form {
        flex-direction: column;
      }
      
      .hero h2 {
        font-size: 32px;
      }
      
      .hero p {
        font-size: 16px;
      }
	  
	  .section-title {
  text-align: center;
  margin-bottom: 40px;
  word-wrap: break-word;
  overflow-wrap: break-word;
  white-space: normal;
}

.section-title h2 {
  font-size: 36px;
  font-weight: 700;
  color: #333;
  margin-bottom: 10px;
  word-wrap: break-word;
  overflow-wrap: break-word;
  line-height: 1.3;
}

.section-title p {
  font-size: 18px;
  color: #666;
  word-wrap: break-word;
  overflow-wrap: break-word;
  line-height: 1.4;
}

@media (max-width: 768px) {
  .section-title h2 {
    font-size: 24px;
    line-height: 1.3;
  }
  
  .section-title p {
    font-size: 16px;
    line-height: 1.4;
  }
}

@media (max-width: 480px) {
  .section-title h2 {
    font-size: 20px;
    line-height: 1.3;
  }
  
  .section-title p {
    font-size: 14px;
    line-height: 1.4;
  }
}
  </style>
</head>

<body class="index-page">

<?php include('components/header.php'); ?>

  <main class="main">

    <!-- Hero Section -->
    <section id="hero" class="hero section dark-background">
      <img src="assets/img/hero-bg.jpg" alt="" data-aos="fade-in">

      <div class="container">
        <h2 data-aos="fade-up" data-aos-delay="100">Discover Your Path to Success</h2>
        <p data-aos="fade-up" data-aos-delay="200">Explore opportunities for scholarships, competitions, clubs, and academic programs across the United States</p>
        
        <div class="search-box-hero" data-aos="fade-up" data-aos-delay="300">
          <form action="search-all.php" method="GET" class="search-form">
            <input type="text" name="keyword" placeholder="Search for opportunities, programs, or activities...">
            <button type="submit"><i class="bi bi-search"></i> Search</button>
          </form>
          
          <div class="quick-links">
            <a href="scholarships.php" class="quick-link"><i class="bi bi-award"></i> Scholarships</a>
            <a href="events.php" class="quick-link"><i class="bi bi-trophy"></i> Competitions</a>
            <a href="clubs.php" class="quick-link"><i class="bi bi-people"></i> Clubs</a>
            <a href="events.php" class="quick-link"><i class="bi bi-mortarboard"></i> Programs</a>
          </div>
        </div>
      </div>
    </section><!-- /Hero Section -->

    <!-- Stats Section -->
    <section class="stats-section">
      <div class="container">
        <div class="row gy-4">
          <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="100">
            <div class="stat-card">
              <i class="bi bi-lightbulb"></i>
              <div class="stat-number"><?php echo number_format($stats['total_opportunities']); ?></div>
              <div class="stat-label">Opportunities</div>
            </div>
          </div>
          
          <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="200">
            <div class="stat-card">
              <i class="bi bi-pin-map"></i>
              <div class="stat-number"><?php echo $stats['total_states']; ?></div>
              <div class="stat-label">States Covered</div>
            </div>
          </div>
          
          <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="300">
            <div class="stat-card">
              <i class="bi bi-grid"></i>
              <div class="stat-number"><?php echo $stats['total_categories']; ?></div>
              <div class="stat-label">Categories</div>
            </div>
          </div>
        </div>
      </div>
    </section><!-- /Stats Section -->

    <!-- Categories Section -->
    <section class="categories-section">
      <div class="container">
        <div class="container section-title" data-aos="fade-up">
          <h2>Browse by Category</h2>
          <p>Find the perfect opportunity for your interests and goals</p>
        </div>
        
        <div class="row gy-4">
          <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="100">
            <a href="clubs.php" class="category-card club">
              <i class="bi bi-people-fill"></i>
              <h4>Clubs & Organizations</h4>
              <p>Join student-led clubs and build lasting connections</p>
            </a>
          </div>
          
          <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="200">
            <a href="scholarships.php" class="category-card scholarship">
              <i class="bi bi-award-fill"></i>
              <h4>Scholarships</h4>
              <p>Financial aid and recognition programs</p>
            </a>
          </div>
          
          <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="300">
            <a href="events.php" class="category-card competition">
              <i class="bi bi-trophy-fill"></i>
              <h4>Competitions</h4>
              <p>Showcase your skills and win awards</p>
            </a>
          </div>
          
          <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="400">
            <a href="events.php" class="category-card program">
              <i class="bi bi-mortarboard-fill"></i>
              <h4>Academic Programs</h4>
              <p>Summer camps, dual enrollment, and more</p>
            </a>
          </div>
        </div>
      </div>
    </section><!-- /Categories Section -->

    <!-- Recent Opportunities Section -->
    <section class="recent-section">
      <div class="container">
        <div class="container section-title" data-aos="fade-up">
          <h2>Recently Updated</h2>
          <p>Check out the latest opportunities added to our database</p>
        </div>
        
        <?php if (count($recent_opportunities) > 0): ?>
        <div class="row">
          <?php foreach ($recent_opportunities as $index => $opp): ?>
          <div class="col-lg-4 col-md-6 col-sm-12 mb-4" data-aos="fade-up" data-aos-delay="<?php echo ($index % 3) * 100 + 100; ?>">
            <div class="opportunity-card">
              
              <?php if ($is_logged_in): ?>
              <button class="favorite-btn <?php echo $opp['is_saved'] ? 'saved' : ''; ?>" onclick="event.stopPropagation(); toggleFavorite(<?php echo $opp['id']; ?>, this)">
                <i class="bi bi-heart<?php echo $opp['is_saved'] ? '-fill' : ''; ?>"></i>
              </button>
              <?php endif; ?>
              
              <h3 class="opportunity-title"><?php echo htmlspecialchars($opp['program_name']); ?></h3>
              
              <div class="opportunity-badges">
                <?php 
                // Determine badge color based on category
                $category_class = 'badge-default';
                $category = $opp['category'];
                
                if ($category === 'Club') {
                    $category_class = 'badge-club';
                } elseif ($category === 'Scholarship') {
                    $category_class = 'badge-scholarship';
                } elseif ($category === 'Competition') {
                    $category_class = 'badge-competition';
                } elseif ($category === 'Academic Program') {
                    $category_class = 'badge-academic-program';
                } elseif ($category === 'Program') {
                    $category_class = 'badge-program';
                }
                ?>
                <?php if ($opp['category']): ?>
                  <span class="badge <?php echo $category_class; ?>"><?php echo htmlspecialchars($opp['category']); ?></span>
                <?php endif; ?>
                <span class="badge badge-state"><?php echo htmlspecialchars($opp['state']); ?></span>
                <span class="badge badge-updated"><i class="bi bi-clock"></i> Updated</span>
              </div>
              
              <div class="opportunity-details">
                <div class="detail-item">
                  <label>Grade Levels</label>
                  <div class="value"><?php echo htmlspecialchars($opp['grade_levels'] ?: 'Not specified'); ?></div>
                </div>
                
                <div class="detail-item">
                  <label>Deadline</label>
                  <div class="value"><?php echo $opp['deadlines'] ? date('M d, Y', strtotime($opp['deadlines'])) : 'Rolling'; ?></div>
                </div>
              </div>
              
              <div class="opportunity-actions">
                <?php 
                $website_url = $opp['website_link'];
                // Add https:// if no protocol is specified
                if (!preg_match("~^(?:f|ht)tps?://~i", $website_url)) {
                    $website_url = "https://" . $website_url;
                }
                ?>
                <a href="<?php echo htmlspecialchars($website_url); ?>" target="_blank" class="btn-visit">
                  <i class="bi bi-box-arrow-up-right"></i> Visit Website
                </a>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        
        <div class="text-center" data-aos="fade-up">
          <a href="search-all.php" class="btn-view-all">View All Opportunities <i class="bi bi-arrow-right"></i></a>
        </div>
        <?php else: ?>
        <div class="text-center" data-aos="fade-up">
          <p style="color: #666;">No recent updates available.</p>
          <a href="search-all.php" class="btn-view-all">Browse All Opportunities <i class="bi bi-arrow-right"></i></a>
        </div>
        <?php endif; ?>
      </div>
    </section><!-- /Recent Opportunities Section -->

    <!-- CTA Section -->
    <?php if (!$is_logged_in): ?>
    <section class="cta-section">
      <div class="container" data-aos="fade-up">
        <h2>Ready to Get Started?</h2>
        <p>Create an account to save your favorite opportunities and track deadlines</p>
        <a href="register-page.php" class="btn-cta"><i class="bi bi-person-plus"></i> Sign Up Free</a>
        <a href="login-page.php" class="btn-cta secondary"><i class="bi bi-box-arrow-in-right"></i> Login</a>
      </div>
    </section>
    <?php endif; ?>

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

  <script>
  function toggleFavorite(opportunityId, button) {
    const isSaved = button.classList.contains('saved');
    const action = isSaved ? 'remove' : 'add';
    
    fetch('toggle-favorite.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
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
  </script>

</body>

</html>