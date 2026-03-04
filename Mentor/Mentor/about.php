<?php
session_start();

// Include database configuration
require_once 'components/db-config.php';
$conn = getDBConnection();

// Get some statistics
$stats_query = "SELECT 
    COUNT(*) as total_opportunities,
    COUNT(DISTINCT state) as total_states,
    COUNT(DISTINCT category) as total_categories,
    COUNT(DISTINCT CASE WHEN category = 'Scholarship' THEN id END) as total_scholarships
    FROM pathways_opportunities";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();

// Get user count
$user_query = "SELECT COUNT(*) as total_users FROM users";
$user_result = $conn->query($user_query);
$user_stats = $user_result->fetch_assoc();

closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>About - Pathways</title>
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
    .mission-card {
      background: white;
      padding: 40px;
      border-radius: 10px;
      box-shadow: 0 0 20px rgba(0, 0, 0, 0.08);
      margin-bottom: 30px;
    }
    
    .mission-card i {
      font-size: 48px;
      color: #5fcf80;
      margin-bottom: 20px;
    }
    
    .mission-card h3 {
      font-size: 24px;
      font-weight: 700;
      margin-bottom: 15px;
      color: #333;
    }
    
    .mission-card p {
      color: #666;
      line-height: 1.8;
    }
    
    .values-section {
      background: #f8f9fa;
      padding: 80px 0;
    }
    
    .value-item {
      text-align: center;
      padding: 30px;
      background: white;
      border-radius: 10px;
      box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
      height: 100%;
      transition: transform 0.3s;
    }
    
    .value-item:hover {
      transform: translateY(-5px);
    }
    
    .value-item i {
      font-size: 48px;
      color: #5fcf80;
      margin-bottom: 20px;
    }
    
    .value-item h4 {
      font-size: 20px;
      font-weight: 600;
      margin-bottom: 15px;
      color: #333;
    }
    
    .value-item p {
      color: #666;
      font-size: 14px;
      line-height: 1.6;
    }
    
    .cta-box {
      background: linear-gradient(135deg, #5fcf80 0%, #4ab86a 100%);
      color: white;
      padding: 60px;
      border-radius: 10px;
      text-align: center;
      margin-top: 60px;
    }
    
    .cta-box h3 {
      font-size: 32px;
      margin-bottom: 20px;
      font-weight: 700;
    }
    
    .cta-box p {
      font-size: 18px;
      margin-bottom: 30px;
      opacity: 0.9;
    }
    
    .btn-cta-white {
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
    
    .btn-cta-white:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
      color: #5fcf80;
    }
  </style>
</head>

<body class="about-page">

<?php include('components/header.php'); ?>

  <main class="main">

    <!-- Page Title -->
    <div class="page-title" data-aos="fade">
      <div class="heading">
        <div class="container">
          <div class="row d-flex justify-content-center text-center">
            <div class="col-lg-8">
              <h1>About Pathways</h1>
              <p class="mb-0">Empowering students to discover and pursue opportunities that shape their future through scholarships, competitions, clubs, and academic programs nationwide.</p>
            </div>
          </div>
        </div>
      </div>
      <nav class="breadcrumbs">
        <div class="container">
          <ol>
            <li><a href="index.php">Home</a></li>
            <li class="current">About Us</li>
          </ol>
        </div>
      </nav>
    </div><!-- End Page Title -->

    <!-- Mission Section -->
    <section id="mission" class="section">
      <div class="container">
        <div class="row gy-4">
          <div class="col-lg-6 order-2 order-lg-1" data-aos="fade-up" data-aos-delay="100">
            <div class="mission-card">
              <i class="bi bi-bullseye"></i>
              <h3>Our Mission</h3>
              <p>Pathways is dedicated to connecting students with life-changing opportunities across the United States. We believe that every student deserves access to scholarships, competitions, clubs, and academic programs that can help them reach their full potential.</p>
              <p>Our platform serves as a comprehensive resource hub, making it easier for students, parents, and educators to discover and track opportunities that align with their interests, goals, and academic pursuits.</p>
            </div>
          </div>

          <div class="col-lg-6 order-1 order-lg-2" data-aos="fade-up" data-aos-delay="200">
            <img src="assets/img/about.jpg" class="img-fluid" alt="Students collaborating" style="border-radius: 10px;">
          </div>
        </div>

        <div class="row gy-4 mt-5">
          <div class="col-lg-6 order-1 order-lg-1" data-aos="fade-up" data-aos-delay="100">
            <img src="assets/img/about/about-1.webp" class="img-fluid" alt="Academic success" style="border-radius: 10px;">
          </div>

          <div class="col-lg-6 order-2 order-lg-2" data-aos="fade-up" data-aos-delay="200">
            <div class="mission-card">
              <i class="bi bi-lightbulb"></i>
              <h3>What We Offer</h3>
              <ul style="list-style: none; padding: 0;">
                <li style="margin-bottom: 15px;"><i class="bi bi-check-circle" style="color: #5fcf80; margin-right: 10px;"></i> <strong>Comprehensive Database:</strong> Access to hundreds of opportunities across all 50 states</li>
                <li style="margin-bottom: 15px;"><i class="bi bi-check-circle" style="color: #5fcf80; margin-right: 10px;"></i> <strong>Smart Search Tools:</strong> Filter by state, category, grade level, and deadline</li>
                <li style="margin-bottom: 15px;"><i class="bi bi-check-circle" style="color: #5fcf80; margin-right: 10px;"></i> <strong>Personalized Tracking:</strong> Save and organize your favorite opportunities</li>
                <li style="margin-bottom: 15px;"><i class="bi bi-check-circle" style="color: #5fcf80; margin-right: 10px;"></i> <strong>Regular Updates:</strong> Stay informed about newly added programs and deadlines</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </section><!-- End Mission Section -->

    <!-- Stats Section -->
    <section id="counts" class="section counts light-background">
      <div class="container" data-aos="fade-up" data-aos-delay="100">
        <div class="row gy-4">
          <div class="col-lg-3 col-md-6">
            <div class="stats-item text-center w-100 h-100">
              <span data-purecounter-start="0" data-purecounter-end="<?php echo $stats['total_opportunities']; ?>" data-purecounter-duration="1" class="purecounter"></span>
              <p>Opportunities</p>
            </div>
          </div>

          <div class="col-lg-3 col-md-6">
            <div class="stats-item text-center w-100 h-100">
              <span data-purecounter-start="0" data-purecounter-end="<?php echo $stats['total_states']; ?>" data-purecounter-duration="1" class="purecounter"></span>
              <p>States Covered</p>
            </div>
          </div>

          <div class="col-lg-3 col-md-6">
            <div class="stats-item text-center w-100 h-100">
              <span data-purecounter-start="0" data-purecounter-end="<?php echo $stats['total_categories']; ?>" data-purecounter-duration="1" class="purecounter"></span>
              <p>Categories</p>
            </div>
          </div>

          <div class="col-lg-3 col-md-6">
            <div class="stats-item text-center w-100 h-100">
              <span data-purecounter-start="0" data-purecounter-end="<?php echo $user_stats['total_users']; ?>" data-purecounter-duration="1" class="purecounter"></span>
              <p>Active Users</p>
            </div>
          </div>
        </div>
      </div>
    </section><!-- End Stats Section -->

    <!-- Values Section -->
    <section class="values-section">
      <div class="container">
        <div class="container section-title" data-aos="fade-up">
          <h2>Our Values</h2>
          <p>What drives us to serve students and educators</p>
        </div>

        <div class="row gy-4">
          <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="100">
            <div class="value-item">
              <i class="bi bi-universal-access"></i>
              <h4>Accessibility</h4>
              <p>We believe every student should have equal access to opportunities, regardless of their background or location.</p>
            </div>
          </div>

          <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="200">
            <div class="value-item">
              <i class="bi bi-transparency"></i>
              <h4>Transparency</h4>
              <p>Clear, accurate, and up-to-date information about eligibility requirements, deadlines, and application processes.</p>
            </div>
          </div>

          <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="300">
            <div class="value-item">
              <i class="bi bi-heart"></i>
              <h4>Student-Centered</h4>
              <p>Every feature and resource is designed with students' success and educational journey in mind.</p>
            </div>
          </div>

          <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="400">
            <div class="value-item">
              <i class="bi bi-shield-check"></i>
              <h4>Quality</h4>
              <p>We carefully curate and verify opportunities to ensure they meet our standards for legitimacy and value.</p>
            </div>
          </div>

          <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="500">
            <div class="value-item">
              <i class="bi bi-arrows-move"></i>
              <h4>Innovation</h4>
              <p>Continuously improving our platform with new features and tools to better serve our community.</p>
            </div>
          </div>

          <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="600">
            <div class="value-item">
              <i class="bi bi-people"></i>
              <h4>Community</h4>
              <p>Building a supportive network of students, educators, and parents working together toward success.</p>
            </div>
          </div>
        </div>
      </div>
    </section><!-- End Values Section -->

    <!-- CTA Section -->
    <section class="section">
      <div class="container">
        <div class="cta-box" data-aos="fade-up">
          <h3>Ready to Discover Your Pathway?</h3>
          <p>Join thousands of students who have found their perfect opportunities through our platform</p>
          <a href="search-all.php" class="btn-cta-white"><i class="bi bi-search"></i> Explore Opportunities</a>
          <?php if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true): ?>
          <a href="register-page.php" class="btn-cta-white"><i class="bi bi-person-plus"></i> Create Free Account</a>
          <?php endif; ?>
        </div>
      </div>
    </section><!-- End CTA Section -->

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