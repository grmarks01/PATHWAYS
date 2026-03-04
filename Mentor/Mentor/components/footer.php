<footer id="footer" class="footer position-relative light-background">

    <div class="container footer-top">
      <div class="row gy-4">
        <div class="col-lg-3 col-md-6 footer-about">
          <a href="index.php" class="logo d-flex align-items-center">
            <span class="sitename">Pathways</span>
          </a>
          <div class="footer-contact pt-3">
            <p>Discover opportunities for scholarships, competitions, clubs, and academic programs across the United States.</p>
          </div>
          <div class="social-links d-flex mt-4">
            <a href=""><i class="bi bi-twitter-x"></i></a>
            <a href=""><i class="bi bi-facebook"></i></a>
            <a href=""><i class="bi bi-instagram"></i></a>
            <a href=""><i class="bi bi-linkedin"></i></a>
          </div>
        </div>

        <div class="col-lg-2 col-md-3 footer-links">
          <h4>Useful Links</h4>
          <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="about.php">About us</a></li>
            <li><a href="search-all.php">Search All</a></li>
            <li><a href="contact.php">Contact</a></li>
          </ul>
        </div>

        <div class="col-lg-2 col-md-3 footer-links">
          <h4>Categories</h4>
          <ul>
            <li><a href="clubs.php">Clubs</a></li>
            <li><a href="scholarships.php">Scholarships</a></li>
            <li><a href="events.php">Competitions</a></li>
            <li><a href="events.php">Programs</a></li>
          </ul>
        </div>

        <div class="col-lg-2 col-md-3 footer-links">
          <h4>Account</h4>
          <ul>
            <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true): ?>
              <li><a href="my-profile.php">My Profile</a></li>
              <li><a href="logout.php">Logout</a></li>
            <?php else: ?>
              <li><a href="my-profile.php">My Profile</a></li>
              <li><a href="login-page.php">Login</a></li>
              <li><a href="register-page.php">Sign Up</a></li>
            <?php endif; ?>
          </ul>
        </div>

        <div class="col-lg-3 col-md-6 footer-links">
          <h4>Resources</h4>
          <ul>
            <li><a href="contact.php">Help Center</a></li>
            <li><a href="#" onclick="window.open('terms-of-service.php', 'Terms of Service', 'width=900,height=700,scrollbars=yes'); return false;">Terms of Service</a></li>
            <li><a href="#" onclick="window.open('privacy-policy.php', 'Privacy Policy', 'width=900,height=700,scrollbars=yes'); return false;">Privacy Policy</a></li>
            <li><a href="contact.php">FAQ</a></li>
          </ul>
        </div>

      </div>
    </div>

    <div class="container copyright text-center mt-4">
      <p>© <span>Copyright</span> <strong class="px-1 sitename">Pathways</strong> <span>All Rights Reserved</span></p>
      <div class="credits">
        Designed by <a href="https://bootstrapmade.com/">BootstrapMade</a>
      </div>
    </div>

  </footer>