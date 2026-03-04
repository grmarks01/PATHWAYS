<?php  
// Get the current page filename
$current_page = basename($_SERVER['PHP_SELF']);

// Check if user is logged in
$is_logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;

// Check if user is admin (approved teacher with Active = '1')
$is_admin = false;
if ($is_logged_in) {
    // User is admin if they are a teacher AND Active = '1'
    $is_admin = (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'teacher' && 
                 isset($_SESSION['user_active']) && $_SESSION['user_active'] === '1');
}
?>

<header id="header" class="header d-flex align-items-center sticky-top">
    <div class="container-fluid container-xl position-relative d-flex align-items-center">
      <a href="index.php" class="logo d-flex align-items-center me-auto">
        <!-- Uncomment the line below if you also wish to use an image logo -->
        <!-- <img src="assets/img/logo.webp" alt=""> -->
        <h1 class="sitename">Pathways</h1>
      </a>
      <nav id="navmenu" class="navmenu">
        <ul>
          <li><a href="index.php" class="<?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">Home<br></a></li>
          <li><a href="about.php" class="<?php echo ($current_page == 'about.php') ? 'active' : ''; ?>">About</a></li>
		  <li><a href="search-all.php">Search All</a></li>
          <li><a href="clubs.php" class="<?php echo ($current_page == 'clubs.php') ? 'active' : ''; ?>">Clubs</a></li>
          <li><a href="scholarships.php" class="<?php echo ($current_page == 'scholarships.php') ? 'active' : ''; ?>">Scholarships</a></li>
          <li><a href="events.php" class="<?php echo ($current_page == 'events.php') ? 'active' : ''; ?>">Events</a></li>
          <?php if (!$is_logged_in): ?>
          <li><a href="register-page.php" class="<?php echo ($current_page == 'register-page.php') ? 'active' : ''; ?>">Register</a></li>
          <?php endif; ?>
          <li><a href="contact.php" class="<?php echo ($current_page == 'contact.php') ? 'active' : ''; ?>">Contact</a></li>
		  <li><a href="my-profile.php">My Profile</a></li>
		  <?php if ($is_admin): ?>
		  <li class="dropdown"><a href="#"><span>Admin</span> <i class="bi bi-chevron-down toggle-dropdown"></i></a>
			<ul>
                <li><a href="import-export.php">Import/Export</a></li>
                <li><a href="reports-summaries.php">Reports/Summaries</a></li>
                <li><a href="admin-opportunities.php">Opportunities Management</a></li>
                <li><a href="usermanagement.php">User Management</a></li>
				<li><a href="contact-submissions.php">Contact Submissions</a></li>
            </ul>
            </li>
			 <?php endif; ?>
            </ul>
        <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
      </nav>
     <?php if ($is_logged_in): ?>
        <a class="btn-getstarted" href="logout.php">Logout</a>
      <?php else: ?>
        <a class="btn-getstarted" href="login-page.php">Login</a>
      <?php endif; ?>
    </div>
  </header>